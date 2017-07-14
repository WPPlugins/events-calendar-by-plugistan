<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
/**
  Form that displays the configuration form in the administration panel.

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Abstract');

class Eabi_Ipenelo_Calendar_Form_Configuration extends Eabi_Ipenelo_Calendar_Form_Abstract {

    const __CLASS = __CLASS__;

    protected $_optionFunctions = array();

    public function render() {
        global $plugin_page;
        $html = '<div class="wrap">';
        $html .= "\r\n";

        //title icon
        $html .= '<div class="icon32" id="icon-options-general"><br></div>';
        $html .= "\r\n";

        //title itself
        $html .= '<h2>' . $this->__->l('Scheduler settings') . '</h2>';
        $html .= "\r\n";
        $html .= Eabi_Ipenelo_Calendar::displayErrors();
        $html .= Eabi_Ipenelo_Calendar::displayMessages();

        //form
        $html .= '<form action="' . admin_url('admin.php?noheader=true&page=' . $plugin_page) . '" method="post">';
        $html .= "\r\n";

        $html .= '<table class="form-table">';

        $html .= '<tbody>';

        foreach ($this->_data as $key => $value) {
            if (is_string($value) || $value == '') {
                if (isset($this->_optionFunctions[$key])) {
                    $func = $this->_optionFunctions[$key];
                    $callable = $func['function'];
                    if (isset($func['values'])) {
                        $html .= $this->$callable($key, $this->__->l('Configuration_' . $key), $this->_isset($this->_data, $key, true), $func['values']);
                    } else {
                        $html .= $this->$callable($key, $this->__->l('Configuration_' . $key), $this->_isset($this->_data, $key, true));
                    }
                } else {
                    $html .= $this->addTextField($key, $this->__->l('Configuration_' . $key), $this->_isset($this->_data, $key, true));
                }
            }
        }

        $url = admin_url('options-general.php');
        $helperText = sprintf($this->__->l('If left empty, email settings from the <a href=\'%s\'>General options</a> will be used'), $url);
        $this->addFormHelper(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX . 'from_email', '<div class="float-left">' . $helperText . '</div>');

        $helperText = $this->__->l('Tooltips, like the one you are reading right now, are not shown, when you enable this option');
        $this->addFormHelper(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX . 'disable_tooltips', '<div class="float-left">' . $helperText . '</div>');

        $helperText = $this->__->l('If you are using paid events, then you need to make sure that the currency ISO code is correct. Examples can be USD, EUR, JPY....');
        $this->addFormHelper(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX . 'currency_iso', '<div class="float-left">' . $helperText . '</div>');


        $helperText = $this->__->l('When registration is disabled Calendar can be used just for showing your schedule');
        $this->addFormHelper(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX . 'disable_registration', '<div class="float-left">' . $helperText . '</div>');

        $helperText = $this->__->l('When disabled, information icon, which opens the detail view, will not be displayed next to the event title in the event list. <br/>Disabling this may mean that users cannot access detailed view for the event when registration is disabled');
        $this->addFormHelper(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX . 'disable_infoicon', '<div class="float-left">' . $helperText . '</div>');

        $html .= '</tbody>';

        $html .= '</table>';

        //submit button
        $html .= '<p class="submit">';
        $html .= '<input type="submit" value="' . $this->__->l('Save changes') . '" class="button-primary" id="submi" name="submi">';
        $html .= '</p>';



        $html .= '</form>';
        $html .= "\r\n";

        //close div.wrap
        $html .= '</div>';
        $html .= "\r\n";
        $html .= $this->collectJs();
        return $html;
    }

    public function setOptionFunctions(array $optionFunctions) {
        $this->_optionFunctions = $optionFunctions;
    }

}
