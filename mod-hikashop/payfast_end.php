<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/**
 * @package Payfast Plugin for HikaShop Joomla!
 * @author  payfast.io
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

?>
<div class="hikashop_payfast_end" id="hikashop_payfast_end">
    <span id="hikashop_payfast_end_message" class="hikashop_payfast_end_message">
        <?php
        echo Text::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X', $this->payment_name) . '<br/>' .
             Text::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED'); ?>
    </span>
    <span id="hikashop_payfast_end_spinner" class="hikashop_payfast_end_spinner hikashop_checkout_end_spinner">
    </span>
    <br/>
    <form id="hikashop_payfast_form" name="hikashop_payfast_form" action="<?php
    echo $this->vars['payfast_url']; ?>"
          method="post">
        <div id="hikashop_payfast_end_image" class="hikashop_payfast_end_image">
            <input id="hikashop_payfast_button" type="submit" class="btn btn-primary"
                   value="<?php
                   echo Text::_('PAY_NOW'); ?>" name="" alt="<?php
            echo Text::_('PAY_NOW'); ?>"/>
        </div>
        <?php
        unset($this->vars['payfast_url']);
        foreach ($this->vars as $name => $value) {
            echo '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
        }
        try {
            $application = JFactory::getApplication();
            $application->input->set('noform', 1);
        } catch (Exception $error) {
            JLog::add($error->getMessage(), JLog::ERROR, 'jerror');
        } ?>
    </form>
    <script type="text/javascript">
      <!--
      document.getElementById('hikashop_payfast_form').submit()
      //-->
    </script>
</div>
