<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
/**
  Displays the Admin side Category edit form.

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Abstract');

class Eabi_Ipenelo_Calendar_Form_Category extends Eabi_Ipenelo_Calendar_Form_Abstract {

    const __CLASS = __CLASS__;

    protected $_formName = 'ipenelo-calendar-category';

    public function render() {
        global $plugin_page;
        $validation = Eabi_Ipenelo_Calendar::service()->get('helpers/Validation');
        $html = '';

        if (!$this->_renderOnlyCore) {
            $html .= '<div class="wrap">';
            $html .= "\r\n";

            //title icon
            $html .= '<div class="icon32" id="icon-options-general"><br></div>';
            $html .= "\r\n";

            //title itself
            if ($this->_isset($this->_data, 'name')) {
                $html .= '<h2>' . $this->__->l('Edit category') . ':' . $this->_isset($this->_data, 'name', true) . '</h2>';
            } else {
                $html .= '<h2>' . $this->__->l('New Category') . '</h2>';
            }
            $html .= "\r\n";
            $html .= Eabi_Ipenelo_Calendar::displayErrors();
            $html .= Eabi_Ipenelo_Calendar::displayMessages();

            //form
            $html .= '<form action="' . admin_url('admin.php?noheader=true&page=' . $plugin_page . '&id=' . $this->_isset($this->_data, 'id')) . '" method="post" id="' . $this->_formName . '">';
            $html .= "\r\n";

            $html .= '<table class="form-table">';

            $html .= '<tbody>';
        }


        //name
        $this->addTextField('name', $this->__->l('Name'), $this->_isset($this->_data, 'name', true));
        $this->addValidatorRule('name', array(&$validation, 'required'), $this->__->l('Name is required'), $validation->js_required());
        //end name
        //is_active
        $this->addCheckboxField('is_active', $this->__->l('Is active?'), $this->_isset($this->_data, 'is_active', true));

        //description
        $this->addTextAreaField('description', $this->__->l('Description'), $this->_isset($this->_data, 'description', true));
        //end description
        //end_is_active
        //background
        $this->addValidatorRule('background', array(&$validation, 'required'), $this->__->l('Background is required'), $validation->js_required());
        $this->addValidatorRule('background', array(&$validation, 'colorOrUrl'), $this->__->l('Invalid color or URL'), $validation->js_colorOrUrl());
        $this->addColorField('background', $this->__->l('Background'), $this->_isset($this->_data, 'background', true));


        //end background
        //sort_order
        $this->addValidatorRule('sort_order', array(&$validation, 'required'), $this->__->l('Sort order is required'), $validation->js_required());
        $this->addValidatorRule('sort_order', array(&$validation, 'posInteger'), $this->__->l('Invalid sort order'), $validation->js_posInteger());
        $this->addNumberField('sort_order', $this->__->l('Sort order'), $this->_isset($this->_data, 'sort_order', true));
        //end sort_order



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