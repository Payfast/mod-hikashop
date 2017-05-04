<?php
/**
 * @package PayFast Plugin for HikaShop Joomla!
 * @version 1.0.0
 * @author  payfast.co.za
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
defined('_JEXEC') or die('Restricted access');
?>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_merchant_id]"><?php echo JText::_('PAYFAST_MERCHANT_ID');?></label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_merchant_id]" value="<?php echo $this->escape(@$this->element->payment_params->payfast_merchant_id); ?>" />
    </td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_merchant_key]"><?php echo JText::_('PAYFAST_MERCHANT_KEY');?></label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_merchant_key]" value="<?php echo $this->escape(@$this->element->payment_params->payfast_merchant_key); ?>" />
    </td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_passphrase]"><?php echo JText::_('PAYFAST_PASSPHRASE');?></label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_passphrase]" value="<?php echo $this->escape(@$this->element->payment_params->payfast_passphrase); ?>" />
    </td>
</tr>

<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_sandbox]"><?php echo JText::_('PAYFAST_SANDBOX');?></label>
    </td>
    <td><?php
        echo JHTML::_('hikaselect.booleanlist', "data[payment][payment_params][payfast_sandbox]" , '', @$this->element->payment_params->payfast_sandbox);
    ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][payfast_debug]"><?php
            echo JText::_('PAYFAST_DEBUG');
        ?></label>
    </td>
    <td><?php
        echo JHTML::_('hikaselect.booleanlist', "data[payment][payment_params][payfast_debug]" , '', @$this->element->payment_params->payfast_debug);
    ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][cancel_url]"><?php
            echo JText::_('PAYFAST_CANCEL_URL');
        ?></label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_cancel_url]" value="<?php echo $this->escape(@$this->element->payment_params->payfast_cancel_url); ?>" />
    </td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][return_url]"><?php
            echo JText::_('PAYFAST_RETURN_URL');
        ?></label>
    </td>
    <td>
        <input type="text" name="data[payment][payment_params][payfast_return_url]" value="<?php echo $this->escape(@$this->element->payment_params->payfast_return_url); ?>" />
    </td>
</tr>


<tr>
    <td class="key">
        <label for="data[payment][payment_params][invalid_status]"><?php
            echo JText::_('INVALID_STATUS');
        ?></label>
    </td>
    <td><?php
        echo $this->data['order_statuses']->display("data[payment][payment_params][invalid_status]", @$this->element->payment_params->invalid_status);
    ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][pending_status]"><?php
            echo JText::_('PENDING_STATUS');
        ?></label>
    </td>
    <td><?php
        echo $this->data['order_statuses']->display("data[payment][payment_params][pending_status]", @$this->element->payment_params->pending_status);
    ?></td>
</tr>
<tr>
    <td class="key">
        <label for="data[payment][payment_params][verified_status]"><?php
            echo JText::_('VERIFIED_STATUS');
        ?></label>
    </td>
    <td><?php
        echo $this->data['order_statuses']->display("data[payment][payment_params][verified_status]", @$this->element->payment_params->verified_status);
    ?></td>
</tr>