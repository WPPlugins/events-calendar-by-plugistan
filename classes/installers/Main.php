<?php
/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2

 */

class Eabi_Ipenelo_Calendar_Installer_Main {

    const VERSION = '0.1.3';
    protected $_db;

    protected $_versions = array(
        '0.1.0',
        '0.1.1',
    );

    public function __construct() {
        
    }
    public function setDb($db) {
        $this->_db = &$db;
    }

    public function install() {
        $oldVersion = Eabi_Ipenelo_Calendar::get('version_number', 0);
        $errors = array();

        foreach ($this->_versions as $version) {
            if (version_compare($version, $oldVersion) > 0) {
                require_once(dirname(__FILE__) . '/mysql-install-' . $version . '.php');
                if (count($errors) == 0) {
                    Eabi_Ipenelo_Calendar::set('version_number', $version);
                }
                $errors = array();
            }
        }
        if (count($errors) == 0 && version_compare(self::VERSION, $oldVersion) > 0) {
            Eabi_Ipenelo_Calendar::set('version_number', self::VERSION);
            Eabi_Ipenelo_Calendar::addMessage(sprintf($this->__->l('Updated to version %s'), Eabi_Ipenelo_Calendar_Installer_Main::VERSION));
        } else if (count($errors) > 0) {
            Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('There was an error with upgrading to version %s'), Eabi_Ipenelo_Calendar_Installer_Main::VERSION));
        }
    }


    
    /**
     *  Translator interface
     * 
     * 
     * @var Eabi_Ipenelo_Calendar_Helper_Translator 
     */
    protected $__;
    
    /**
     *
     * @param Eabi_Ipenelo_Calendar_Helper_Translator $translator 
     */
    public function setTranslator($translator) {
        $this->__ = $translator;
    }
    
}

?>