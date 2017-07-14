<?php

class Eabi_Ipenelo_Calendar_Helper_Translator {

    private static $_enableDump = false;
    private static $_loadedTranslations;
    
    public function __construct() {
        self::_init();
    }
    
    private static $_inited;
    final public static function _init() {
        if (self::$_inited === null) {
           load_plugin_textdomain('ipenelo_calendar', false, dirname(plugin_basename(Eabi_Ipenelo_Calendar::path())) . '/languages');
           self::$_inited = true;
        }
    }
    
    public function l($var, $domain = 'ipenelo_calendar'){
        return self::translate($var, $domain);
    }

    public static function translate($var, $domain = 'ipenelo_calendar') {
        if ($domain == 'ipenelo_calendar' && self::$_enableDump) {
            if (self::$_loadedTranslations === null) {
                self::$_loadedTranslations = array();
            }
            if (isset(self::$_loadedTranslations[$var]) && __($var, $domain) == self::$_loadedTranslations[$var]) {
                return self::$_loadedTranslations[$var];
            } else {
                self::$_loadedTranslations[$var] = __($var, $domain);
                return self::$_loadedTranslations[$var];
            }
        }
        return __($var, $domain);
    }

    public static function getLoaded() {
        return self::$_loadedTranslations;
    }

    public static function load() {
        if (!self::$_enableDump) {
            return;
        }
        if (self::$_loadedTranslations === null) {
            self::$_loadedTranslations = array();
        }
        $lang = WPLANG;
        if ($lang == '') {
            $lang = 'en';
        }
        $file = plugin_dir_path(__FILE__) . '../../languages/' . $lang . '.txt';
        if (file_exists($file)) {
            $tmp = unserialize(file_get_contents($file));
            if (!is_array($tmp)) {
                
            } else {
                foreach ($tmp as $k => $v) {
                    if (!isset(self::$_loadedTranslations[$k])) {
                        self::$_loadedTranslations[$k] = $v;
                    }
                }
            }
        }
    }

    public static function save() {
        if (!self::$_enableDump) {
            return;
        }
        if (self::$_loadedTranslations != null) {
            $lang = WPLANG;
            if ($lang == '') {
                $lang = 'en';
            }

            foreach (self::$_loadedTranslations as $k => $v) {
                self::translate($k);
            }
            $file = plugin_dir_path(__FILE__) . '../../languages/' . $lang . '.txt';
            file_put_contents($file, serialize(self::$_loadedTranslations));
            $file = plugin_dir_path(__FILE__) . '../../languages/' . $lang . '.po';
            $fp = fopen($file, 'w');
            $str = '
msgid ""
msgstr ""
"Project-Id-Version: Ipenelo Calendar\n"
"POT-Creation-Date: \n"
"PO-Revision-Date: \n"
"Last-Translator: Matis Halmann <info@e-abi.ee>\n"
"Language-Team: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=utf-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Poedit-Language: Estonian\n"

# LANGUAGE (EN) translation for Ipenelo Calendar plugin.
# Copyright (C) 2012 Eabi Ipenelo.
# This file is distributed under the same license as the WordPress package.
# Matis Halmann <info@e-abi.ee>, 2012.
#

';
            fwrite($fp, $str);

            foreach (self::$_loadedTranslations as $key => $translation) {
                fwrite($fp, 'msgid "' . str_replace('\\\'', '\'', addslashes($key)) . '"');
                fwrite($fp, "\n");
                fwrite($fp, 'msgstr "' . str_replace('\\\'', '\'', addslashes($translation)) . '"');
                fwrite($fp, "\n");
            }
            fclose($fp);

            //do the english also
            $file = plugin_dir_path(__FILE__) . '../../languages/en.po';
            $fp = fopen($file, 'w');
            $str = '
msgid ""
msgstr ""
"Project-Id-Version: Ipenelo Calendar\n"
"POT-Creation-Date: \n"
"PO-Revision-Date: \n"
"Last-Translator: Matis Halmann <info@e-abi.ee>\n"
"Language-Team: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=utf-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Poedit-Language: English\n"

# LANGUAGE (EN) translation for Ipenelo Calendar plugin.
# Copyright (C) 2012 Eabi Ipenelo.
# This file is distributed under the same license as the WordPress package.
# Matis Halmann <info@e-abi.ee>, 2012.
#

';
            fwrite($fp, $str);

            foreach (self::$_loadedTranslations as $key => $translation) {
                fwrite($fp, 'msgid "' . str_replace('\\\'', '\'', addslashes($key)) . '"');
                fwrite($fp, "\n");
                fwrite($fp, 'msgstr "' . str_replace('\\\'', '\'', addslashes($key)) . '"');
                fwrite($fp, "\n");
            }
            fclose($fp);
        }
    }

}