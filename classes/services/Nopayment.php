<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2

 */
Eabi_Ipenelo_Calendar::service()->import('models/Registrant');

/**
 * Description of Haspayment
 *
 * @author matishalmann
 */
class Eabi_Ipenelo_Calendar_Service_Nopayment {
    //put your code here
    public function __construct() {
        
    }
    
    public function detectAvailablePaymentMethods() {
        Eabi_Ipenelo_Calendar::set('available_payments', serialize(array()));
        return '';
    }
    
    public function getByCode($code, Eabi_Ipenelo_Calendar_Model_Registrant $registrant) {
        return false;
    }
    
    public function renderSelect($paymentMethods) {
        $str = 'If you would like to have more than 10 events, payment methods for your events, automatic emails after registration, then consider buying <a href="%s" target="_blank">Premium version of this Calendar.</a>';
        $sti = 'http://www.e-abi.ee/wordpress-calendar-and-scheduler-plugin.html/';
        return '<br/><br/>'.sprintf(Eabi_Ipenelo_Calendar::service()->get('translator')->l(($str)), ($sti));
    }
}

