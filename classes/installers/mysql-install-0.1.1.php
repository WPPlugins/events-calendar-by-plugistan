<?php

$entry = Eabi_Ipenelo_Calendar::service()->get('models/Event');

$entryTable = $entry->getTableName();

$sqls = array();

$sqls[] = <<<EOT

ALTER TABLE `{$entryTable}` ADD `url_click_title` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `url` ;

EOT;



$errors = array();
foreach ($sqls as $sql) {
    $this->_db->query($sql);
    if ($this->_db->last_error != '') {
        $errors[] = htmlspecialchars($this->_db->last_error);
    }
}




Eabi_Ipenelo_Calendar::set('default_category', '1');

Eabi_Ipenelo_Calendar::set('event_lasts', '180');

Eabi_Ipenelo_Calendar::set('theme', 'simple');


if (count($errors) == 0) {
    Eabi_Ipenelo_Calendar::addMessage(sprintf($this->__->l('Updated to version %s'), Eabi_Ipenelo_Calendar_Installer_Main::VERSION));
} else {
    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('There was an error with upgrading to version %s'), Eabi_Ipenelo_Calendar_Installer_Main::VERSION));
    foreach ($errors as $error) {
        Eabi_Ipenelo_Calendar::addError($error);
        
    }
}


unset($sqls);