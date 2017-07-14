<?php

/**

  Base event handler.
  By Extending this class and implenenting methods such as
 * new_registrant
 * payment_successful
 * accept_registrant
 * reject_registrant

  You can write your own handler which would for example send XML to another ticket server.

  In addition to the methods implemented you need to set integer value to _executionOrder class variable
  and implement isEnabled() method, which should return true, if the handler is active.

  Take a look at the class Eabi_Ipenelo_Calendar_Event_Email to see how it is done.

 */
abstract class Eabi_Ipenelo_Calendar_Event_Abstract {

    protected $_executionOrder;

    /**
     *  Holds an array of Eabi_Ipenelo_Calendar_Event_Abstract instances,
     *  which are ordered by $this->$_executionOrder variable ascending.
     * @var type 
     */
    private static $_instances;

    /**
     *  Sets up an array of self::$instances.
     *  
     *  
     */
    private static function _init() {
        if (self::$_instances == null) {
            self::$_instances = array();

            //try to load from the option
            $tmpEvents = @unserialize(Eabi_Ipenelo_Calendar::get('available_events'));
            if (is_array($tmpEvents)) {
                //sort
                foreach ($tmpEvents as $class => $event) {
                    $instance = Eabi_Ipenelo_Calendar::service()->get($event['import']);

                    if ($instance->isEnabled()) {
                        self::$_instances[$class] = $instance;
                    }
                }
                //sort it
                uasort(self::$_instances, array(__CLASS__, 'sort'));
            } else {
                self::$_instances = array();
            }
        }
    }

    final public function __construct() {
        ;
    }

    /**
     *  Override this method in your defined subclass. 
     *  Should return true, if you want methods, from your defined subclass, to be fired.
     * 
     * @return boolean true, if handler is active or false if not active.
     */
    public function isEnabled() {
        return false;
    }

    /**
     *  Orders self::$_instances by the getOrder() method.
     * @param type $a
     * @param type $b
     * @return int 
     */
    public static function sort($a, $b) {
        if ($a->getOrder() == $b->getOrder()) {
            return 0;
        }
        return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
    }

    /*
     * This method should return positive integer. This function return has an 
     * effect, how the eventhandlers are ordered.
     * 
     */

    final public function getOrder() {
        if ($this->_executionOrder === null || !is_int($this->_executionOrder)) {
            throw new Exception('Eventhandler should have integer type of _executionOrder');
        }
        return $this->_executionOrder;
    }

    public static function execute($eventName, $params) {
        //load the events
        self::_init();
        foreach (self::$_instances as $class => $instance) {
            if (method_exists(&$instance, $eventName)) {
                call_user_func(array(&$instance, $eventName), &$params);
            }
        }
    }

    /**
     *  Scans availableEventHandler by extended classes.
     *  Those classes should be placed in a directory:
     *  <module-install-path>/classes/Events
     *  See the Readme for more information.
     * 
     * Results of the scanning are written to the database.
     * So this specific function itself is called only when the WP Admin users visits the Calendar's configuration page.
     * 
     * On all the other cases available Event Handlers are being read from the Database configuration.
     * @see self::_init();
     * 
     * @return string available eventhandler in a serialized form.
     * @throws Exception 
     */
    public static function detectAvailableEventHandlers() {
        //get directory contents

        $files = array_diff(scandir(plugin_dir_path(__FILE__)), array('.', '..'));
        $eventHandlers = array();

        foreach ($files as $eventToTest) {
            if (!is_dir(plugin_dir_path(__FILE__) . $eventToTest) && $eventToTest != 'Abstract.php'
                    && substr($eventToTest, -4, 4) == '.php') {
                $eventHandler = Eabi_Ipenelo_Calendar::service()->get('events/' . substr($eventToTest, 0, -4));
                $className = get_class($eventHandler);
                if (isset($eventHandlers[$className])) {
                    throw new Exception('ClassName clash for the EventHandlers: ' . $className);
                }

                //code, classname, default title
                $eventHandlers[$className] = array(
                    'order' => $eventHandler->getOrder(),
                    'enabled' => $eventHandler->isEnabled(),
                    'class' => $className,
                    'import' => 'events/' . substr($eventToTest, 0, -4),
                );
            }
        }
        if (count($eventHandlers) > 0) {
            Eabi_Ipenelo_Calendar::set('available_events', serialize($eventHandlers));
        }
        return $eventHandlers;
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
    
    protected $_template;
    
    public function setTemplate($template) {
        $this->_template = $template;
    }

}