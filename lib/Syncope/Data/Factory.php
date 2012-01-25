<?php

/**
 * Syncope
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL),
 *              Version 1, the distribution of the Tine 2.0 Syncope module in or to the
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncope_Data_Factory
{
    const CLASS_CALENDAR = 'Calendar';
    const CLASS_CONTACTS = 'Contacts';
    const CLASS_EMAIL    = 'Email';
    const CLASS_TASKS    = 'Tasks';
    
    protected static $_classMap = array();
    
    /**
     * @param unknown_type $_class
     * @param Syncope_Model_IDevice $_device
     * @param DateTime $_timeStamp
     * @throws InvalidArgumentException
     * @return Syncope_Data_IData
     */
    public static function factory($_classFactory, Syncope_Model_IDevice $_device, DateTime $_timeStamp)
    {
        switch($_classFactory) {
            case self::CLASS_CALENDAR:
                $className = isset(self::$_classMap[$_classFactory]) ? self::$_classMap[$_classFactory] : 'Syncope_Data_Calendar';
                break;
                
            case self::CLASS_CONTACTS:
                $className = isset(self::$_classMap[$_classFactory]) ? self::$_classMap[$_classFactory] : 'Syncope_Data_Contacts';
                break;
                
            case self::CLASS_EMAIL:
                $className = isset(self::$_classMap[$_classFactory]) ? self::$_classMap[$_classFactory] : 'Syncope_Data_Email';
                break;
                
            case self::CLASS_TASKS:
                $className = isset(self::$_classMap[$_classFactory]) ? self::$_classMap[$_classFactory] : 'Syncope_Data_Tasks';
                break;
                
            default:
                throw new InvalidArgumentException('invalid class type provided');
                breeak;
        }
        
        $class = new $className($_device, $_timeStamp);
        
        if (! $class instanceof Syncope_Data_IData) {
            throw new RuntimeException('class must be instanceof Syncope_Data_IData');
        }
                    
        return $class;
    }
    
    public static function registerClass($_classFactory, $_className)
    {
        if (!class_exists($_className)) {
            throw new InvalidArgumentException('invalid $_className provided');
        }
        self::$_classMap[$_classFactory] = $_className;
    }
}

