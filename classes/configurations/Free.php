<?php

/**
  Käesoleva loomingu autoriõigused kuuluvad Matis Halmannile ja Aktsiamaailm OÜ-le
  @licence GPL v2

 */

/**
 * Description of Paid
 *
 * @author matishalmann
 */
class Eabi_Ipenelo_Calendar_Configuration_Free {
    //put your code here
    public function __construct() {
        
    }
    
    public function toArray() {
        $array = array(
            'translator' => array(
                'class' => 'helpers/Translator',
                'singleton' => false,
            ),
            'database' => array(
                'class' => 'helpers/Dbn',
                'singleton' => false,
            ),
            'payment' => array(
                'class' => 'services/Nopayment',
                'singleton' => true,
            ),
            'event' => array(
                'class' => 'services/Noevent',
                'singleton' => true,
            ),
            'template' => array(
                'class' => 'templateparsers/Standard',
                'singleton' => true,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'grids/Event' => array(
                'class' => 'grids/Event',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),

            
            'grids/Category' => array(
                'class' => 'grids/Category',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'grids/EventJson' => array(
                'class' => 'grids/EventJson',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                    'template' => array(
                        'method' => 'run',
                    ),
                ),
            ),
            'grids/Registrant' => array(
                'class' => 'grids/Registrant',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'installers/Main' => array(
                'class' => 'installers/Main',
                'singleton' => true,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'forms/Registrant' => array(
                'class' => 'forms/Registrant',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'forms/Category' => array(
                'class' => 'forms/Category',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'forms/Configuration' => array(
                'class' => 'forms/Configuration',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'forms/Event' => array(
                'class' => 'forms/Event',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'forms/Payment' => array(
                'class' => 'forms/Payment',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'forms/PublicRegistrant' => array(
                'class' => 'forms/PublicRegistrant',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            
            'models/Event' => array(
                'class' => 'models/Event',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                ),
            ),
            'models/Category' => array(
                'class' => 'models/Category',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                ),
            ),
            'models/Registrant' => array(
                'class' => 'models/Registrant',
                'singleton' => false,
                'deps' => array(
                    'database' => array(
                        'method' => 'setDb',
                        
                    ),
                ),
            ),
            'models/Configuration' => array(
                'class' => 'models/Configuration',
                'singleton' => true,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'events/Email' => array(
                'class' => 'events/Email',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                    'template' => array(
                        'method' => 'setTemplate',
                    ),
                ),
            ),
            'helpers/Validation' => array(
                'class' => 'helpers/Validation',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                ),
            ),
            'shortcodes/Calendar' => array(
                'class' => 'shortcodes/Calendar',
                'singleton' => false,
                'deps' => array(
                    'translator' => array(
                        'method' => 'setTranslator',
                    ),
                    'template' => array(
                        'method' => 'setTemplate',
                    ),
                ),
            ),

        );
        return $array;
    }
}

