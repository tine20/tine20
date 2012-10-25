<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Api Backend for the Sipgate application
 *
 * @package     Sipgate
 */
class Sipgate_Backend_Api {

    /**
     * @var Zend_Http_Client
     */
    protected $_http;

    /**
     * @var Zend_XmlRpc_Client
     */

    protected $_rpc;

    /**
     * Sipgate Devices
     * @var array
     */

    protected $devices = NULL;

    /**
     * resolved account
     * @var Sipgate_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * Phone Devices
     * @var array
     */
    
    
    protected $phoneDevices = NULL;

    protected $faxDevices = NULL;

    protected $allDevices = NULL;

    protected $_lastException = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Sipgate_Backend_Api
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Sipgate_Backend_Api
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sipgate_Backend_Api();
        }

        return self::$_instance;
    }

    /**
     * calls the samurai
     * @param string $_sipUri
     * @param Tinebase_DateTime $_start
     * @param Tinebase_DateTime $_stop
     * @return Zend_XmlRpc_Value_Struct
     */
    private function _samuraiItemizedEntriesGet($_sipUri, $_start, $_stop)
    {
        $localUriList[] = new Zend_XmlRpc_Value_String($_sipUri);
        $structAr['LocalUriList'] = new Zend_XmlRpc_Value_Array($localUriList);

        $structAr['PeriodStart'] = new Zend_XmlRpc_Value_DateTime($_start->toString());
        $structAr['PeriodEnd'] = new Zend_XmlRpc_Value_DateTime($_stop->toString());
        $struct = new Zend_XmlRpc_Value_Struct($structAr);

        return $this->_rpc->call('samurai.ItemizedEntriesGet',array(0 => $struct));
    }

    /**
     * calls the samurai
     * @param string $_sipUri
     * @param Tinebase_DateTime $_start
     * @param Tinebase_DateTime $_stop
     * @return Zend_XmlRpc_Value_Struct
     */
    private function _samuraiHistoryGetByDate($_sipUri, $_start, $_stop)
    {
        $localUriList[] = new Zend_XmlRpc_Value_String($_sipUri);
        $structAr['LocalUriList'] = new Zend_XmlRpc_Value_Array($localUriList);

        $structAr['PeriodStart'] = new Zend_XmlRpc_Value_DateTime($_start->toString());
        $structAr['PeriodEnd'] = new Zend_XmlRpc_Value_DateTime($_stop->toString());
        $struct = new Zend_XmlRpc_Value_Struct($structAr);

        return $this->_rpc->call('samurai.HistoryGetByDate',array(0 => $struct));
    }

    /**
     * connect
     * @param String $_accountId     ID of the account if configured already or resolved Sipgate_Model_Account
     * @param String $_username      Username if no account-id is given
     * @param String $_password      Password if no account-id is given
     * @param String $_accounttype   Accounttype if no account-id is given
     */
    public function connect($_accountId = NULL, $_username = NULL, $_password = NULL, $_accounttype = NULL)
    {
        if($_accountId) {
            if($_accountId instanceof Sipgate_Model_Account) {
                $this->_account = $_accountId;
            } else {
                $this->_account = Sipgate_Controller_Account::getInstance()->getResolved($_accountId);
            }
            $cfg = $this->_account->toArray();
            if(!$cfg) {
                throw new Sipgate_Exception_ResolveCredentials();
            }
        } else {
            if($_username && $_password && $_accounttype) {
                $cfg['username'] = $_username;
                $cfg['password'] = $_password;
                $cfg['accounttype'] = $_accounttype;
            } else {
                throw new Sipgate_Exception_MissingConfig();
            }
        }

        if($cfg['accounttype'] == 'team') {
            $url = 'https://api.sipgate.net/RPC2';
        } else {
            $url = 'https://samurai.sipgate.net/RPC2';
        }

        $this->_http = new Zend_Http_Client($url);
        $this->_http->setMethod('post');
        $this->_http->setAuth($cfg['username'], $cfg['password']);
        $this->_rpc = new Zend_XmlRpc_Client($url, $this->_http);

        try {
            $this->identify();
        } catch (Zend_XmlRpc_Client_HttpException $e) {
            switch ($e->getCode()) {
                case 401:
                    // Set Account Invalid on auth error
                    if($_accountId) {
                        $this->_account->is_valid = false;
                        $ac->update($this->_account);
                    }
                    throw new Sipgate_Exception_Authorization();
                    break;
                default: $this->_throwUnknownExeption($e);
            }
        } catch (Zend_Http_Client_Adapter_Exception $e) {
            switch ($e->getCode()) {
                case 0:
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " throws connection error exception: " . get_class($e));
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Code:    " . $e->getCode());
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Message: " . $e->getMessage());
                    }
                    throw new Sipgate_Exception_NoConnection();
                    break;
                default:
                    $this->_throwUnknownExeption($e);
            }
        } catch (Exception $e) {
            $this->_throwUnknownExeption($e);
        }

        return $this;
    }
    /**
     * Identifies and Validates Connection
     */
    public function identify() {
        $version = Tinebase_Application::getInstance()->getApplicationByName('Sipgate')->version;
        $this->_rpc->call('samurai.ClientIdentify',
            array(0 => new Zend_XmlRpc_Value_Struct(array(
                'ClientName' => new Zend_XmlRpc_Value_String('Tine 2.0 Sipgate'),
                'ClientVersion' => new Zend_XmlRpc_Value_String($version),
                'ClientVendor' =>new Zend_XmlRpc_Value_String('Alexander Stintzing')
            )))
        );
    }

    /**
     * Get Call History
     * @param string $_sipUri
     * @param Tinebase_DateTime $_start
     * @param Tinebase_DateTime $_stop
     * @return Zend_XmlRpc_Value_Struct
     */
    public function getCallHistory($_sipUri, $_start, $_stop) {
        // TODO: combine with itemized entries
        //$resp = $this->_samuraiItemizedEntriesGet($_sipUri, $_start, $_stop);

        $resp = $this->_samuraiHistoryGetByDate($_sipUri, $_start, $_stop);

        if($resp) {
            $ret = array(
                'history' => $resp['History'],
                'totalcount' => count($resp['History'])
            );
        } else {
            $ret = array(
                'history' => NULL,
                'totalcount' => NULL,
            );
        }

        $ret['status_code'] = $resp['StatusCode'];
        $ret['status_string'] = $resp['StatusString'];
         
        return $ret;
    }
    
    /**
     * initiate new call
     *
     * @param string $_caller
     * @param string $_callee
     */
    public function dialNumber($_caller, $_callee)
    {
        $structAr['LocalUri'] = new Zend_XmlRpc_Value_String($_caller);
        $structAr['RemoteUri'] = new Zend_XmlRpc_Value_String($_callee);
        $structAr['TOS'] = new Zend_XmlRpc_Value_String('voice');
        $struct = new Zend_XmlRpc_Value_Struct($structAr);
        
        return $this->_rpc->call('samurai.SessionInitiate',array(0 => $struct));
    }


    /**
     * initiate new conference
     *
     * @param string $_caller
     * @param array $_callees
     */
    public function initiateConference($_caller,$_callees)
    {
        $structAr['LocalUri'] = new Zend_XmlRpc_Value_String($_caller);
        foreach($_callees as $callee) {
            $remotes[] = new Zend_XmlRpc_Value_String($_callee);
        }
        $structAr['RemoteUri'] = new Zend_XmlRpc_Value_Array($remotes);
        $structAr['TOS'] = new Zend_XmlRpc_Value_String('voice');
        $struct = new Zend_XmlRpc_Value_Struct($structAr);
        return $this->_rpc->call('samurai.SessionInitiateMulti',array(0 => $struct));
    }

    /**
     * Get Call Information (itemizedEntries)
     * @param string $_sipUri
     * @return Zend_XmlRpc_Value_Struct
     */
    //        public function getCallInfo($_sipUri) {
    //
    //                $localUriList[] = new Zend_XmlRpc_Value_String($_sipUri);
    //                $structAr['LocalUriList'] = new Zend_XmlRpc_Value_Array($localUriList);
    //                $structAr['PeriodStart'] = new Zend_XmlRpc_Value_DateTime(time()-604800);
    //                $structAr['PeriodEnd'] = new Zend_XmlRpc_Value_DateTime(time());
    //                $struct = new Zend_XmlRpc_Value_Struct($structAr);
    //
    //                $resp = $this->_rpc->call('samurai.HistoryGetByDate',array(0 => $struct));
    //                $ret = false;
    //                if($resp['StatusCode'] === 200) {
    //                        $ret = $resp['History'];
    //
    //                }
    //                return $ret;
    //        }

    /**
     * Get devices
     * @return Zend_XmlRpc_Response
     */
    public function getDevices() {
        if(is_array($this->devices)) return $this->devices;
        $resp = $this->_rpc->call('samurai.OwnUriListGet');
        if($resp['StatusCode'] === 200) {
            $this->devices = $resp['OwnUriList'];
            return $this->devices;
        }
        else {
            return false;
        }
    }

    public function getPhoneDevices() {
        if(is_array($this->phoneDevices)) return $this->phoneDevices;
        $ret = false;
        if(($dev = $this->getDevices())) {
            foreach($dev as $device) {
                if(in_array('voice',$device['TOS'])) $this->phoneDevices[] = $device;
            }
            $ret = $this->phoneDevices;
        }
        return $ret;
    }

    public function getFaxDevices() {
        if(is_array($this->faxDevices)) return $this->faxDevices;
        $ret = false;
        if(($dev = $this->getDevices())) {
            foreach($dev as $device) {
                if(in_array('fax',$device['TOS'])) $this->faxDevices[] = $device;
            }
            $ret = $this->faxDevices;
        }
        return $ret;
    }

    public function getAllDevices() {
        if (is_array($this->allDevices)) return $this->allDevices;
        $ret = false;
        $i=0;
        if (($phones = $this->getPhoneDevices())) {
            foreach($phones as $phone) {
                $this->allDevices[$i] = $phone;
                $this->allDevices[$i]['type'] = 'phone';
                $i++;
            }
            $ret['devices'] = &$this->allDevices;
        }
        if (($faxes = $this->getFaxDevices())) {
            foreach($faxes as $fax) {
                $this->allDevices[$i] = $fax;
                $this->allDevices[$i]['type'] = 'fax';
                $i++;
            }
        }
//         $ret['account_id'] = $this->_account_id;
        return $ret;
    }

    /**
     * Get generic status
     * @param string $sessionId
     * @return Zend_XmlRpc_Response
     */

    public function getSessionStatus($sessionId) {
        if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in API submitted!');
        $structAr['SessionID'] = new Zend_XmlRpc_Value_String($sessionId);
        $struct = new Zend_XmlRpc_Value_Struct($structAr);
        return $this->_rpc->call('samurai.SessionStatusGet',array(0 => $struct));
    }

    /**
     * Closes a Session
     * @param string $sessionId
     * @return Zend_XmlRpc_Response
     */

    public function closeSession($sessionId) {
        if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in API submitted!');
        $structAr['SessionID'] = new Zend_XmlRpc_Value_String($sessionId);
        $struct = new Zend_XmlRpc_Value_Struct($structAr);
        return $this->_rpc->call('samurai.SessionClose',array(0 => $struct));
    }

    /**
     *
     * @param Exception $e
     */
    protected function _throwUnknownExeption(Exception $e)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " throws unknown exception: " . get_class($e));
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Code:    " . $e->getCode());
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Message: " . $e->getMessage());
        }
        // TODO better general handling here
        throw $e;
    }

    /**
     * send SMS
     *
     * @param string $_caller
     * @param string $_callee
     * @param string $_content
     */

    public function sendSms($_caller = null, $_callee, $_content)
    {
        $_callee = preg_replace('/\+/','',$_callee);
        $_caller = $_caller ? preg_replace('/\+/','',$_caller) : null;
        
        if($_caller) {
            $structAr['LocalUri'] = new Zend_XmlRpc_Value_String('sip:'.$_caller.'@sipgate.net');
        }
        $structAr['RemoteUri'] = new Zend_XmlRpc_Value_String('sip:'.$_callee.'@sipgate.net');
        $structAr['TOS'] = new Zend_XmlRpc_Value_String('text');
        $structAr['Content'] = new Zend_XmlRpc_Value_String($_content);
        
        $struct = new Zend_XmlRpc_Value_Struct($structAr);

        return $this->_rpc->call('samurai.SessionInitiate', array(0 => $struct));
    }

}
?>