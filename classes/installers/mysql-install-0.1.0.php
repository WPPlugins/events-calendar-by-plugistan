<?php

$registrant = Eabi_Ipenelo_Calendar::service()->get('models/Registrant');
$entry = Eabi_Ipenelo_Calendar::service()->get('models/Event');
$category = Eabi_Ipenelo_Calendar::service()->get('models/Category');

$registrantTable = $registrant->getTableName();
$entryTable = $entry->getTableName();
$categoryTable = $category->getTableName();

$sqls = array();

$sqls[] = <<<EOT

DROP TABLE IF EXISTS `{$categoryTable}`;
EOT;


$sqls[] = <<<EOT
CREATE TABLE IF NOT EXISTS `{$categoryTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `background` varchar(255) NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sort_order` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
EOT;



$sqls[] = <<<EOT

INSERT INTO `{$categoryTable}` (`id`, `name`, `description`, `background`, `is_active`, `sort_order`) VALUES
(1, 'default category', 'default description', '#ffffff', 1, 1);
EOT;

$sqls[] = <<<EOT
DROP TABLE IF EXISTS `{$entryTable}`;
EOT;

$sqls[] = <<<EOT
CREATE TABLE IF NOT EXISTS `{$entryTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `url` varchar(255) DEFAULT NULL,
  `active_from` datetime DEFAULT NULL,
  `active_to` datetime DEFAULT NULL,
  `is_full_day` tinyint(1) NOT NULL DEFAULT '0',
  `last_registration_allowed` datetime DEFAULT NULL,
  `is_paid_event` tinyint(1) NOT NULL DEFAULT '0',
  `cost` decimal(14,4) DEFAULT NULL,
  `visible_from` datetime DEFAULT NULL,
  `visible_to` datetime DEFAULT NULL,
  `status` int(11) NOT NULL,
  `main_category_id` int(11) NOT NULL,
  `background` varchar(255) DEFAULT NULL,
  `max_registrants` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
EOT;

$sqls[] = <<<EOT
DROP TABLE IF EXISTS `{$registrantTable}`;
EOT;

$sqls[] = <<<EOT
CREATE TABLE IF NOT EXISTS `{$registrantTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wp_user_id` int(11) unsigned DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `user_comment` text,
  `status` int(11) unsigned NOT NULL,
  `event_id` int(11) unsigned NOT NULL,
  `extra_data` text,
  `cache_data` text NOT NULL,
  `registration_date` datetime NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_data` text,
  `order_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

EOT;

$errors = array();
foreach ($sqls as $sql) {
    $this->_db->query($sql);
    if ($this->_db->last_error != '') {
        $errors[] = htmlspecialchars($this->_db->last_error);
    }
}

Eabi_Ipenelo_Calendar::set('date_format', '1');
Eabi_Ipenelo_Calendar::set('image_thumb_height', '32');
Eabi_Ipenelo_Calendar::set('image_thumb_width', '32');
Eabi_Ipenelo_Calendar::set('image_normal_height', '150');
Eabi_Ipenelo_Calendar::set('image_normal_width', '150');
Eabi_Ipenelo_Calendar::set('image_thumb_crop', '1');
Eabi_Ipenelo_Calendar::set('tb_height', '600');
Eabi_Ipenelo_Calendar::set('tb_width', '1024');

if (count($errors) == 0) {
    Eabi_Ipenelo_Calendar::addMessage(sprintf($this->__->l('Updated to version %s'), Eabi_Ipenelo_Calendar_Installer_Main::VERSION));
} else {
    Eabi_Ipenelo_Calendar::addError(sprintf($this->__->l('There was an error with upgrading to version %s'), Eabi_Ipenelo_Calendar_Installer_Main::VERSION));
    foreach ($errors as $error) {
        Eabi_Ipenelo_Calendar::addError($error);
        
    }
}



unset($sqls);