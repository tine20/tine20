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
    const CALENDAR = 'Calendar';
    const CONTACTS = 'Contacts';
    const EMAIL    = 'Email';
    const TASKS    = 'Tasks';
    
    /**
     * @param unknown_type $_class
     * @param Syncope_Model_IDevice $_device
     * @param DateTime $_timeStamp
     * @throws InvalidArgumentException
     * @return Syncope_Data_IData
     */
    public static function factory($_class, Syncope_Model_IDevice $_device, DateTime $_timeStamp)
    {
        switch($_class) {
            case self::CALENDAR:
                $class = new Syncope_Data_Calendar($_device, $_timeStamp);
                break;
                
            case self::CONTACTS:
                $class = new Syncope_Data_Contacts($_device, $_timeStamp);
                break;
                
            case self::EMAIL:
                $class = new Syncope_Data_Email($_device, $_timeStamp);
                break;
                
            case self::TASKS:
                $class = new Syncope_Data_Tasks($_device, $_timeStamp);
                break;
                
            default:
                throw new InvalidArgumentException('invalid class name provided');
                breeak;
        }
            
        return $class;
    }
}

