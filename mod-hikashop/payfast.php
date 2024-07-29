<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/**
 * @package Payfast Plugin for HikaShop Joomla!
 * @author  payfast.io
 * Copyright (c) 2024 Payfast (Pty) Ltd
 **/
defined('_JEXEC') or die('Restricted access');

require_once __DIR__ . '/vendor/autoload.php';

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\Event\DispatcherInterface;
use Payfast\PayfastCommon\PayfastCommon;
use Joomla\CMS\Factory;

/**
 * ITN class
 */
class PlgHikashopPaymentPayfast extends hikashopPaymentPlugin
{
    public $currencies = [
        'ZAR',
    ];

    public $multiple = true;
    public $name = 'payfast';
    public $docForm = 'payfast';

    /**
     * Constructor
     *
     * @param DispatcherInterface  &$subject The object to observe
     * @param array $config An optional associative array of configuration settings.
     *                                          Recognized key values include 'name', 'group', 'params', 'language'
     *                                         (this list is not meant to be comprehensive).
     *
     * @throws Exception
     * @since   1.5
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $language = Factory::getApplication()->getLanguage();
        $language->load('plg_hikashoppayfast', JPATH_ADMINISTRATOR);
    }

    public function onBeforeOrderCreate(&$order, &$do)
    {
        if (parent::onBeforeOrderCreate($order, $do) === true) {
            return true;
        }

        if ((empty($this->payment_params->payfast_merchant_id) || empty($this->payment_params->payfast_merchant_key)) &&
            !$this->payment_params->payfast_sandbox) {
            $this->app->enqueueMessage(
                'Please check your &quot;Payfast&quot; plugin configuration, a merchant ID and Key are required'
            );
            $do = false;
        }

        return false;
    }

    /**
     * Send data to the Payfast API
     *
     * @param $order
     * @param $methods
     * @param $method_id
     *
     * @return bool
     * @throws Exception
     */
    public function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        parent::onAfterOrderConfirm($order, $methods, $method_id);

        if ($this->currency->currency_locale['int_frac_digits'] > 2) {
            $this->currency->currency_locale['int_frac_digits'] = 2;
        }

        $notifyUrl = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=' .
                     $this->name . '&tmpl=component&lang=' . $this->locale . $this->url_itemid;
        $returnUrl = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' .
                     $order->order_id . $this->url_itemid;
        $cancelUrl = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' .
                     $order->order_id . $this->url_itemid;

        if (!empty($this->payment_params->payfast_return_url)) {
            $returnUrl = $this->payment_params->payfast_return_url;
        }

        if (!empty($this->payment_params->payfast_cancel_url)) {
            $cancelUrl = $this->payment_params->payfast_cancel_url;
        }

        $merchantId  = $this->payment_params->payfast_merchant_id;
        $merchantKey = $this->payment_params->payfast_merchant_key;

        if ($this->payment_params->payfast_sandbox) {
            $payfast_url = 'https://sandbox.payfast.co.za/eng/process';
        } else {
            $payfast_url = 'https://www.payfast.co.za/eng/process';
        }
        $config = Factory::getApplication()->getConfig();

        $sitename  = $config->get('sitename');
        $dataArray = [
            'merchant_id'   => $merchantId,
            'merchant_key'  => $merchantKey,
            'return_url'    => $returnUrl,
            'cancel_url'    => $cancelUrl,
            'notify_url'    => $notifyUrl,
            'email_address' => $this->user->user_email,
            'm_payment_id'  => $order->order_id,
            'amount'        => round(
                $order->cart->full_total->prices[0]->price_value_with_tax,
                (int)$this->currency->currency_locale['int_frac_digits']
            ),
            'item_name'     => $sitename . ' - Order #' . $order->order_id,
        ];

        $pfOutput = '';
        foreach ($dataArray as $key => $val) {
            $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
        }

        $passPhrase = $this->payment_params->payfast_passphrase;
        if (empty($passPhrase)) {
            $pfOutput = substr($pfOutput, 0, -1);
        } else {
            $pfOutput = $pfOutput . 'passphrase=' . urlencode($passPhrase);
        }

        $dataArray['signature']   = md5($pfOutput);
        $dataArray['user_agent']  = 'HikaShop 5.1';
        $dataArray['payfast_url'] = $payfast_url;

