<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2
  @version 0.1.0

 */
class Eabi_Ipenelo_Calendar_Helper_Validation {

    public function __construct() {
        ;
    }

    public function required($value) {
        if ($value == '')
            return $this->__->l('Required entry');
        return true;
    }

    private static function escJs($var) {
        return addslashes(htmlspecialchars($var));
    }

    public function js_required() {
        $text = self::escJs($this->__->l('Required entry'));
        return <<<EOT
 var ret = !(v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    public function js_required_cost() {
        $text = self::escJs($this->__->l('Required entry'));
        return <<<EOT
if (v) { v = v.replace(',', '.'); };
if (v != '') {
	v = parseFloat(v);
}

 var ret = !(v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    public function email($value) {
        if ($value == '') {
            return true;
        }
        $return = true;
        // Split email address up and disallow '..'
        if ((strpos($value, '..') !== false) or
                (!preg_match('/^(.+)@([^@]+)$/', $value, $matches))) {
            $return = false;
        }

        if ((strlen($matches[1]) > 64) || (strlen($matches[2]) > 255)) {
            $return = false;
        }

        //todo hostname validation
        // Dot-atom characters are: 1*atext *("." 1*atext)
        // atext: ALPHA / DIGIT / and "!", "#", "$", "%", "&", "'", "*",
        //        "+", "-", "/", "=", "?", "^", "_", "`", "{", "|", "}", "~"
        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
        if ($return && preg_match('/^[' . $atext . ']+(\x2e+[' . $atext . ']+)*$/', $matches[1])) {
            
        } else {
            // Try quoted string format
            // Quoted-string characters are: DQUOTE *([FWS] qtext/quoted-pair) [FWS] DQUOTE
            // qtext: Non white space controls, and the rest of the US-ASCII characters not
            //   including "\" or the quote character
            $noWsCtl = '\x01-\x08\x0b\x0c\x0e-\x1f\x7f';
            $qtext = $noWsCtl . '\x21\x23-\x5b\x5d-\x7e';
            $ws = '\x20\x09';
            if ($return && preg_match('/^\x22([' . $ws . $qtext . '])*[$ws]?\x22$/', $matches[1])) {
                
            } else {
                $return = false;
            }
        }

        if ($return) {
            return true;
        } else {
            return $this->__->l('Invalid email');
        }
    }

    public function js_email() {
        $text = self::escJs($this->__->l('Invalid email'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)) || /^([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/i.test(v); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    /**
     * Callback function to validate if the value is valid css HEX color or valid url
     * @param string $value value to validate
     * @return boolean 
     */
    public function colorOrUrl($value) {
        if ($value == '') {
            return true;
        }
        if (strlen($value) == 7) {
            //assume it is hex
            if (substr($value, 0, 1) == '#' &&
                    preg_match('/[0-9a-f]/i', substr($value, 1))) {
                return true;
            }
        } else if (strlen($value) > 0) {
            //assume it is image url
            if ($this->__checkUrl($value)) {
                return true;
            }
        }
        return $this->__->l('Invalid color or image URL');
    }

    public function js_colorOrUrl() {
        $text = self::escJs($this->__->l('Invalid color or image URL'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)) || (v.length == 7 && /#[0-9a-f]+/i.test(v)) || /^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i.test(v); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    public function url($value) {
        if ($value == '') {
            return true;
        }
        if ($this->__checkUrl($value)) {
            return true;
        }
        return $this->__->l('Invalid URL');
    }

    public function js_url() {
        $text = self::escJs($this->__->l('Invalid URL'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)) || /^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i.test(v); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    public function currency($value) {
        if ($value == '') {
            return true;
        }
        $value = str_replace(',', '', $value);
        if ((float) $value >= 0)
            return true;
        return $this->__->l('Invalid price');
    }

    public function js_currency() {
        $text = self::escJs($this->__->l('Invalid price'));
        return <<<EOT
if (v) { v = v.replace(',', '.'); };
if (v != '') {
	v = parseFloat(v);
}
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)) || (!isNaN(v) && v >= 0); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    public function posInteger($value) {
        if ($value == '' || $value == 0) {
            return true;
        }
        if ((int) $value > 0)
            return true;
        return $this->__->l('Invalid amount');
    }

    public function js_posInteger() {
        $text = self::escJs($this->__->l('Invalid amount'));
        return <<<EOT
if (v != '') {
	v = parseInt(v, 10);
}
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)) || (!isNaN(v) && v > 0); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    public function date($value) {
        if ($value == '') {
            return true;
        }
        $format = 'Y-m-d H:i:s';
        $d = Eabi_Ipenelo_Calendar::createDateFromFormat($format, $value);
        if ($d instanceof DateTime) {
            return true;
        }
        return $this->__->l('Invalid date');
    }

    public function js_date() {
        $text = $this->__->l('Invalid date');
        Eabi_Ipenelo_Calendar::service()->import('models/Configuration');
        $format = get_option(Eabi_Ipenelo_Calendar_Model_Configuration::OPTION_PREFIX . 'date_format');
        if (!$format) {
            $format = '1';
        }
        $str = '';
        switch ($format) {
            case '1':
                $str = <<<EOT

	els = v.split('.');
	if (els.length == 3 && !passed) {
		d = new Date(parseInt(els[2], 10), parseInt(els[1], 10) -1, parseInt(els[0], 10));
		passed = true;
	}

EOT;
                break;
            case '2':
                $str = <<<EOT

	els = v.split('/');
	if (els.length == 3 && !passed) {
		d = new Date(parseInt(els[2], 10), parseInt(els[0], 10) -1, parseInt(els[1], 10));
		passed = true;
	}

EOT;
                break;
            case '3':
                $str = <<<EOT

	els = v.split('-');
	if (els.length == 3) {
		d = new Date(parseInt(els[0], 10), parseInt(els[1], 10) -1, parseInt(els[2], 10));
		passed = true;
	}

EOT;
                break;
        }


        return <<<EOT
var els = [], d = false, passed = false;
if (v) {
	{$str}
}
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)) || (passed && typeof(d) == 'object'); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    public function js_time() {
        $text = self::escJs($this->__->l('Invalid time'));
        return <<<EOT
 var ret = (v == '' || (v == null) || (v.length == 0) || /^\s+$/.test(v)) || /[0-2][0-9]:[0-5][0-9]/.test(v); 
 if (!ret) {
 	return '{$text}';
 }
 return ret;
EOT;
    }

    private function __checkUrl($url) {

        $url = @parse_url($url);
        if (!$url) {
            return false;
        }

        $url = array_map('trim', $url);
        $url['port'] = (!isset($url['port'])) ? 80 : (int) $url['port'];

        $path = (isset($url['path'])) ? $url['path'] : '/';
        $path .= (isset($url['query'])) ? "?$url[query]" : '';

        if (isset($url['host']) && $url['host'] != gethostbyname($url['host'])) {

            $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);

            if (!$fp) {
                return false;
            }

            fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n"); //socket opened
            $headers = fread($fp, 4096);
            fclose($fp);

            if (preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers)) {//matching header
                return true;
            } else {
                return false;
            }
        } else {
            return false;
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