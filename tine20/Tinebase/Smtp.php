<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Smtp
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class Tinebase_Smtp
 * 
 * send emails using smtp
 * 
 * @package Tinebase
 * @subpackage Smtp
 */
class Tinebase_Smtp
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Smtp
     */
    private static $_instance = NULL;
    
    /**
     * the default smtp transport
     *
     * @var Zend_Mail_Transport_Abstract
     */
    protected static $_defaultTransport = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $config = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP, 'Tinebase', array(
            'hostname' => 'localhost', 
            'port' => 25
        ));
        
        // set default transport none is set yet
        if (! self::getDefaultTransport()) {
            // don't try to login if no username is given or if auth set to 'none'
            if ($config['auth'] == 'none' || empty($config['username'])) {
                unset($config['username']);
                unset($config['password']);
                unset($config['auth']);
            }
            
            if ($config['ssl'] == 'none') {
                unset($config['ssl']);
            }
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Setting SMTP transport. Hostname: ' . $config['hostname']);
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($config, TRUE));
            
            self::setDefaultTransport(new Zend_Mail_Transport_Smtp($config['hostname'], $config));
        }
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {   
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Smtp
     */
    public static function getInstance() 
    {
		if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Smtp();
        }
        
        return self::$_instance;
    }

    /**
     * sets default transport
     * @param  Zend_Mail_Transport_Abstract $_transport
     * @return void
     */
    public static function setDefaultTransport($_transport)
    {
        self::$_defaultTransport = $_transport;
    }
    
    /**
     * returns default transport
     * 
     * @return Zend_Mail_Transport_Abstract
     */
    public static function getDefaultTransport()
    {
        return self::$_defaultTransport;
    }
    
    /**
     * send message using default transport or an instance of Zend_Mail_Transport_Abstract
     *
     * @param Zend_Mail $_mail
     * @param Zend_Mail_Transport_Abstract $_transport
     * @return void
     */
    public function sendMessage(Zend_Mail $_mail, $_transport = NULL)
    {
        $transport = $_transport instanceof Zend_Mail_Transport_Abstract ? $_transport : self::getDefaultTransport();
        
        $_mail->addHeader('X-MailGenerator', 'Tine 2.0');
        
        $_mail->send($transport); 
    }
}
