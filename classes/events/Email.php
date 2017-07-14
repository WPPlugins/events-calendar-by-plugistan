<?php

/**
  Event handler which responds to the Eabi_Ipenelo_Calendar_Event_Abstract::execute() calls
  This class responds to various events by sending out an email.
  Currently it implements events associated with registration for the event.
  It sends out an email when:
 * New registrant is added
 * Payment was successful
 * Registrant was accepted for the event
 * Registrant was rejected from the event.


 */
Eabi_Ipenelo_Calendar::service()->import('events/Abstract');

class Eabi_Ipenelo_Calendar_Event_Email extends Eabi_Ipenelo_Calendar_Event_Abstract {

    /**

      Since emails should be sent before any other action is taken, then this handler is set to be as the very first handler.

     */
    protected $_executionOrder = -1;

    /**
      Make this field to return false, to disable calling events within this class.

     */
    public function isEnabled() {
        return true;
    }

    public static function getFromEmail() {
        $email = Eabi_Ipenelo_Calendar::get('from_email');
        if ($email == '') {
            $email = get_option('admin_email');
        }
        return $email;
    }

    public static function getFromName() {
        $name = Eabi_Ipenelo_Calendar::get('from_name');
        if ($name == '') {
            $name = get_option('blogname');
        }
        return $name;
    }

    /**

      This event is called, when new registrant is added and the initial registration was successful.
      This event does not indicate that the registrant would be fully accepted or the registrant has fully paid.

      @param $params
      array(
      'registrant' => assoc array of registrant entry,
      'event' => assoc array of event entry
      );


     */
    public function new_registrant($params) {
        $d = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $params['event']['active_from']);
        
        $params['event']['active_from_friendly'] = $d->format(Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat') . ' H:i');

        $subject = $this->__->l('Registration successful');
        $to = $params['registrant']['email'];
        $headers = array(
            'Content-type: text/html',
        );
        $html = $this->_template->parse('emails/new-registrant.phtml', $params);
        wp_mail($to, $subject, $html, $headers);
    }

    /**
      This event is called when registrant has successfully completed the payment.
      It does not indicate that the registrant should be accepted for the event.
      @param $params
      array(
      'registrant' => assoc array of registrant entry,
      'event' => assoc array of event entry
      );


     */
    public function payment_successful($params) {
        $d = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $params['event']['active_from']);
        $params['event']['active_from_friendly'] = $d->format(Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat') . ' H:i');

        $subject = $this->__->l('Payment successful');
        $to = $params['registrant']['email'];
        $headers = array(
            'Content-type: text/html',
        );
        $html = $this->_template->parse('emails/payment-successful.phtml', $params);
        wp_mail($to, $subject, $html, $headers);
    }

    /**
      This event is called when registrant is fully accepted for the event.

      @param $params
      array(
      'registrant' => assoc array of registrant entry,
      'event' => assoc array of event entry
      );


     */
    public function accept_registrant($params) {
        $d = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $params['event']['active_from']);
        $params['event']['active_from_friendly'] = $d->format(Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat') . ' H:i');

        $subject = $this->__->l('Registration accepted');
        $to = $params['registrant']['email'];
        $headers = array(
            'Content-type: text/html',
        );
        $html = $this->_template->parse('emails/accept-registrant.phtml', $params);
        wp_mail($to, $subject, $html, $headers);
    }

    /**

      This function is called when the registrant is rejected from the event.

      @param $params
      array(
      'registrant' => assoc array of registrant entry,
      'event' => assoc array of event entry
      );


     */
    public function reject_registrant($params) {
        $d = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $params['event']['active_from']);
        $params['event']['active_from_friendly'] = $d->format(Eabi_Ipenelo_Calendar::service()->getStatic('models/Configuration', 'getDateFormat') . ' H:i');

        $subject = $this->__->l('Registration rejected');
        $to = $params['registrant']['email'];
        $headers = array(
            'Content-type: text/html',
        );
        $html = $this->_template->parse('emails/reject-registrant.phtml', $params);
        wp_mail($to, $subject, $html, $headers);
    }

}