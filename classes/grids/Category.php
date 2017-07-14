<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
Eabi_Ipenelo_Calendar::service()->import('models/Category');
Eabi_Ipenelo_Calendar::service()->import('forms/Category');
Eabi_Ipenelo_Calendar::service()->import('grids/Abstract');

class Eabi_Ipenelo_Calendar_Grid_Category extends Eabi_Ipenelo_Calendar_Grid_Abstract {

    public function __construct(Eabi_Ipenelo_Calendar_Form_Abstract $formModel = null, $dataModel = null) {
        $formModel = Eabi_Ipenelo_Calendar::service()->get('forms/Category', array());
        $dataModel = Eabi_Ipenelo_Calendar::service()->get('models/Category');
        parent::__construct($formModel, $dataModel);
    }

    public function render() {
        global $plugin_page;

        //todo for event....

        $this->setTitle($this->__->l('Manage categories'));

        $this->addFieldOrder('name');
        $this->addFieldOrder('is_active');
        $this->addFieldOrder('background');
        $this->addFieldOrder('sort_order');

        $this->restrictFieldOutput('description');

        $this->addOrderBy('name');
        $this->addOrderBy('is_active');
        $this->addOrderBy('sort_order');

        $deleteNonce = wp_create_nonce('deleteCategory');

        $deleteUrl = admin_url('admin.php?page=' . $plugin_page . '&noheader=true&action=delete&_wpnonce=' . $deleteNonce . '&ids=%id%');



        $this->addMassAction('deleteAll', $this->__->l('Delete'), $deleteUrl, $this->__->l('About to delete, are you sure?'));


        $this->addButton('addNew', $this->__->l('Add new category'), admin_url('admin.php?page=ipenelo_calendar_edit_category'));


        $this->setEditLink('admin.php?page=ipenelo_calendar_edit_category&amp;id=%id%');

        $this->addAction('edit', $this->__->l('Edit'), 'admin.php?page=ipenelo_calendar_edit_category&amp;id=%id%');
        $this->addAction('trash', $this->__->l('Delete'), 'admin.php?page=' . $plugin_page . '&amp;noheader=true&amp;_wpnonce=' . $deleteNonce . '&amp;action=delete&amp;ids=%id%&amp;event_id=' . $_GET['event_id']);


        $html = $this->_render();
        return $html;
    }

}