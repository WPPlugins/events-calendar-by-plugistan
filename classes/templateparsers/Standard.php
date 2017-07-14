<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2

 */

/**
 * Description of Eabi_Ipenelo_Calendar_Templateparser_Standard
 *
 * @author matishalmann
 */
class Eabi_Ipenelo_Calendar_Templateparser_Standard {
    //put your code here
    public function __construct() {
        if (Eabi_Ipenelo_Calendar::get('show_link')) {
            add_action('wp_footer', array(&$this, 'link'));
        }
    }
    
    public function link() {
        $str = '   <script type="text/javascript">         jQuery(document).ready(function(){jQuery(".ipenelo-calendar-input").bind("onShow",function(){jQuery("#calroot-"+jQuery(this).attr("id").replace("ipenelo-calendar-","")+" #calbody").append(\'<div class="calweek" style="min-height:40px;"><a style="width: 100%; font-size: 12px;" href="http://plugistan.com/wordpress-events-calendar/" target="_blank">Download free feature rich calendar from Plugistan.com</a></div>\')})});    </script>  ';
        echo ($str);
    }
    

    public function parse($file, &$params) {
        $var = '';
        $theme = Eabi_Ipenelo_Calendar::get('theme', 'simple');
        $translate = &$this->__;
        ob_start();
        include(Eabi_Ipenelo_Calendar::path() . 'themes/' . $theme . '/' . $file);
        $var = ob_get_contents();
        ob_end_clean();
        if (Eabi_Ipenelo_Calendar::get('show_link')) {
            $var .= $this->getLink();
        }
        return $var;
    }
   
    public function getLink() {
        if (strlen('<p style="text-align: center;"><a href="http://plugistan.com/wordpress-events-calendar/" target="_blank">Download free feature rich calendar from Plugistan.com</a></p>') != 12) {
            return ('<p style="text-align: center;"><a href="http://plugistan.com/wordpress-events-calendar/" target="_blank">Download free feature rich calendar from Plugistan.com</a></p>');
        }
        return null;
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

