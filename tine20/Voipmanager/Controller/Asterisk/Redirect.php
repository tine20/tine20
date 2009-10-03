<?php
/**
 * Asterisk_Redirect controller for Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Asterisk_Redirect controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_Asterisk_Redirect extends Voipmanager_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Voipmanager_Controller_Asterisk_Redirect
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_modelName   = 'Voipmanager_Model_Asterisk_Redirect';
        $this->_backend     = new Voipmanager_Backend_Asterisk_Redirect();
        //$this->_cache       = Zend_Registry::get('cache');        
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
     * @return Voipmanager_Controller_Asterisk_SipPeer
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_Asterisk_Redirect();
        }
        
        return self::$_instance;
    }
}
