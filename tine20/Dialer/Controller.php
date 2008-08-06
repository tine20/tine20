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
        $vmController = Voipmanager_Controller::getInstance();
        $filter = new Voipmanager_Model_SnomPhoneFilter(array(
            'accountId' => Zend_Registry::get('currentAccount')->getId()
        ));
        $phones = $vmController->getSnomPhones($filter);
        if(count($phones) > 0) {
            $phone = $vmController->getSnomPhone($phones[0]->id);
            if(count($phone->lines) > 0) {
                $asteriskLine = $vmController->getAsteriskSipPeer($phone->lines[0]->asteriskline_id);
                $backend = Dialer_Backend_Factory::factory(Dialer_Backend_Factory::ASTERISK);
                $backend->dialNumber('SIP/' . $asteriskLine->name, $asteriskLine->context, $_number, 1, $asteriskLine->callerid);
            }
        }        
    }    
}