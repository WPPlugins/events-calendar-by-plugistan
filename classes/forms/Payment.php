<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Abstract');

class Eabi_Ipenelo_Calendar_Form_Payment extends Eabi_Ipenelo_Calendar_Form_Abstract {

    const __CLASS = __CLASS__;

    protected $_formName = 'ipenelo-calendar-payment';

    public function render() {

        $html = '';
        global $plugin_page;

        if (!$this->_renderOnlyCore) {
            $html .= '<div class="wrap">';
            $html .= "\r\n";

            //title icon
            $html .= '<div class="icon32" id="icon-options-general"><br></div>';
            $html .= "\r\n";

            //title itself
            if ($this->_isset($this->_data, 'title')) {
                $html .= '<h2>' . $this->__->l('Edit payment method') . ':' . $this->_isset($this->_data, 'title', true) . '</h2>';
            } else {
                $html .= '<h2>' . $this->__->l('New payment method') . '</h2>';
            }
            $html .= "\r\n";
            $html .= Eabi_Ipenelo_Calendar::displayErrors();
            $html .= Eabi_Ipenelo_Calendar::displayMessages();
            //form
            $html .= '<form action="' . admin_url('admin.php?noheader=true&page=' . $plugin_page . '&code=' . $_GET['code']) . '" method="post" id="' . $this->_formName . '">';
            $html .= "\r\n";

            $html .= '<table class="form-table">';

            $html .= '<tbody>';
        }


        $html .= $this->_render();


        if (!$this->_renderOnlyCore) {

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
        }
        $html .= $this->collectJs();
        return $html;
    }

}