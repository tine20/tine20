<?php
/**
 * Tine 2.0
 * 
 * @package     Dialer
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * controller class for the Dialer application
 * 
 * @package     Dialer
 */
class Dialer_Controller
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Dialer_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Dialer_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Dialer_Controller;
        }
        
        return self::$_instance;
    }
    
    public function dialNumber($_number)
    {
        $backed = Dialer_Backend_Factory::factory(Dialer_Backend_Factory::ASTERISK);
        
        $extension = $backend->getPreferedExtension(Zend_Registry::get('currentAccount'));

        $backend->dialNumber($extension['device'], $extension['context'], $_number, 1, $extension['callerid']);        
    }    
}