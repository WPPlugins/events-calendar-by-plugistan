<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */

/**
  Emulates single database table row for a series of configuration values.

  All the configuration values, this module creates, start with self::OPTION_PREFIX constant.
 */
class Eabi_Ipenelo_Calendar_Model_Configuration {

    const OPTION_PREFIX = 'eabi_ipenelo_calendar_';

    public $allowedOptions = array();
    private static $_jsDateFormats = array(
        '1' => 'dd.mm.yyyy',
        '2' => 'mm/dd/yyyy',
        '3' => 'yyyy-mm-dd',
    );
    private static $_phpDateFormats = array(
        '1' => 'd.m.Y',
        '2' => 'm/d/Y',
        '3' => 'Y-m-d',
    );
    protected $_optionFunctions = array();

    public function __construct() {
        $this->allowedOptions = array(
            self::OPTION_PREFIX . 'disable_registration',
            self::OPTION_PREFIX . 'show_in_admin_bar',
            self::OPTION_PREFIX . 'date_format',
            self::OPTION_PREFIX . 'enable_12h',
            self::OPTION_PREFIX . 'default_category',
            self::OPTION_PREFIX . 'image_thumb_height',
            self::OPTION_PREFIX . 'image_thumb_width',
            self::OPTION_PREFIX . 'image_thumb_crop',
            self::OPTION_PREFIX . 'image_normal_height',
            self::OPTION_PREFIX . 'image_normal_width',
            self::OPTION_PREFIX . 'image_normal_crop',
            self::OPTION_PREFIX . 'tb_height',
            self::OPTION_PREFIX . 'tb_width',
            self::OPTION_PREFIX . 'currency_num_decimals',
            self::OPTION_PREFIX . 'currency_decimal_separator',
            self::OPTION_PREFIX . 'currency_thousands_separator',
            self::OPTION_PREFIX . 'currency_symbol',
            self::OPTION_PREFIX . 'currency_symbol_position',
            self::OPTION_PREFIX . 'currency_iso',
            self::OPTION_PREFIX . 'log_to_register',
            self::OPTION_PREFIX . 'log_to_view',
            self::OPTION_PREFIX . 'show_free_spots',
            self::OPTION_PREFIX . 'calendar_size',
            self::OPTION_PREFIX . 'event_lasts',
            self::OPTION_PREFIX . 'registration_lasts',
            self::OPTION_PREFIX . 'visible_from',
            self::OPTION_PREFIX . 'visible_to',
            self::OPTION_PREFIX . 'payment_success',
            self::OPTION_PREFIX . 'payment_cancel',
            self::OPTION_PREFIX . 'theme',
            self::OPTION_PREFIX . 'color_calendar',
            self::OPTION_PREFIX . 'disable_tooltips',
            self::OPTION_PREFIX . 'disable_infoicon',
            self::OPTION_PREFIX . 'from_email',
            self::OPTION_PREFIX . 'from_name',
        );
        if (true) {
            $this->allowedOptions[] = self::OPTION_PREFIX.'show_link';
        }

    }
    
    
    private function _init() {
        $pages = array();
        $dbPages = Eabi_Ipenelo_Calendar::service()->get('database')->get_results("select ID, post_title from " . Eabi_Ipenelo_Calendar::service()->get('database')->prefix . "posts where post_type = 'page' order by post_title, post_name ", OBJECT_K);
        foreach ($dbPages as $dbPage) {
            $pages[$dbPage->ID] = $dbPage->post_title . ' (ID:' . $dbPage->ID . ')';
        }

        $minutes = array(
            '0' => sprintf($this->__->l('%s minutes'), '0'),
            '1' => sprintf($this->__->l('%s minute'), '1'),
        );
        for ($i = 2; $i < 60; $i++) {
            $minutes[(string) $i] = sprintf($this->__->l('%s minutes'), (string) $i);
        }
        for ($i = 0; $i < 60; $i+=5) {
            $minutes[(string) (60 + $i)] = sprintf($this->__->l('%s hour and %s minutes'), '1', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=10) {
            $minutes[(string) (120 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '2', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=15) {
            $minutes[(string) (180 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '3', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=15) {
            $minutes[(string) (240 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '4', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=15) {
            $minutes[(string) (300 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '5', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=15) {
            $minutes[(string) (360 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '6', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=15) {
            $minutes[(string) (420 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '7', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=30) {
            $minutes[(string) (480 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '8', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=30) {
            $minutes[(string) (540 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '9', (string) $i);
        }
        for ($i = 0; $i < 60; $i+=30) {
            $minutes[(string) (600 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '10', (string) $i);
        }


        $eventminutes = array(
            '0' => sprintf($this->__->l('%s minutes'), '0') . ' ' . $this->__->l('before the event starts'),
            '1' => sprintf($this->__->l('%s minute'), '1') . ' ' . $this->__->l('before the event starts'),
        );
        for ($i = 2; $i < 60; $i++) {
            $eventminutes[(string) $i] = sprintf($this->__->l('%s minutes'), (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=5) {
            $eventminutes[(string) (60 + $i)] = sprintf($this->__->l('%s hour and %s minutes'), '1', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=10) {
            $eventminutes[(string) (120 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '2', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=15) {
            $eventminutes[(string) (180 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '3', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=15) {
            $eventminutes[(string) (240 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '4', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=15) {
            $eventminutes[(string) (300 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '5', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=15) {
            $eventminutes[(string) (360 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '6', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=15) {
            $eventminutes[(string) (420 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '7', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=30) {
            $eventminutes[(string) (480 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '8', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=30) {
            $eventminutes[(string) (540 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '9', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 0; $i < 60; $i+=30) {
            $eventminutes[(string) (600 + $i)] = sprintf($this->__->l('%s hours and %s minutes'), '10', (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 1; $i <= 1; $i+=1) {
            $eventminutes[(string) (1440 * $i)] = sprintf($this->__->l('%s day'), (string) $i) . ' ' . $this->__->l('before the event starts');
        }
        for ($i = 2; $i < 60; $i+=1) {
            $eventminutes[(string) (1440 * $i)] = sprintf($this->__->l('%s days'), (string) $i) . ' ' . $this->__->l('before the event starts');
        }

        for ($i = 1; $i >= 1; $i--) {
            $eventminutes[(string) ($i * -1)] = sprintf($this->__->l('%s minute'), (string) $i) . ' ' . $this->__->l('after the event starts');
        }
        for ($i = 2; $i < 60; $i++) {
            $eventminutes[(string) ($i * -1)] = sprintf($this->__->l('%s minutes'), (string) ($i * 1)) . ' ' . $this->__->l('after the event starts');
        }
        for ($i = 0; $i < 60; $i+=5) {
            $eventminutes[(string) ((60 + $i) * -1)] = sprintf($this->__->l('%s hour and %s minutes'), '1', (string) ($i * 1)) . ' ' . $this->__->l('after the event starts');
        }
        for ($i = 0; $i < 60; $i+=10) {
            $eventminutes[(string) ((120 + $i) * -1)] = sprintf($this->__->l('%s hours and %s minutes'), '2', (string) ($i * 1)) . ' ' . $this->__->l('after the event starts');
        }
        for ($i = 0; $i < 60; $i+=15) {
            $eventminutes[(string) ((180 + $i) * -1)] = sprintf($this->__->l('%s hours and %s minutes'), '3', (string) ($i * 1)) . ' ' . $this->__->l('after the event starts');
        }
        for ($i = 0; $i < 60; $i+=30) {
            $eventminutes[(string) ((240 + $i) * -1)] = sprintf($this->__->l('%s hours and %s minutes'), '4', (string) ($i * 1)) . ' ' . $this->__->l('after the event starts');
        }

        $visibleFromMinutes = array('startoftime' => $this->__->l('from the moment inserted')) + $minutes;
        $visibleToMinutes = array('endoftime' => $this->__->l('forever')) + $minutes;

        foreach ($visibleFromMinutes as $k => $v) {
            if (is_numeric($k)) {
                $visibleFromMinutes[$k] = $v . ' ' . $this->__->l('before the event starts');
            }
        }

        foreach ($visibleToMinutes as $k => $v) {
            if (is_numeric($k)) {
                $visibleToMinutes[$k] = $v . ' ' . $this->__->l('after the event ends');
            }
        }

        for ($i = 1; $i < 180; $i+=5) {
            if ($i == 1) {
                $visibleFromMinutes[(string) ((1440 * $i) * 1)] = sprintf($this->__->l('%s day'), (string) ($i * 1)) . ' ' . $this->__->l('before the event starts');
            } else {
                $visibleFromMinutes[(string) ((1440 * $i) * 1)] = sprintf($this->__->l('%s days'), (string) ($i * 1)) . ' ' . $this->__->l('before the event starts');
            }
        }
        for ($i = 1; $i < 180; $i+=5) {
            if ($i == 1) {
                $visibleToMinutes[(string) ((1440 * $i) * 1)] = sprintf($this->__->l('%s day'), (string) ($i * 1)) . ' ' . $this->__->l('after the event ends');
            } else {
                $visibleToMinutes[(string) ((1440 * $i) * 1)] = sprintf($this->__->l('%s days'), (string) ($i * 1)) . ' ' . $this->__->l('after the event ends');
            }
        }

        $themes = array();

        $dir = Eabi_Ipenelo_Calendar::path() . '/themes';
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir($dir . '/' . $file)) {
                $themes[$file] = $file;
            }
        }
        Eabi_Ipenelo_Calendar::service()->import('models/Event');


        $this->_optionFunctions = array(
            self::OPTION_PREFIX . 'date_format' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '1' => $this->__->l('dd.mm.yyyy'),
                    '2' => $this->__->l('mm/dd/yyyy'),
                    '3' => $this->__->l('yyyy-mm-dd'),
                ),
            ),
            self::OPTION_PREFIX . 'image_thumb_height' => array(
                'function' => 'addNumberField',
            ),
            self::OPTION_PREFIX . 'image_thumb_width' => array(
                'function' => 'addNumberField',
            ),
            self::OPTION_PREFIX . 'image_thumb_crop' => array(
                'function' => 'addCheckboxField',
            ),
            self::OPTION_PREFIX . 'image_normal_height' => array(
                'function' => 'addNumberField',
            ),
            self::OPTION_PREFIX . 'image_normal_width' => array(
                'function' => 'addNumberField',
            ),
            self::OPTION_PREFIX . 'image_normal_crop' => array(
                'function' => 'addCheckboxField',
            ),
            self::OPTION_PREFIX . 'currency_decimal_separator' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '.' => $this->__->l('Dot (.)'),
                    ',' => $this->__->l('Comma (,)'),
                ),
            ),
            self::OPTION_PREFIX . 'currency_thousands_separator' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '' => $this->__->l('None'),
                    ' ' => $this->__->l('Space ( )'),
                    '\'' => $this->__->l('Apostrophe (\')'),
                ),
            ),
            self::OPTION_PREFIX . 'currency_symbol_position' => array(
                'function' => 'addSelectField',
                'values' => array(
                    'left' => $this->__->l('To the left from the price'),
                    'right' => $this->__->l('To the right from the price'),
                ),
            ),
            self::OPTION_PREFIX . 'log_to_register' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'log_to_view' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'show_free_spots' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'calendar_size' => array(
                'function' => 'addSelectField',
                'values' => array(
                    'calroot' => $this->__->l('Medium'),
                    'calrootsmall' => $this->__->l('Small'),
                    'calrootlarge' => $this->__->l('Large'),
                ),
            ),
            self::OPTION_PREFIX . 'event_lasts' => array(
                'function' => 'addSelectField',
                'values' => $minutes,
            ),
            self::OPTION_PREFIX . 'registration_lasts' => array(
                'function' => 'addSelectField',
                'values' => $eventminutes,
            ),
            self::OPTION_PREFIX . 'visible_from' => array(
                'function' => 'addSelectField',
                'values' => $visibleFromMinutes,
            ),
            self::OPTION_PREFIX . 'visible_to' => array(
                'function' => 'addSelectField',
                'values' => $visibleToMinutes,
            ),
            self::OPTION_PREFIX . 'payment_success' => array(
                'function' => 'addSelectField',
                'values' => $pages,
            ),
            self::OPTION_PREFIX . 'payment_cancel' => array(
                'function' => 'addSelectField',
                'values' => $pages,
            ),
            self::OPTION_PREFIX . 'theme' => array(
                'function' => 'addSelectField',
                'values' => $themes,
            ),
            self::OPTION_PREFIX . 'color_calendar' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'disable_tooltips' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'default_category' => array(
                'function' => 'addSelectField',
                'values' => Eabi_Ipenelo_Calendar_Model_Event::getCategories(),
            ),
            self::OPTION_PREFIX . 'enable_12h' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'show_in_admin_bar' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'disable_registration' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'disable_infoicon' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
            self::OPTION_PREFIX . 'show_link' => array(
                'function' => 'addSelectField',
                'values' => array(
                    '0' => $this->__->l('No'),
                    '1' => $this->__->l('Yes'),
                ),
            ),
        );
        
    }
    private $_inited = false;

    /**
      Loads the configuration from the database.

     */
    public function load($id = 0) {
        if (!$this->_inited) {
            $this->_init();
            $this->_inited = true;
        }
        foreach ($this->allowedOptions as $option) {
            $this->$option = get_option($option);
        }
        return $this;
    }

    public function get($option, $default = false) {
        return get_option(self::OPTION_PREFIX . $option, $default);
    }

    public function set($option, $value) {
        return update_option(self::OPTION_PREFIX . $option, $value);
    }

    /**
      Returns the configuration manipulation options.
      Enables different types of input for the configuration fields.
      Such as checkbox and datetime are possible.
     */
    public function getOptionFunctions() {
        return $this->_optionFunctions;
    }

    /**
      Returns the current date format saved in the configuration.
      Returns date format, which can be supplied to the PHP-s date() function
      or jquery tools dateinput module.
      @param $js - if true, returns js date format.
      @return date format string

     */
    public static function getDateFormat($js = false) {
        $key = self::OPTION_PREFIX . 'date_format';
        $val = get_option($key);
        if (!$val) {
            $val = '1';
        }
        if ($js) {
            return self::$_jsDateFormats[$val];
        }
        return self::$_phpDateFormats[$val];
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