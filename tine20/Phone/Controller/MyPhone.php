<?php
/**
 * MyPhone controller for Voipmanager Management application
 *
 * @package     Phone
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * MyPhone controller class for Voipmanager Management application
 * 
 * @package     Phone
 * @subpackage  Controller
 */
class Phone_Controller_MyPhone extends Voipmanager_Controller_Snom_Phone
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_modelName   = 'Phone_Model_MyPhone';
        $this->_backend     = new Voipmanager_Backend_Snom_Phone(NULL, $this->_modelName);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
            
    /**
     * holds the instance of the singleton
     *
     * @var Phone_Controller_MyPhone
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Controller_MyPhone
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Phone_Controller_MyPhone();
        }
        
        return self::$_instance;
    }
}
