<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */

/**
  Emulates single self::TABLE_NAME database table entry.

 */
class Eabi_Ipenelo_Calendar_Model_Registrant {

    const TABLE_NAME = 'eabi_ipenelo_calendar_registrant';
    const STATUS_PENDING = 1;
    const STATUS_PAYMENT_ACCEPTED = 2;
    const STATUS_ACCEPTED = 3;
    const STATUS_REJECTED = 4;

    protected $tableName;

    protected $_db;
    public function setDb(&$db) {
        $this->_db = &$db;
        $this->tableName = $this->_db->prefix . self::TABLE_NAME;
    }
    
    public function __construct() {
    }

    /**
      Load the entry

     */
    public function load($id) {
        $id = (int) $id;
        $result = $this->_db->get_row("select * from `" . $this->tableName . "` where id = " . $id, ARRAY_A);
        if ($result == null) {
            return false;
        }
        //we have result

        foreach ($result as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    public function exists($id) {
        $id = (int) $id;
        $result = $this->_db->get_var("select count(*) from `" . $this->tableName . "` where id = " . $id);
        return $result > 0;
    }

    public function emailExists($email, $event_id) {
        $email = $this->_db->escape($email);
        $event_id = (int) $event_id;
        if ($event_id <= 0) {
            throw new Exception('Invalid event on email uniqueness validation');
        }
        $result = $this->_db->get_var("select count(*) from `" . $this->tableName . "` where event_id = " . $event_id . " and email ='" . $email . "'");
        return $result > 0;
    }

    public function getTableName() {
        if ($this->tableName) {
            return $this->tableName;
        }
        return false;
    }

    public function getHash() {
        return hexdec(uniqid());
    }

    /**

      Possible entry statuses.

     */
    public static function toStatusArray() {
        return array(
            (string) self::STATUS_PENDING => self::__l('Pending'),
            (string) self::STATUS_PAYMENT_ACCEPTED => self::__l('Payment accepted'),
            (string) self::STATUS_ACCEPTED => self::__l('Accepted'),
            (string) self::STATUS_REJECTED => self::__l('Rejected'),
        );
    }

    public static function allowedStatuses() {
        return array(
            self::STATUS_PENDING,
            self::STATUS_PAYMENT_ACCEPTED,
            self::STATUS_ACCEPTED,
        );
    }

    public static function loadByEmailAndEvent($email, $event_id) {
        $result = null;
        $event_id = (int) $event_id;
        if ($event_id <= 0) {
            return $result;
        }
        $db = Eabi_Ipenelo_Calendar::service()->get('database');
        if (is_user_logged_in() || (isset($_SESSION['ipenelo_calendar_noregister']) && is_array($_SESSION['ipenelo_calendar_noregister'])
                && isset($_SESSION['ipenelo_calendar_noregister'][(string) $event_id]))) {
            $registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
            $query = "select * from " . $registrant->getTableName() . " where event_id = " . $event_id . " and email = '" . $db->escape($email) . "'";
            $result = $db->get_row($query, ARRAY_A);
            if ($result != null) {
                foreach ($result as $key => $value) {
                    $registrant->$key = $value;
                }
                return $registrant;
            }
        }
        return $result;
    }

    public function markAsPaid($ignoreStatus = false) {
        if (isset($this->id)
                && ($this->status == self::STATUS_PENDING || $ignoreStatus)
                && ($this->payment_date == '' || $this->payment_date == '0000-00-00 00:00:00')) {

            $data = array(
                'payment_date' => date('Y-m-d H:i:s', current_time('timestamp')),
                'status' => self::STATUS_PAYMENT_ACCEPTED,
            );
            if ($ignoreStatus) {
                unset($data['status']);
            }
            $this->_db->update($this->getTableName(), $data, array('id' => $this->id));

            $eventModel = Eabi_Ipenelo_Calendar::service()->get('models/Event');
            $eventParams = array(
                'registrant' => (array) $this->load($this->id),
                'event' => (array) $eventModel->load($this->event_id),
            );
            Eabi_Ipenelo_Calendar::service()->get('event')->event('payment_successful', $eventParams);




            return true;
        }
        return false;
    }
    private static function __l($var) {
        if (self::$_translator === null) {
            self::$_translator = Eabi_Ipenelo_Calendar::service()->get('translator');
        }
        return self::$_translator->l($var);
    }
    
    private static $_translator;

}