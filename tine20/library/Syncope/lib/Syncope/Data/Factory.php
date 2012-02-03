<?php

/**
 * Syncope
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
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
    const STORE_EMAIL    = 'Mailbox';
    
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
                $className = Syncope_Registry::get(Syncope_Registry::CALENDAR_DATA_CLASS);
                break;
                
            case self::CLASS_CONTACTS:
                $className = Syncope_Registry::get(Syncope_Registry::CONTACTS_DATA_CLASS);
                break;
                
            case self::STORE_EMAIL:
            case self::CLASS_EMAIL:
                $className = Syncope_Registry::get(Syncope_Registry::EMAIL_DATA_CLASS);
                break;
                
            case self::CLASS_TASKS:
                $className = Syncope_Registry::get(Syncope_Registry::TASKS_DATA_CLASS);
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
}

