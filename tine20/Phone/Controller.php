<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Controller.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
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
    
    /**
     * dial number
     *
     * @param int $_number
     * @param string $_phoneId
     * @param string $_lineId
     * 
     * @todo remove deprecated code
     */
    public function dialNumber($_number, $_phoneId = NULL, $_lineId = NULL)
    {
        $accountId = Zend_Registry::get('currentAccount')->getId();
        $vmController = Voipmanager_Controller::getInstance();
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::ASTERISK);
        
        if ($_phoneId === NULL && $_lineId === NULL) {
            
            // use first phone and first line
            // @todo remove that later
            $filter = new Voipmanager_Model_SnomPhoneFilter(array(
                'accountId' => $accountId 
            ));
            $phones = $vmController->getSnomPhones($filter);
            if(count($phones) > 0) {
                $phone = $vmController->getSnomPhone($phones[0]->id);
                if(count($phone->lines) > 0) {
                    $asteriskLineId = $phone->lines[0]->asteriskline_id;
                } else {
                    throw new Exception('No line found for this phone.');
                }
            } else {
                throw new Exception('No phones found.');
            }
        } else {
            $phone = $vmController->getMyPhone($_phoneId, $accountId);
            $line = $phone->lines[$phone->lines->getIndexById($_lineId)];
            $asteriskLineId = $line->asteriskline_id; 
        }

        $asteriskLine = $vmController->getAsteriskSipPeer($asteriskLineId);
        $backend->dialNumber('SIP/' . $asteriskLine->name, $asteriskLine->context, $_number, 1, $asteriskLine->callerid);
    }    
}