<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller class for the Phone application
 * 
 * @package     Phone
 */
class Phone_Controller extends Tinebase_Controller_Abstract
{
    /**
     * call backend type
     * 
     * @var string
     */
    protected $_callBackendType = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $snomConfig = Tinebase_Config::getInstance()->get('snom', new Tinebase_Config_Struct(array(
            'backend' => Phone_Backend_Factory::ASTERISK
        )))->toArray();
        $this->_callBackendType = $snomConfig['backend'];
        $this->_applicationName = 'Phone';
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
            self::$_instance = new Phone_Controller();
        }
        
        return self::$_instance;
    }
    
    /**
     * dial number
     *
     * @param   int $_number
     * @param   string $_phoneId
     * @param   string $_lineId
     * @throws  Phone_Exception_NotFound
     * 
     * @todo check dial right here?
     */
    public function dialNumber($_number, $_phoneId = NULL, $_lineId = NULL)
    {
        $accountId = Tinebase_Core::getUser()->getId();
        $vmController = Voipmanager_Controller_Snom_Phone::getInstance();
        $backend = Phone_Backend_Factory::factory($this->_callBackendType);

        $number = $this->_cleanNumber($_number);

        if ($_phoneId === NULL && $_lineId === NULL) {
            
            // use first phone and first line
            $filter = new Voipmanager_Model_Snom_PhoneFilter(array(
                array('field' => 'account_id', 'operator' => 'equals', 'value' => $accountId)
            ));
            $phones = $vmController->search($filter);

            if(count($phones) > 0) {
                $phone = $vmController->get($phones[0]->id);
                if ($this->_callBackendType === Phone_Backend_Factory::ASTERISK) {
                    if (count($phone->lines) > 0) {
                        $asteriskLineId = $phone->lines[0]->asteriskline_id;
                    } else {
                        throw new Phone_Exception_NotFound('No line found for this phone.');
                    }
                 }
            } else {
                throw new Phone_Exception_NotFound('No phones found.');
            }
            
        } else {
            // use given phone and line ids
            $phone = Phone_Controller_MyPhone::getInstance()->get($_phoneId);
            if ($this->_callBackendType === Phone_Backend_Factory::ASTERISK) {
               $line = $phone->lines[$phone->lines->getIndexById($_lineId)];
               $asteriskLineId = $line->asteriskline_id;
            }
        }

        if ($this->_callBackendType === Phone_Backend_Factory::SNOM_WEBSERVER) {
            $filter = new Voipmanager_Model_Snom_PhoneFilter(array(
                array('field' => 'account_id', 'operator' => 'equals', 'value' => $accountId)
            ));
            foreach ($vmController->search($filter) as $p) {
                if ($p->id == $phone->id) {
                    // @todo http_user / http_pass
                    $backend->dialNumber($p->ipaddress, $number, null, null);
                    break;
                }
            }
        } else {
            $asteriskLine = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($asteriskLineId);
            $asteriskContext = Voipmanager_Controller_Asterisk_Context::getInstance()->get($asteriskLine->context_id);
            
            $backend->dialNumber('SIP/' . $asteriskLine->name, $asteriskContext->name, $number, 1, "WD <$number>");
        }
    }
    
    /**
     * removes illegal chars from telephone number
     * @param string $_number
     */
    protected function _cleanNumber($_number)
    {
        return preg_replace('/[^\d+]/','',$_number);
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
        
        $_call->start = Tinebase_DateTime::now();
        
        $filter = new Voipmanager_Model_Asterisk_SipPeerFilter(array(
            array('field' => 'name', 'operator' => 'equals', 'value' => $_call->line_id)
        ));
        $asteriskSipPeers = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->search($filter);
        if(count($asteriskSipPeers) > 0) {
            $_call->callerid = $asteriskSipPeers[0]->callerid;
        } else {
            $_call->callerid = $_call->line_id;
        }
        
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
        
        $_call->connected = Tinebase_DateTime::now();
        
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
        
        $_call->disconnected = Tinebase_DateTime::now();
        
        $call = $backend->update($_call);
        // calculate duration and ringing time
        if($call->connected instanceof DateTime) {
            
            // how long did we talk
            $connected = clone $call->connected;
            $disconnected = clone $call->disconnected;
            $call->duration = $disconnected->getTimestamp()-$connected->getTimestamp();
            
            // how long was the telephone ringing
            $start = clone $call->start;
            $connected = clone $call->connected;
            $call->ringing = $connected->getTimestamp()-$start->getTimestamp();
        } else {
            $start = clone $call->start;
            $disconnected = clone $call->disconnected;
            $call->ringing = $disconnected->getTimestamp()-$start->getTimestamp();
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
