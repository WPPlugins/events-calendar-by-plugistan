<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */

/**
  Emulates single self::TABLE_NAME database table entry.

 */
class Eabi_Ipenelo_Calendar_Model_Category {

    const TABLE_NAME = 'eabi_ipenelo_calendar_category';

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

    public function getTableName() {
        if ($this->tableName) {
            return $this->tableName;
        }
        return false;
    }

}