        $this->vars = $dataArray;

        return $this->showPage('end');
    }

    /**
     * ITN handler
     *
     * @param $statuses
     *
     * @return bool
     * @throws Exception
     */
    public function onPaymentNotification(&$statuses)
    {
        // Call the parent method
        parent::onPaymentNotification($statuses);

        // Debug mode
        $payfastCommon = new PayfastCommon(true);
        $pfError       = false;
        $pfErrMsg      = '';
        $pfParamString = '';

        // Notify Payfast that information has been received
        header('HTTP/1.0 200 OK');
        flush();

        $dbOrder = $this->getOrder((int)($_POST['m_payment_id'] ?? null));
        $this->loadPaymentParams($dbOrder);
        $paymentStatus = !empty($this->payment_params);
        $pfHost        = $this->payment_params->payfast_sandbox ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';

        // Module parameters for pfValidData
        $moduleInfo = [
            "pfSoftwareName"       => 'HikaShop',
            "pfSoftwareVer"        => '5.1.',
            "pfSoftwareModuleName" => 'Payfast-HikaShop',
            "pfModuleVer"          => '1.5.',
        ];

        $payfastCommon->pflog('Payfast ITN call received');
        // Get data sent by Payfast
        $payfastCommon->pflog('Get posted data');

        // Posted variables from ITN
        $pfData = $payfastCommon->pfGetData();

        $payfastCommon->pflog('Payfast Data: ' . print_r($pfData, true));

        if ($pfData === false) {
            $pfError  = true;
            $pfErrMsg = $payfastCommon::PF_ERR_BAD_ACCESS;
        }

        // Verify security signature
        $pfError = $this->verifySecuritySignature($payfastCommon, $pfData, $pfParamString);

        // Verify source IP (If not in debug mode)
        // Get internal cart
        if (!$pfError) {
            $this->loadOrderData($dbOrder);

            if (empty($dbOrder)) {
                $payfastCommon->pflog('Could not load any order for your notification ' . $pfData['m_payment_id']);
                $paymentStatus = false;
            }


            $payfastCommon->pflog("Purchase:\n" . print_r($order_info, true));
        }

        // Verify data received
        if (!$pfError) {
            $payfastCommon->pflog('Verify data received');

            $pfValid = $payfastCommon->pfValidData($moduleInfo, $pfHost, $pfParamString);

            if (!$pfValid) {
                $pfError  = true;
                $pfErrMsg = $payfastCommon::PF_ERR_BAD_ACCESS;
            }
        }

        // Check data against internal order
        if (!$pfError) {
            $payfastCommon->pflog('Check data against internal order');

            $amount = round($dbOrder->order_full_price, (int)$this->currency->currency_locale['int_frac_digits']);
            // Check order amount
            if (!$payfastCommon->pfAmountsEqual($pfData['amount_gross'], $amount)) {
                $pfError  = true;
                $pfErrMsg = $payfastCommon::PF_ERR_AMOUNT_MISMATCH;
            }
        }

        // Check status and update order
        if (!$pfError) {
            $paymentStatus = $this->doPaymentStatus($payfastCommon, $pfData, $dbOrder);
        } else {
            $payfastCommon->pflog('Error occurred: ' . $pfErrMsg);
        }

        return $paymentStatus;
    }

    public function verifySecuritySignature(PayfastCommon $payfastCommon, $pfData, &$pfParamString)
    {
        $payfastCommon->pflog('Verify security signature');
        $passPhrase   = $this->payment_params->payfast_passphrase;
        $pfPassPhrase = empty($passPhrase) ? null : $passPhrase;

        if (!$payfastCommon->pfValidSignature($pfData, $pfParamString, $pfPassPhrase)) {
            $this->handleError($payfastCommon, $payfastCommon->PF_ERR_INVALID_SIGNATURE);

            return true;
        }

        return false;
    }

    /**
     * Handles the payments status update
     *
     * @param $pfData
     * @param $dbOrder
     *
     * @return bool|void
     */
    public function doPaymentStatus(PayfastCommon $payfastCommon, $pfData, $dbOrder)
    {
        $payfastCommon->pflog('Check status and update order');
        $paymentStatus = false;
        switch ($pfData['payment_status']) {
            case 'COMPLETE':
                $payfastCommon->pflog('- Complete');
                $payfastCommon->pflog('Payfast transaction id: ' . $pfData['pf_payment_id']);

                $history           = new stdClass();
                $history->notified = 0;
                $history->amount   = $pfData['amount_gross'];
                $history->data     = ob_get_clean();

                $order_status = $this->payment_params->verified_status;

                $config = &hikashop_config();
                if ($config->get('order_confirmed_status', 'confirmed') == $order_status) {
                    $history->notified = 1;
                }

                $email          = new stdClass();
                $email->subject = Text::sprintf(
                    'PAYMENT_NOTIFICATION_FOR_ORDER',
                    'Payfast',
                    $pfData['payment_status'],
                    $dbOrder->order_number
                );
                $email->body    = str_replace(
                                      '<br/>',
                                      "\r\n",
                                      Text::sprintf(
                                          'PAYMENT_NOTIFICATION_STATUS',
                                          'Payfast',
                                          $pfData['payment_status']
                                      )
                                  ) . ' ' . Text::sprintf(
                        'ORDER_STATUS_CHANGED',
                        $order_status
                    );

                $this->modifyOrder($dbOrder->order_id, $order_status, $history, $email);
                $paymentStatus = true;
                break;

            case 'FAILED':
                $payfastCommon->pflog('- Failed');

                $email          = new stdClass();
                $email->subject = Text::sprintf(
                        'NOTIFICATION_REFUSED_FOR_THE_ORDER',
                        'Payfast'
                    ) . ' ' . Text::sprintf('PAYPAL_CONNECTION_FAILED', $dbOrder->order_number);
                $email->body    = str_replace(
                                      '<br/>',
                                      "\r\n",
                                      Text::sprintf('NOTIFICATION_REFUSED_NO_CONNECTION', 'Payfast')
                                  ) . "\r\n\r\n" . Text::sprintf(
                        'CHECK_DOCUMENTATION',
                        HIKASHOP_HELPURL . 'payment-payfast-error#connection'
                    );
                $action         = false;
                $this->modifyOrder($action, null, null, $email);

                // Raise an error message and redirect with a 403 status
                try {
                    $app = Factory::getApplication();
                    if ($app instanceof CMSApplication) {
                        // Web application context
                        $app->enqueueMessage(Text::_('Access Forbidden'), 'error');
                        $app->redirect('index.php', 403);
                    } else {
                        // Handle other application contexts (e.g., console)
                        echo Text::_('Access Forbidden');
                        exit(403);
                    }
                } catch (Exception $e) {
                    echo 'An error occurred: ' . $e->getMessage();
                    exit(1); // Exit with a general error code
                }

                break;

            case 'PENDING':
                $payfastCommon->pflog('- Pending');

                // Need to wait for "Completed" before processing
                break;

            default:
                // If unknown status, do nothing (safest course of action)
                break;
        }

        return $paymentStatus;
    }

    public function handleError(PayfastCommon $payfastCommon, $errorMessage)
    {
        $payfastCommon->pflog('Error occurred: ' . $errorMessage);
        $this->pfError  = true;
        $this->pfErrMsg = $errorMessage;
    }

    /**
     * Save configuration
     *
     * @param $element
     *
     * @return true
     */
    public function onPaymentConfigurationSave(&$element)
    {
        // Call the parent method
        parent::onPaymentConfigurationSave($element);

        return true;
    }

    /**
     * Set payment default values
     *
     * @param $element
     */
    public function getPaymentDefaultValues(&$element)
    {
        // Call the parent method
        parent::getPaymentDefaultValues($element);

        $element->payment_name        = 'Payfast';
        $element->payment_description = 'You can pay with Payfast using this payment method';
        $element->payment_images      = 'payfast';

        $element->payment_params->payfast_debug    = 1;
        $element->payment_params->payfast_sandbox  = 1;
        $element->payment_params->invalid_status   = 'cancelled';
        $element->payment_params->pending_status   = 'created';
        $element->payment_params->verified_status  = 'confirmed';
        $element->payment_params->address_override = 1;
    }

}
