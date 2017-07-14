<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
Eabi_Ipenelo_Calendar::service()->import('forms/Registrant');
Eabi_Ipenelo_Calendar::service()->import('grids/Abstract');

class Eabi_Ipenelo_Calendar_Grid_Registrant extends Eabi_Ipenelo_Calendar_Grid_Abstract {

    protected $_event;

    public function __construct(Eabi_Ipenelo_Calendar_Form_Abstract $formModel = null, $dataModel = null) {
        $formModel = Eabi_Ipenelo_Calendar::service()->get('forms/Registrant', array());
        $dataModel = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');

        $this->_event = Eabi_Ipenelo_Calendar::service()->get('models/Event')->load($_GET['event_id']);


        parent::__construct($formModel, $dataModel);
    }

    public function render() {
        global $plugin_page;

        //todo for event....


        $this->setTitle(
                sprintf($this->__->l('Manage registrants (%s)'), htmlspecialchars($this->_event->title)));

        $this->addFieldOrder('email');
        $this->addFieldOrder('first_name');
        $this->addFieldOrder('last_name');
        $this->addFieldOrder('wp_user_id');
        $this->addFieldOrder('status');
        $this->addFieldOrder('registration_date');
        $this->addFieldOrder('payment_date');


        $this->addOrderBy('email');
        $this->addOrderBy('wp_user_id');
        $this->addOrderBy('first_name');
        $this->addOrderBy('last_name');
        $this->addOrderBy('registration_date');

        $deleteNonce = wp_create_nonce('deleteRegistrant');

        $markPaidNonce = wp_create_nonce('markPaidRegistrant');
        $markAcceptedNonce = wp_create_nonce('markAcceptedRegistrant');
        $markRejectedNonce = wp_create_nonce('markRejectedRegistrant');


        $deleteUrl = admin_url('admin.php?page=' . $plugin_page . '&action=delete&noheader=true&event_id=' . $_GET['event_id'] . '&_wpnonce=' . $deleteNonce . '&ids=%id%');

        $markPaidUrl = admin_url('admin.php?page=' . $plugin_page . '&action=markpaid&noheader=true&event_id=' . $_GET['event_id'] . '&_wpnonce=' . $markPaidNonce . '&ids=%id%');
        $markAcceptedUrl = admin_url('admin.php?page=' . $plugin_page . '&action=markaccepted&noheader=true&event_id=' . $_GET['event_id'] . '&_wpnonce=' . $markAcceptedNonce . '&ids=%id%');
        $markRejectedUrl = admin_url('admin.php?page=' . $plugin_page . '&action=markrejected&noheader=true&event_id=' . $_GET['event_id'] . '&_wpnonce=' . $markRejectedNonce . '&ids=%id%');





        $this->addMassAction('markPaid', $this->__->l('Mark as paid'), $markPaidUrl, $this->__->l('Mark selected items as paid?'));
        $this->addMassAction('markAccepted', $this->__->l('Mark as accepted'), $markAcceptedUrl, $this->__->l('Mark selected items as accepted?'));
        $this->addMassAction('markRejected', $this->__->l('Mark as rejected'), $markRejectedUrl, $this->__->l('Mark selected items as rejected?'));

        $this->addMassAction('deleteAll', $this->__->l('Delete'), $deleteUrl, $this->__->l('About to delete, are you sure?'));

        $this->addQuickFilter('status', Eabi_Ipenelo_Calendar_Model_Registrant::toStatusArray());

        $this->addAlwaysOnUrlParam('event_id', $_GET['event_id']);

        $this->addWherePart('event_id', "main_table.event_id = " . (int) $_GET['event_id']);

        $this->addButton('addNew', $this->__->l('Add new registrant'), admin_url('admin.php?page=ipenelo_calendar_edit_registrant&event_id=' . $_GET['event_id']));
        $this->addButton('viewEvent', $this->__->l('View event'), admin_url('admin.php?page=ipenelo_calendar_new&id=' . $_GET['event_id']));
        $this->addButton('toEventList', $this->__->l('Back to event list'), admin_url('admin.php?page=ipenelo_calendar'));

        $this->setEditLink('admin.php?page=ipenelo_calendar_edit_registrant&amp;id=%id%');


        $this->addAction('edit', $this->__->l('Edit'), 'admin.php?page=ipenelo_calendar_edit_registrant&amp;id=%id%');

        $this->addAction('asPaid', $this->__->l('Mark as paid'), 'admin.php?page=' . $plugin_page . '&amp;noheader=true&amp;_wpnonce=' . $markPaidNonce . '&amp;action=markpaid&amp;ids=%id%&amp;event_id=' . $_GET['event_id']);
        $this->addAction('asAccepted', $this->__->l('Mark as accepted'), 'admin.php?page=' . $plugin_page . '&amp;noheader=true&amp;_wpnonce=' . $markAcceptedNonce . '&amp;action=markaccepted&amp;ids=%id%&amp;event_id=' . $_GET['event_id']);
        $this->addAction('asRejected', $this->__->l('Mark as rejected'), 'admin.php?page=' . $plugin_page . '&amp;noheader=true&amp;_wpnonce=' . $markRejectedNonce . '&amp;action=markrejected&amp;ids=%id%&amp;event_id=' . $_GET['event_id']);

        $this->addAction('trash', $this->__->l('Delete'), 'admin.php?page=' . $plugin_page . '&amp;noheader=true&amp;_wpnonce=' . $deleteNonce . '&amp;action=delete&amp;ids=%id%&amp;event_id=' . $_GET['event_id']);


        $html = $this->_render();
        return $html;
    }

}