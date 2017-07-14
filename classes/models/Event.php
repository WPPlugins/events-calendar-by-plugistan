<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */

/**
  Emulates single self::TABLE_NAME database table entry.

 */
class Eabi_Ipenelo_Calendar_Model_Event {

    const TABLE_NAME = 'eabi_ipenelo_calendar_entry';
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;
    const STATUS_SOLD_OUT = 3;
    const STATUS_ENDED = 4;

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
    
    public function count() {
        return $this->_db->get_var("select count(id) from `" . $this->tableName . "`");
        
    }

    public function exists($id) {
        $id = (int) $id;
        $result = $this->_db->get_var("select count(*) from `" . $this->tableName . "` where id = " . $id);
        return $result > 0;
    }

    public function existsPublic($id) {
        $id = (int) $id;
        $result = $this->_db->get_var("select count(*) from `" . $this->tableName . "` where id = " . $id . " and visible_from <= NOW() and visible_to >= NOW() ");
        return $result > 0;
    }

    public function getTableName() {
        if ($this->tableName) {
            return $this->tableName;
        }
        return false;
    }

    /**

      Possible entry statuses.

     */
    public static function toStatusArray() {
        return array(
            (string) self::STATUS_ENABLED => self::__l('Enabled'),
            (string) self::STATUS_DISABLED => self::__l('Disabled'),
            (string) self::STATUS_SOLD_OUT => self::__l('Sold out'),
            (string) self::STATUS_ENDED => self::__l('Ended'),
        );
    }

    public static function publicStatuses() {
        return array(
            (string) self::STATUS_ENABLED => self::__l('Enabled'),
            (string) self::STATUS_SOLD_OUT => self::__l('Sold out'),
            (string) self::STATUS_ENDED => self::__l('Ended'),
        );
    }

    protected static $_categories;

    public static function getCategories() {
        if (self::$_categories == null) {
            $db = Eabi_Ipenelo_Calendar::service()->get('database');
            $model = Eabi_Ipenelo_Calendar::service()->get('models/Category');
            $results = $db->get_results("select id, name from " . $model->getTableName() . " order by sort_order asc", OBJECT_K);
            self::$_categories = array();
            foreach ($results as $result) {
                self::$_categories[$result->id] = $result->name;
            }
        }
        return self::$_categories;
    }

    protected static $_users;

    public static function getUsers() {
        if (self::$_users == null) {
            $db = Eabi_Ipenelo_Calendar::service()->get('database');
            $users = $db->get_results(
                    $db->prepare(
                            "SELECT ".$db->users.".ID, ".$db->users.".user_nicename FROM ".$db->users." ORDER BY %s ASC"
                            , 'user_nicename'
                    )
            );
            $suppliedUsers = array();
            foreach ($users as $user) {
                $suppliedUsers[$user->ID] = $user->user_nicename;
            }
            self::$_users = $suppliedUsers;
        }
        return self::$_users;
    }

    private static $_registrantLoaded;

    public function isOver($data = array()) {
        $isOver = false;

        if (count($data) > 0 && isset($data['id']) || (isset($this->id) && $this->id > 0)) {
            if (!isset($data['active_to']) && isset($data['id'])) {
                $this->load($data['id']);
            }
            $id = (int) isset($data['id']) ? $data['id'] : $this->id;
            $lastRegistrationAllowed = isset($data['last_registration_allowed']) ? $data['last_registration_allowed'] : $this->last_registration_allowed;
            $activeTo = isset($data['active_to']) ? $data['active_to'] : $this->active_to;
            if ($lastRegistrationAllowed == '' || $lastRegistrationAllowed == '0000-00-00 00:00:00') {
                $lastRegistrationAllowed = $activeTo;
            }
            $currentTime = current_time('timestamp');
            $lastTime = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $lastRegistrationAllowed);
            $activeTime = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $activeTo);
            if ($currentTime >= (int) $lastTime->format('U')) {
                return true;
            }
            if ($currentTime >= (int) $activeTime->format('U')) {
                return true;
            }
            return $isOver;
        } else {
            throw new Exception('Event ID is required to determine is the registration is over or not');
        }
    }

    public function isStarted($data = array()) {
        $isOver = false;

        if (count($data) > 0 && isset($data['id']) || (isset($this->id) && $this->id > 0)) {
            if (!isset($data['active_from']) && isset($data['id'])) {
                $this->load($data['id']);
            }
            $id = (int) isset($data['id']) ? $data['id'] : $this->id;
            $activeFrom = isset($data['active_from']) ? $data['active_from'] : $this->active_from;
            $currentTime = current_time('timestamp');
            $activeTime = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $activeFrom);
            if ($currentTime >= (int) $activeTime->format('U')) {
                return true;
            }
            return $isOver;
        } else {
            throw new Exception('Event ID is required to determine is the registration is over or not');
        }
    }

    public function isEnded($data = array()) {
        $isOver = false;

        if (count($data) > 0 && isset($data['id']) || (isset($this->id) && $this->id > 0)) {
            if (!isset($data['active_to']) && isset($data['id'])) {
                $this->load($data['id']);
            }
            $id = (int) isset($data['id']) ? $data['id'] : $this->id;
            $activeTo = isset($data['active_to']) ? $data['active_to'] : $this->active_from;
            $currentTime = current_time('timestamp');
            $activeTime = Eabi_Ipenelo_Calendar::createDateFromFormat('Y-m-d H:i:s', $activeTo);
            if ($currentTime >= (int) $activeTime->format('U')) {
                return true;
            }
            return $isOver;
        } else {
            throw new Exception('Event ID is required to determine is the registration is over or not');
        }
    }

    public function getFreeSpots($data = array()) {

        //defaults to unlimited free spots
        $freeSpots = false;
        if (count($data) > 0 && isset($data['id']) || (isset($this->id) && $this->id > 0)) {
            if (!isset($data['max_registrants']) && isset($data['id'])) {
                $this->load($data['id']);
            }
            $id = (int) isset($data['id']) ? $data['id'] : $this->id;
            $maxSpots = (int) isset($data['max_registrants']) ? $data['max_registrants'] : $this->max_registrants;
            if ($maxSpots > 0) {
                if (self::$_registrantLoaded == null) {
                    self::$_registrantLoaded = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
                }
                $query = "select count(id) from " . self::$_registrantLoaded->getTableName() . " where event_id = " . $id . " and status in (" . implode(',', Eabi_Ipenelo_Calendar_Model_Registrant::allowedStatuses()) . ")";
                $freeSpots = $maxSpots - ((int) $this->_db->get_var($query));
                if ($freeSpots <= 0) {
                    $freeSpots = 0;
                }
            }
            return $freeSpots;
        } else {
            throw new Exception('Event ID is required to calculate free spots');
        }
        return $freeSpots;
    }

    private static $_statusesApplied;

    public static function applyStatuses() {
        if (self::$_statusesApplied === null) {
            $db = Eabi_Ipenelo_Calendar::service()->get('database');
            $currentTime = date('Y-m-d H:i:s', current_time('timestamp'));

            $modelInstance = Eabi_Ipenelo_Calendar::service()->get('models/Event');

            $query = "update " . $modelInstance->getTableName() . " set status = " . self::STATUS_ENDED . " where active_to < '" . $currentTime . "' and status <> " . self::STATUS_DISABLED;
            $db->query($query);
            self::$_statusesApplied = true;
        }
    }
    
    private static function __l($var) {
        if (self::$_translator === null) {
            self::$_translator = Eabi_Ipenelo_Calendar::service()->get('translator');
        }
        return self::$_translator->l($var);
    }
    
    private static $_translator;

}