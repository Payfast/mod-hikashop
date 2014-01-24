<?php
/**
 * @package PayFast Plugin for HikaShop Joomla!
 * @version 1.0.0
 * @author  payfast.co.za
 * @copyright   (C) 2010-2014 All rights reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');

?><div class="hikashop_payfast_end" id="hikashop_payfast_end">
    <span id="hikashop_payfast_end_message" class="hikashop_payfast_end_message">
        <?php echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X', $this->payment_name).'<br/>'. JText::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED');?>
    </span>
    <span id="hikashop_payfast_end_spinner" class="hikashop_payfast_end_spinner hikashop_checkout_end_spinner">
    </span>
    <br/>
    <form id="hikashop_payfast_form" name="hikashop_payfast_form" action="<?php echo $this->vars['payfast_url']; ?>" method="post">
        <div id="hikashop_payfast_end_image" class="hikashop_payfast_end_image">
            <input id="hikashop_payfast_button" type="submit" class="btn btn-primary" value="<?php echo JText::_('PAY_NOW');?>" name="" alt="<?php echo JText::_('PAY_NOW');?>" />
        </div>
        <?php
        unset( $this->vars['payfast_url'] );
            foreach($this->vars as $name => $value ) {
                echo '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
            }
            JRequest::setVar('noform',1); ?>
    </form>
    <script type="text/javascript">
        <!--
        document.getElementById('hikashop_payfast_form').submit();
        //-->
    </script>
</div>
