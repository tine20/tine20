<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Notification factory class
 * 
 * this class is responsible for returning the right notification backend
 *
 * @package     Tinebase
 * @subpackage  Notification
 */
class Tinebase_Notification_Factory
{
    /**
     * smtp backend type
     *
     * @staticvar string
     */
    const SMTP = 'Smtp';
    
    /**
     * return a instance of the current accounts backend
     *
     * @return  Tinebase_Notification_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function getBackend($_backendType) 
    {
        switch($_backendType) {
            case self::SMTP:
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get SMTP notifiaction backend.');
                $result = new Tinebase_Notification_Backend_Smtp();
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument("Notification backend type $_backendType not implemented");
        }
        
        return $result;
    }
}