<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Factory.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 */

/**
 * phone backend factory class
 * 
 * @package     Phone
 */
class Phone_Backend_Factory
{
    /**
     * constant for the asterisk backend class
     *
     */
    const ASTERISK = 'Asterisk';

    /**
     * constant for the snom phone callhistory backend class
     *
     */
    const CALLHISTORY = 'Callhistory';
    
    /**
     * factory function to return a selected phone backend class
     *
     * @param   string $type
     * @return  Phone_Backend_Interface
     * @throws  Phone_Exception_InvalidArgument
     * @throws  Phone_Exception_NotFound
     */
    static public function factory($type)
    {
        switch($type) {
            case self::ASTERISK:
                if(isset(Tinebase_Core::getConfig()->asterisk)) {
                    $asteriskConfig = Tinebase_Core::getConfig()->asterisk;
                    $url = $asteriskConfig->managerurl;
                    $username = $asteriskConfig->managerusername;
                    $password = $asteriskConfig->managerpassword;
                } else {
                    throw new Phone_Exception_NotFound('No settings found for asterisk backend in config.ini.');
                }
                $instance = Phone_Backend_Asterisk::getInstance($url, $username, $password);
                break;
                
            case self::CALLHISTORY:
                $instance = Phone_Backend_Snom_Callhistory::getInstance();
                break;
                
            default:
                throw new Phone_Exception_InvalidArgument('Unsupported phone backend (' . $type . ').');
        }

        return $instance;
    }
}    
