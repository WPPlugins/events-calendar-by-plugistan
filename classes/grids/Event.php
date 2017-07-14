<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
Eabi_Ipenelo_Calendar::service()->import('models/Event');
Eabi_Ipenelo_Calendar::service()->import('forms/Event');
Eabi_Ipenelo_Calendar::service()->import('grids/Abstract');
Eabi_Ipenelo_Calendar::service()->import('models/Registrant');

class Eabi_Ipenelo_Calendar_Grid_Event extends Eabi_Ipenelo_Calendar_Grid_Abstract {

    protected $_registeredCounts = array();

    public function __construct(Eabi_Ipenelo_Calendar_Form_Abstract $formModel = null, $dataModel = null) {
        $formModel = Eabi_Ipenelo_Calendar::service()->get('forms/Event', array());
        $dataModel = Eabi_Ipenelo_Calendar::service()->get('models/Event');
        parent::__construct($formModel, $dataModel);
    }

    protected function _afterFormInstance(Eabi_Ipenelo_Calendar_Form_Abstract &$form) {
        if ($this->_registeredCounts == null && $this->_results != null) {

            /**
             *  @var Eabi_Ipenelo_Calendar_Model_Registrant 
             */
            $registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
            $query = "select count(id) as value, event_id as name from " . $registrant->getTableName() . " where event_id in (" . implode(',', array_keys($this->_results)) . ") group by event_id";
            $this->_registeredCounts = Eabi_Ipenelo_Calendar::convert($this->_db->get_results($query, ARRAY_A));
        }
        $value = 0;
        $model = $form->getModel();
        if (isset($model['id']) && isset($this->_registeredCounts[$model['id']])) {
            $value = $this->_registeredCounts[$model['id']];
        }
        if (isset($model['id'])) {

            $url = admin_url('admin.php?page=ipenelo_calendar_view_registrants&amp;event_id=' . $model['id']);
            $title = $this->__->l('View registrants');
            $form->setFormElementHtml('<td><a title="' . $title . '" class="post-com-count ipenelo-calendar-number" href="' . $url . '">${INPUT}</a></td>' . "\n\r", 'users_signed');
        }
        $form->addTextField('users_signed', $this->__->l('Users registered'), $value);
    }

    public function render() {
        global $plugin_page;

        $this->setTitle($this->__->l('Manage Events'));

        $this->addFieldOrder('title');
        $this->addFieldOrder('active_from');
        $this->addFieldOrder('active_to');
        $this->addFieldOrder('users_signed');
        $this->addFieldOrder('max_registrants');
        $this->addFieldOrder('last_registration_allowed');

        $this->restrictFieldOutput('description');
        $this->restrictFieldOutput('url');
        $this->restrictFieldOutput('visible_from');
        $this->restrictFieldOutput('visible_to');
        $this->restrictFieldOutput('is_paid_event');
        $this->restrictFieldOutput('url_click_title');

        $this->addOrderBy('title');
        $this->addOrderBy('active_from');
        $this->addOrderBy('active_to');

        $deleteNonce = wp_create_nonce('deleteEvent');
        $updateStatusNonce = wp_create_nonce('updateStatus');

        $deleteUrl = admin_url('admin.php?page=' . $plugin_page . '&action=delete&noheader=true&_wpnonce=' . $deleteNonce . '&ids=%id%');
        $updateStatusUrl = admin_url('admin.php?page=' . $plugin_page . '&action=updateStatus&noheader=true&_wpnonce=' . $updateStatusNonce . '&ids=%id%');

        $updateStatusHtml = '<select id="massActionExtra" name="status">';
        $updateStatusHtml .= '<option value="">' . $this->__->l('Select status') . '</option>';
        
        

        foreach (Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'toStatusArray') as $status_id => $status_name) {
            $updateStatusHtml .= '<option value="' . $status_id . '">' . $status_name . '</option>';
        }

        $updateStatusHtml .= '</select>';


        $this->addMassAction('deleteAll', $this->__->l('Delete'), $deleteUrl, $this->__->l('About to delete, are you sure?'));
        $this->addMassAction('updateStatus', $this->__->l('Update status'), $updateStatusUrl, $this->__->l('Update status for selected items?'), $updateStatusHtml);

        $this->addQuickFilter('status', Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'toStatusArray'));
        $this->addQuickFilter('main_category_id', Eabi_Ipenelo_Calendar::service()->getStatic('models/Event', 'getCategories'));

        $this->addButton('addNew', $this->__->l('Add new event'), admin_url('admin.php?page=ipenelo_calendar_new'));

        $this->setEditLink('admin.php?page=ipenelo_calendar_new&amp;id=%id%');



        $this->addAction('edit', $this->__->l('Edit'), 'admin.php?page=ipenelo_calendar_new&amp;id=%id%');
        $this->addAction('inline', $this->__->l('View registrants'), 'admin.php?page=ipenelo_calendar_view_registrants&amp;event_id=%id%');
        $this->addAction('trash', $this->__->l('Delete'), 'admin.php?page=' . $plugin_page . '&amp;noheader=true&amp;_wpnonce=' . $deleteNonce . '&amp;action=delete&amp;ids=%id%');

        $html = $this->_render();
        return $html;
    }
    

}