<?php

/**
 * Syncroton
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

class Syncroton_Data_Factory
{
    const CLASS_CALENDAR = 'Calendar';
    const CLASS_CONTACTS = 'Contacts';
    const CLASS_EMAIL    = 'Email';
    const CLASS_TASKS    = 'Tasks';
    const STORE_EMAIL    = 'Mailbox';
    const STORE_GAL      = 'GAL';
    
    protected static $_classMap = array();
    
    /**
     * @param unknown_type $_class
     * @param Syncroton_Model_IDevice $_device
     * @param DateTime $_timeStamp
     * @throws InvalidArgumentException
     * @return Syncroton_Data_IData
     */
    public static function factory($_classFactory, Syncroton_Model_IDevice $_device, DateTime $_timeStamp)
    {
        switch($_classFactory) {
            case self::CLASS_CALENDAR:
                $className = Syncroton_Registry::get(Syncroton_Registry::CALENDAR_DATA_CLASS);
                break;
                
            case self::CLASS_CONTACTS:
                $className = Syncroton_Registry::get(Syncroton_Registry::CONTACTS_DATA_CLASS);
                break;
                
            case self::STORE_EMAIL:
            case self::CLASS_EMAIL:
                $className = Syncroton_Registry::get(Syncroton_Registry::EMAIL_DATA_CLASS);
                break;
                
            case self::CLASS_TASKS:
                $className = Syncroton_Registry::get(Syncroton_Registry::TASKS_DATA_CLASS);
                break;

            case self::STORE_GAL:
                $className = Syncroton_Registry::get(Syncroton_Registry::GAL_DATA_CLASS);
                break;

            default:
                throw new Syncroton_Exception_UnexpectedValue('invalid class type provided');
                breeak;
        }
        
        $class = new $className($_device, $_timeStamp);
        
        if (! $class instanceof Syncroton_Data_IData) {
            throw new RuntimeException('class must be instanceof Syncroton_Data_IData');
        }
                    
        return $class;
    }
}

