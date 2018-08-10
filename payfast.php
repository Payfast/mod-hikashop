<?php
/**
 * @package PayFast Plugin for HikaShop Joomla!
 * @version 1.0.0
 * @author  payfast.co.za
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
?><?php
class plgHikashoppaymentPayfast extends hikashopPaymentPlugin
{
    var $accepted_currencies = array(
        'ZAR',
    );

    var $multiple = true;
    var $name = 'payfast';
    var $doc_form = 'payfast';

    const SANDBOX_MERCHANT_KEY = '46f0cd694581a';
    const SANDBOX_MERCHANT_ID = '10000100';

     function __construct( &$subject, $config )
    {
        parent::__construct( $subject, $config );
        $lang = JFactory::getLanguage();
        $lang->load( 'plg_hikashoppayfast', JPATH_ADMINISTRATOR );
    }

     function onBeforeOrderCreate( &$order, &$do )
    {
        if ( parent::onBeforeOrderCreate( $order, $do ) === true )
        {
            return true;
        }

        if (  ( empty( $this->payment_params->payfast_merchant_id ) || empty( $this->payment_params->payfast_merchant_key ) ) && !$this->payment_params->payfast_sandbox )
        {
            $this->app->enqueueMessage( 'Please check your &quot;PayFast&quot; plugin configuration, a merchant ID and Key are required' );
            $do = false;
        }

    }

     function onAfterOrderConfirm( &$order, &$methods, $method_id )
    {
        parent::onAfterOrderConfirm( $order, $methods, $method_id );

        if ( $this->currency->currency_locale['int_frac_digits'] > 2 )
        {
            $this->currency->currency_locale['int_frac_digits'] = 2;
        }

        $notify_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=' . $this->name . '&tmpl=component&lang=' . $this->locale . $this->url_itemid;
        $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order->order_id . $this->url_itemid;
        $cancel_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' . $order->order_id . $this->url_itemid;

        if ( !empty( $this->payment_params->payfast_return_url ) )
        {
            $return_url = $this->payment_params->payfast_return_url;
        }

        if ( !empty( $this->payment_params->payfast_cancel_url ) )
        {
            $cancel_url = $this->payment_params->payfast_cancel_url;
        }

        $tax_total = '';
        $discount_total = '';

        if ( $this->payment_params->payfast_sandbox )
        {
            $payfast_url = 'https://sandbox.payfast.co.za/eng/process';
            $merchant_id = self::SANDBOX_MERCHANT_ID;
            $merchant_key = self::SANDBOX_MERCHANT_KEY;
        }
        else
        {
            $payfast_url = 'https://www.payfast.co.za/eng/process';
            $merchant_id = $this->payment_params->payfast_merchant_id;
            $merchant_key = $this->payment_params->payfast_merchant_key;
        }
        $config = JFactory::getConfig();

        $sitename = $config->get( 'sitename' );
        $vars = array(
            'merchant_id' => $merchant_id,
            'merchant_key' => $merchant_key,
            'return_url' => $return_url,
            'cancel_url' => $cancel_url,
            'notify_url' => $notify_url,
            'email_address' => $this->user->user_email,
            'm_payment_id' => $order->order_id,
            'amount' => round( $order->cart->full_total->prices[0]->price_value_with_tax, (int) $this->currency->currency_locale['int_frac_digits'] ),
            'item_name' => $sitename . ' - Order #' . $order->order_id,
        );

        $pfOutput = '';
        foreach ( $vars as $key => $val )
        {
            $pfOutput .= $key . '=' . urlencode( trim( $val ) ) . '&';
        }

        $passPhrase = $this->payment_params->payfast_passphrase;
        if ( empty( $passPhrase ) || $this->payment_params->payfast_sandbox )
        {
            $pfOutput = substr( $pfOutput, 0, -1 );
        }
        else
        {
            $pfOutput = $pfOutput . "passphrase=" . urlencode( $passPhrase );
        }

        $vars['signature'] = md5( $pfOutput );
        $vars['user_agent'] = 'HikaShop 3.5.1';
        $vars['payfast_url'] = $payfast_url;

        $this->vars = $vars;
        return $this->showPage( 'end' );
    }

     function onPaymentNotification( &$statuses )
    {

        $pfError = false;
        $pfErrMsg = '';
        $pfDone = false;
        $pfData = array();
        $pfParamString = '';

        //// Notify PayFast that information has been received
        if ( !$pfError && !$pfDone )
        {
            header( 'HTTP/1.0 200 OK' );
            flush();
        }

        $dbOrder = $this->getOrder( (int) @$_POST['m_payment_id'] );
        $this->loadPaymentParams( $dbOrder );
        if ( empty( $this->payment_params ) )
        {
            return false;
        }

        $pfHost = $this->payment_params->payfast_sandbox ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        define( 'PF_DEBUG', $this->payment_params->payfast_debug );
        include_once 'payfast_common.inc';
        pflog( 'PayFast ITN call received' );
        //// Get data sent by PayFast
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Get posted data' );

            // Posted variables from ITN
            $pfData = pfGetData();

            pflog( 'PayFast Data: ' . print_r( $pfData, true ) );

            if ( $pfData === false )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Verify security signature' );

            $passPhrase = $this->payment_params->payfast_passphrase;
            $pfPassPhrase = empty( $passPhrase ) ? null : $passPhrase;
            // If signature different, log for debugging
            if ( !pfValidSignature( $pfData, $pfParamString, $pfPassPhrase ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
            }
        }

        //// Verify source IP (If not in debug mode)
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Verify source IP' );

            if ( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
            }
        }
        //// Get internal cart
        if ( !$pfError && !$pfDone )
        {
            $this->loadOrderData( $dbOrder );

            if ( empty( $dbOrder ) )
            {
                pflog( 'Could not load any order for your notification ' . $pfData['m_payment_id'] );
                return false;
            }

            $order_id = $dbOrder->order_id;

            pflog( "Purchase:\n" . print_r( $order_info, true ) );
        }

        //// Verify data received
        if ( !$pfError )
        {
            pflog( 'Verify data received' );

            $pfValid = pfValidData( $pfHost, $pfParamString );

            if ( !$pfValid )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Check data against internal order
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Check data against internal order' );

            $amount = round( $dbOrder->order_full_price, (int) $this->currency->currency_locale['int_frac_digits'] );
            // Check order amount
            if ( !pfAmountsEqual( $pfData['amount_gross'], $amount ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
            }

        }

        //// Check status and update order
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Check status and update order' );

            $transaction_id = $pfData['pf_payment_id'];

            switch ( $pfData['payment_status'] )
            {
                case 'COMPLETE':
                    pflog( '- Complete' );
                    pflog( 'PayFast transaction id: ' . $pfData['pf_payment_id'] );

                    $history = new stdClass();
                    $history->notified = 0;
                    $history->amount = $pfData['amount_gross'];
                    $history->data = ob_get_clean();

                    $order_status = $this->payment_params->verified_status;
                    if ( $dbOrder->order_status == $order_status )
                    {
                        return true;
                    }

                    $config = &hikashop_config();
                    if ( $config->get( 'order_confirmed_status', 'confirmed' ) == $order_status )
                    {
                        $history->notified = 1;
                    }

                    $email = new stdClass();
                    $email->subject = JText::sprintf( 'PAYMENT_NOTIFICATION_FOR_ORDER', 'Payfast', $pfData['payment_status'], $dbOrder->order_number );
                    $email->body = str_replace( '<br/>', "\r\n", JText::sprintf( 'PAYMENT_NOTIFICATION_STATUS', 'Payfast', $pfData['payment_status'] ) ) . ' ' . JText::sprintf( 'ORDER_STATUS_CHANGED', $order_status ) . "\r\n\r\n" . $order_text;

                    $this->modifyOrder( $order_id, $order_status, $history, $email );
                    return true;
                    break;

                case 'FAILED':
                    pflog( '- Failed' );

                    $email = new stdClass();
                    $email->subject = JText::sprintf( 'NOTIFICATION_REFUSED_FOR_THE_ORDER', 'Payfast' ) . ' ' . JText::sprintf( 'PAYPAL_CONNECTION_FAILED', $dbOrder->order_number );
                    $email->body = str_replace( '<br/>', "\r\n", JText::sprintf( 'NOTIFICATION_REFUSED_NO_CONNECTION', 'Payfast' ) ) . "\r\n\r\n" . JText::sprintf( 'CHECK_DOCUMENTATION', HIKASHOP_HELPURL . 'payment-payfast-error#connection' ) . $order_text;
                    $action = false;
                    $this->modifyOrder( $action, null, null, $email );

                    JError::raiseError( 403, JText::_( 'Access Forbidden' ) );
                    return false;

                    break;

                case 'PENDING':
                    pflog( '- Pending' );

                    // Need to wait for "Completed" before processing
                    break;

                default:
                    // If unknown status, do nothing (safest course of action)
                    break;
            }
        }
        else
        {
            pflog( "Errors:\n" . print_r( $pfErrMsg, true ) );
        }
    }

     function onPaymentConfiguration( &$element )
    {
        $subtask = JRequest::getCmd( 'subtask', '' );

        parent::onPaymentConfiguration( $element );
    }

     function onPaymentConfigurationSave( &$element )
    {

        return true;
    }

     function getPaymentDefaultValues( &$element )
    {
        $element->payment_name = 'PayFast';
        $element->payment_description = 'You can pay with PayFast using this payment method';
        $element->payment_images = 'payfast';

        $element->payment_params->payfast_debug = 1;
        $element->payment_params->payfast_sandbox = 1;
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->pending_status = 'created';
        $element->payment_params->verified_status = 'confirmed';
        $element->payment_params->address_override = 1;
    }

}
