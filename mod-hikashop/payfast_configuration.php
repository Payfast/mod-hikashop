<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/**
 * @package Payfast Plugin for HikaShop Joomla!
 * @author  payfast.io
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */
defined('_JEXEC') or die('Restricted access');

$merchantId     = isset($this->element->payment_params->payfast_merchant_id) ?
    $this->escape($this->element->payment_params->payfast_merchant_id) : '';
$merchantKey    = isset($this->element->payment_params->payfast_merchant_key) ?
    $this->escape($this->element->payment_params->payfast_merchant_key) : '';
$passphrase     = isset($this->element->payment_params->payfast_passphrase) ?
    $this->escape($this->element->payment_params->payfast_passphrase) : '';
$sandbox        = $this->element->payment_params->payfast_sandbox ?? 0;
$debug          = $this->element->payment_params->payfast_debug ?? 0;
$cancelUrl      = isset($this->element->payment_params->payfast_cancel_url) ?
    $this->escape($this->element->payment_params->payfast_cancel_url) : '';
$returnUrl      = isset($this->element->payment_params->payfast_return_url) ?
    $this->escape($this->element->payment_params->payfast_return_url) : '';
$invalidStatus  = $this->element->payment_params->invalid_status ?? '';
$pendingStatus  = $this->element->payment_params->pending_status ?? '';
$verifiedStatus = $this->element->payment_params->verified_status ?? '';
?>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_merchant_id]">
            <?php
            echo JText::_('PAYFAST_MERCHANT_ID'); ?>
        </label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_merchant_id]"
               value="<?php
               echo $merchantId; ?>"/>
    </td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_merchant_key]">
            <?php
            echo JText::_('PAYFAST_MERCHANT_KEY'); ?>
        </label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_merchant_key]"
               value="<?php
               echo $merchantKey; ?>"/>
    </td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_passphrase]">
            <?php
            echo JText::_('PAYFAST_PASSPHRASE'); ?>
        </label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_passphrase]"
               value="<?php
               echo $passphrase; ?>"/>
    </td>
</tr>

<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_sandbox]">
            <?php
            echo JText::_('PAYFAST_SANDBOX'); ?>
        </label>
    </td>
    <td><?php
        echo JHtml::_(
            'hikaselect.booleanlist',
            'data[payment][payment_params][payfast_sandbox]',
            '',
            $sandbox
        );
        ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_debug]"><?php
            echo JText::_('PAYFAST_DEBUG');
            ?></label>
    </td>
    <td><?php
        echo JHtml::_('hikaselect.booleanlist', 'data[payment][payment_params][payfast_debug]', '', $debug);
        ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][cancel_url]"><?php
            echo JText::_('PAYFAST_CANCEL_URL');
            ?></label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_cancel_url]" value="<?php
        echo $cancelUrl; ?>"/>
    </td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][return_url]"><?php
            echo JText::_('PAYFAST_RETURN_URL');
            ?></label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_return_url]" value="<?php
        echo $returnUrl; ?>"/>
    </td>
</tr>


<tr>
    <td class="key">
        <label for="data[payment][payment_params][invalid_status]"><?php
            echo JText::_('INVALID_STATUS');
            ?></label>
    </td>
    <td><?php
        echo $this->data['order_statuses']->display('data[payment][payment_params][invalid_status]', $invalidStatus);
        ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][pending_status]"><?php
            echo JText::_('PENDING_STATUS');
            ?></label>
    </td>
    <td><?php
        echo $this->data['order_statuses']->display('data[payment][payment_params][pending_status]', $pendingStatus);
        ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][verified_status]"><?php
            echo JText::_('VERIFIED_STATUS');
            ?></label>
    </td>
    <td><?php
        echo $this->data['order_statuses']->display('data[payment][payment_params][verified_status]', $verifiedStatus);
        ?></td>
</tr>
