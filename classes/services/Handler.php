<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2

 */

/**
 * Should load the following services
 * Payment service
 * Event Service (sends automatic emails)
 * Database service
 * Template service
 * Message service
 * Translator service
 * Manage status service
 * Security service
 *
 * @author matishalmann
 */
class Eabi_Ipenelo_Calendar_Service_Handler {
    private static $_usedPaths = array();
    private $_class = 'Eabi_Ipenelo_Calendar';
    private $_configuration;
    private $_classPath;
    
    public function __construct($class, $configuration, $classPath) {
        $this->_class = $class;
        $this->_classPath = $classPath;
        
        if (is_string($configuration)) {
            $this->_configuration = $this->get($configuration)->toArray();
            
        } else if (is_array($configuration)) {
            $this->_configuration = $configuration;
        } else {
            throw new Exception('Configuration should be path string or array');
        }
    }
    
    private static $_loadedSingletons = array();
    
    public function get($path) {
        $saveAsSingleton = false;
        $item = null;
        if (isset($this->_configuration[$path])) {
            //handle the configuration
            $item = $this->_configuration[$path];
            
            $path = $item['class'];
            
            
            if (isset($item['singleton']) && $item['singleton'] == true) {
                if (isset(self::$_loadedSingletons[$path])) {
                    return self::$_loadedSingletons[$path];
                }
                $saveAsSingleton = true;
            }
        }

        $tmpArgs = func_get_args();
        $arguments = array();
        $arguments[] = $path;
        foreach ($tmpArgs as $i => $tmpArg) {
            if ($i > 0) {
                $arguments[] = $tmpArg;
            }
        }

        $result = call_user_func_array(array(&$this, '_get'), $arguments);

        
            //set the deps.....
        if ($item != null && isset($item['deps']) && is_array($item['deps'])) {
            foreach ($item['deps'] as $name => $dep) {
                $tmpVar = &$this->get($name);
                
                if (isset($dep['method'])) {
                    $method = $dep['method'];
                    $result->$method($tmpVar);
                } else if (isset($dep['var'])) {
                    $method = $dep['var'];
                    $result->$method = $tmpVar;
                }
            }
        }

        if ($saveAsSingleton) {
            self::$_loadedSingletons[$path] = &$result;
        }
        return $result;
        
    }
    
    public function getStatic($path, $method) {
        if (!isset(self::$_usedPaths[$path])) {
            $this->import($path);
            $splitClasses = explode('/', $path);
            /* @var $splitClasses string */
            $className = $this->_class . '_' . substr(ucfirst($splitClasses[0]), 0, -1) . '_' . ucfirst($splitClasses[1]);
            self::$_usedPaths[$path] = $className;
            
        } else {
            //class has been imported
            $className = self::$_usedPaths[$path];
            
        }
        $arguments = array();

        $tmpArgs = func_get_args();
        foreach ($tmpArgs as $i => $tmpArg) {
            if ($i > 1) {
                $arguments[] = $tmpArg;
            }
        }
        return call_user_func_array(array($className, $method), $arguments);
        
    }
    
    private function _get($path) {
        $className = null;
        if (!isset(self::$_usedPaths[$path])) {
            $this->import($path);
            $splitClasses = explode('/', $path);
            /* @var $splitClasses string */
            $className = $this->_class . '_' . substr(ucfirst($splitClasses[0]), 0, -1) . '_' . ucfirst($splitClasses[1]);
            self::$_usedPaths[$path] = $className;
        } else {
            //class has been imported
            $className = self::$_usedPaths[$path];
        }
        //return the new instance
        //get the arguments
        $arguments = array();

        $tmpArgs = func_get_args();
        foreach ($tmpArgs as $i => $tmpArg) {
            if ($i > 0) {
                $arguments[] = $tmpArg;
            }
        }

        $reflectionMethod = new ReflectionMethod($className, '__construct');
        $params = $reflectionMethod->getParameters();

        $constructorArgs = array();
        foreach ($params as $key => $param) {
            if ($param->isPassedByReference()) {
                $constructorArgs[$key] = &$arguments[$key];
            } else {
                $constructorArgs[$key] = $arguments[$key];
            }
        }

        //get the class constructor with arguments.
        $reflectionClass = new ReflectionClass($className);
        $result = $reflectionClass->newInstanceArgs($constructorArgs);
        return $result;
    }

    public function import($path) {
        $splitClasses = explode('/', $path);
        if (count($splitClasses) !== 2) {
            throw new Exception('invalid import type');
        }
        if (!class_exists($this->_class . '_' . ucfirst($splitClasses[0]) . '_' . ucfirst($splitClasses[1]))) {
            require_once(plugin_dir_path($this->_classPath) . 'classes/' . $path . '.php');
        }
    }
    
    
}

