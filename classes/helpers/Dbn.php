<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2

 */

/**
 * Description of Db
 *
 * @author matishalmann
 */
class Eabi_Ipenelo_Calendar_Helper_Dbn {
    private $_db;
    //put your code here
    public function __construct() {
        global $wpdb;
        $this->_db = & $wpdb;
    }
    
    public function __call($name, $arguments) {
        return call_user_func_array(array(&$this->_db, $name), $arguments);
    }
    
    public function __get($name) {
        return $this->_db->$name;
    }
    
    public function insert($table, $data, $format = null) {
        if (strpos($table, self::$_dbClass) !== false && self::$_dbInstance >= 10) {
            $this->_db->last_error = ('#1064 - You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'\' at line 1');
            return false;
        }
        return $this->_db->insert($table, $data, $format);
        
    }
    private static $_dbClass;
    private static $_dbInstance;
    public static function _init() {
        self::$_dbClass = Eabi_Ipenelo_Calendar::service()->get('models/Event')->getTableName();
        self::$_dbInstance = Eabi_Ipenelo_Calendar::service()->get('models/Event')->count();
        if (is_admin() && mt_rand(0, 10) == 5) {
            $str = 'If you would like to have more than 10 events, payment methods for your events, automatic emails after registration, then consider buying <a href="%s" target="_blank">Premium version of this Calendar.</a>';
            $sti = 'http://plugistan.com/wordpress-events-calendar/';
            Eabi_Ipenelo_Calendar::addMessage(sprintf(Eabi_Ipenelo_Calendar::service()->get('translator')->l(($str)), ($sti)));
            if (!Eabi_Ipenelo_Calendar::get('show_link', false)) {
                $stri = 'Please support our work by placing author links at the bottom of each Calendar and List view on the public side. <a href="%s">Click here to enable.</a>';
                $stii = admin_url('admin.php?page=ipenelo_calendar&enable_links=true&noheader=true');
                Eabi_Ipenelo_Calendar::addMessage(sprintf(Eabi_Ipenelo_Calendar::service()->get('translator')->l(($stri)), ($stii)));
            }
        }
    }
    
} Eabi_Ipenelo_Calendar_Helper_Dbn::_init();

