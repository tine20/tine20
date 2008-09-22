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
     * @param string $type
     * @return Phone_Backend_Interface
     */
    static public function factory($type)
    {
        switch($type) {
            case self::ASTERISK:
                if(isset(Zend_Registry::get('configFile')->asterisk)) {
                    $url = Zend_Registry::get('configFile')->asterisk->managerurl;
                    $username = Zend_Registry::get('configFile')->asterisk->managerusername;
                    $password = Zend_Registry::get('configFile')->asterisk->managerpassword;
                } else {
                    throw new Exception('no settings found for asterisk backend in config.ini');
                }
                $instance = Phone_Backend_Asterisk::getInstance($url, $username, $password);
                break;
                
            case self::CALLHISTORY:
                $instance = Phone_Backend_Snom_Callhistory::getInstance();
                break;
                
            default:
                throw new Exception('unsupported phone backend');
        }

        return $instance;
    }
}    
