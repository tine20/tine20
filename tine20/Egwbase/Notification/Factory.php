<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Notification factory class
 * 
 * this class is responsible for returning the right notification backend
 *
 * @package     Egwbase
 * @subpackage  Notification
 */
class Egwbase_Notification_Factory
{
    const SMTP = 'Smtp';
    
    /**
     * return a instance of the current accounts backend
     *
     * @return Egwbase_Notification_Interface
     */
    public static function getBackend($_backendType) 
    {
        switch($_backendType) {
            case self::SMTP:
                $result = new Egwbase_Notification_Backend_Smtp();
                break;
                
            default:
                throw new Exception("accounts backend type $_backendType not implemented");
        }
        
        return $result;
    }
}