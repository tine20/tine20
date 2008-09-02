<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * controller class for the Phone application
 * 
 * @package     Phone
 */
class Phone_Controller
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
     * @var Phone_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Phone_Controller;
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
                $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::ASTERISK);
                $backend->dialNumber('SIP/' . $asteriskLine->name, $asteriskLine->context, $_number, 1, $asteriskLine->callerid);
            }
        }        
    }    
}