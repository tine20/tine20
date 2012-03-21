<?php
/**
 * Snom_Setting controller for Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Snom_Setting controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_Snom_Setting extends Voipmanager_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Voipmanager_Controller_Snom_Setting
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_modelName   = 'Voipmanager_Model_Snom_Setting';
        $this->_backend     = new Voipmanager_Backend_Snom_Setting();
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
     * @return Voipmanager_Controller_Snom_Setting
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_Snom_Setting();
        }
        
        return self::$_instance;
    }
}
