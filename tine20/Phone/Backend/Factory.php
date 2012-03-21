<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * phone backend factory class
 * 
 * @package     Phone
 */
class Phone_Backend_Factory
{
    /**
     * object instance
     *
     * @var Addressbook_Backend_Factory
     */
    private static $_instance = NULL;
    
    /**
     * backend object instances
     */
    private static $_backends = array();
    
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
      * constant for the snom phone webserver backend class
      *
      */
     const SNOM_WEBSERVER = 'SnomWebserver';
     
    /**
     * factory function to return a selected phone backend class
     *
     * @param   string $_type
     * @return  Phone_Backend_Interface
     * @throws  Phone_Exception_InvalidArgument
     * @throws  Phone_Exception_NotFound
     */
    static public function factory($_type)
    {
        switch($_type) {
            case self::ASTERISK:
                if (!isset(self::$_backends[$_type])) {
                    if(isset(Tinebase_Core::getConfig()->asterisk)) {
                        $asteriskConfig = Tinebase_Core::getConfig()->asterisk;
                        $url        = $asteriskConfig->managerbaseurl;
                        $username   = $asteriskConfig->managerusername;
                        $password   = $asteriskConfig->managerpassword;
                    } else {
                        throw new Phone_Exception_NotFound('No settings found for asterisk backend in config file!');
                    }
                    self::$_backends[$_type] = Phone_Backend_Asterisk::getInstance($url, $username, $password);
                }
                $instance = self::$_backends[$_type];
                break;
            case self::CALLHISTORY:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Phone_Backend_Snom_Callhistory();
                }
                $instance = self::$_backends[$_type];
                break;
            case self::SNOM_WEBSERVER:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Phone_Backend_Snom_Webserver();
                }
                $instance = self::$_backends[$_type];
                break;
            default:
                throw new Phone_Exception_InvalidArgument('Unsupported phone backend (' . $_type . ').');
        }

        return $instance;
    }
}    
