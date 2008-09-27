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
     * @todo check dial right here?
     */
    public function dialNumber($_number, $_phoneId = NULL, $_lineId = NULL)
    {
        $accountId = Zend_Registry::get('currentAccount')->getId();
        $vmController = Voipmanager_Controller::getInstance();
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::ASTERISK);
        
        if ($_phoneId === NULL && $_lineId === NULL) {
            
            // use first phone and first line
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
            // use given phone and line ids
            $phone = $vmController->getMyPhone($_phoneId, $accountId);
            $line = $phone->lines[$phone->lines->getIndexById($_lineId)];
            $asteriskLineId = $line->asteriskline_id; 
        }

        $asteriskLine = $vmController->getAsteriskSipPeer($asteriskLineId);
        $backend->dialNumber('SIP/' . $asteriskLine->name, $asteriskLine->context, $_number, 1, $asteriskLine->callerid);
    }    
    
    /**
     * Search for calls matching given filter
     *
     * @param Phone_Model_CallFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * 
     * @return Tinebase_Record_RecordSet
     */
    public function searchCalls(Phone_Model_CallFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {        
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);        
        $calls = $backend->search($_filter, $_pagination);
        
        return $calls;
    }
    
    /**
     * Search for calls matching given filter
     *
     * @param Phone_Model_CallFilter $_filter
     * 
     * @return Tinebase_Record_RecordSet
     */
    public function searchCallsCount(Phone_Model_CallFilter $_filter)
    {
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        $count = $backend->searchCount($_filter);
        return $count;
    }
    
    /************************ create / update calls ****************************/
    
    /**
     * start phone call and save in history
     *
     * @param Phone_Model_Call $_call
     * @return Phone_Model_Call
     */
    public function callStarted(Phone_Model_Call $_call) 
    {    
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        
        $_call->start = Zend_Date::now();
        $_call->callerid = $_call->line_id;
        
        $call = $backend->create($_call);
        
        return $call;
    }
    
    /**
     * update call
     *
     * @param Phone_Model_Call $_call
     * @return Phone_Model_Call
     */
    public function callConnected(Phone_Model_Call $_call)
    {
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        
        $_call->connected = Zend_Date::now();
        
        $call = $backend->update($_call);
        
        return $call;
    }

    /**
     * update call, set duration and ringing time
     *
     * @param Phone_Model_Call $_call
     * @return Phone_Model_Call
     */
    public function callDisconnected(Phone_Model_Call $_call)
    {
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        
        $_call->disconnected = Zend_Date::now();
        
        $call = $backend->update($_call);

        // calculate duration and ringing time
        if($call->connected instanceof Zend_Date) {
            // how long did we talk
            $connected = clone $call->connected;
            $disconnected = clone $call->disconnected;
            $call->duration = $disconnected->sub($connected);
            
            // how long was the telephone ringing
            $start = clone $call->start;
            $connected = clone $call->connected;
            $call->ringing = $connected->sub($start);
        } else {
            $start = clone $call->start;
            $disconnected = clone $call->disconnected;
            $call->ringing = $disconnected->sub($start);
        }

        $call = $backend->update($call);
        
        return $call;
    }

    /**
     * get one call from the backend
     *
     * @param string $_callId the callId
     * @return Phone_Model_Call
     */
    public function getCall($_callId)
    {
        $backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        
        $call = $backend->get($_callId);
        
        return $call;
    }
}
