<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Api.php 26 2011-05-03 01:42:01Z alex $
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

    protected $devices = false;

    /**
     * Phone Devices
     * @var array
     */

    protected $phoneDevices = false;

    protected $faxDevices = false;

    protected $allDevices = false;

    protected $_url;

    protected $_username;

    protected $_password;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct($_username, $_password, $_url)	{

        $this->_url = (empty($_url)) ? 'https://samurai.sipgate.net/RPC2' : $_url;
        $this->_username = $_username;
        $this->_password = $_password;

        $this->_http = new Zend_Http_Client($this->_url);
        $this->_http->setMethod('post');
        $this->_http->setAuth($_username, $_password);

        $this->_rpc = new Zend_XmlRpc_Client($this->_url,$this->_http->setAuth($_username, $_password));

        $this->_rpc->call(
			'samurai.ClientIdentify',
        array(0 => new Zend_XmlRpc_Value_Struct(array(
				'ClientName' => new Zend_XmlRpc_Value_String('Tine 2.0 Sipgate'),
				'ClientVersion' =>new Zend_XmlRpc_Value_String('0.4'),
				'ClientVendor' =>new Zend_XmlRpc_Value_String('Alexander Stintzing')
        )))
        );
    }


    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

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
    public static function getInstance($_username, $_password, $_url)
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sipgate_Backend_Api($_username, $_password, $_url);
        }

        return self::$_instance;
    }

    /**
     * initiate new call
     *
     * @param string $_caller
     * @param string $_callee
     */
    public function dialNumber($_caller,$_callee)
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
     * Get Call History
     * @param string $_sipUri
     * @param string $_start
     * @param string $_stop
     * @param integer $_pstart
     * @param integer $_plimit
     * @return Zend_XmlRpc_Value_Struct
     */
    public function getCallHistory($_sipUri, $_start, $_stop, $_pstart, $_plimit) {
         
        $start = strtotime($_start);
        $stop = strtotime($_stop);
         
        $localUriList[] = new Zend_XmlRpc_Value_String($_sipUri);
        $structAr['LocalUriList'] = new Zend_XmlRpc_Value_Array($localUriList);

        $structAr['PeriodStart'] = new Zend_XmlRpc_Value_DateTime($start);
        $structAr['PeriodEnd'] = new Zend_XmlRpc_Value_DateTime($stop);
        $struct = new Zend_XmlRpc_Value_Struct($structAr);



        $resp = $this->_rpc->call('samurai.HistoryGetByDate',array(0 => $struct));
        $ret = false;

        if($resp) {
            $history = array_reverse($resp['History'],false); 

            if($resp['StatusCode'] === 200) {
                for ($i = $_pstart; $i < $_pstart + $_plimit; $i++) {
                    if(!empty($history[$i])) $ret['items'][] = $history[$i];
                    else break;
                }
                $ret['totalcount'] = count($resp['History']);
            }
        }
        return $ret;
    }

    /**
     * Get Call Information (itemizedEntries)
     * @param string $_sipUri
     * @return Zend_XmlRpc_Value_Struct
     */
    //	public function getCallInfo($_sipUri) {
    //
    //		$localUriList[] = new Zend_XmlRpc_Value_String($_sipUri);
    //		$structAr['LocalUriList'] = new Zend_XmlRpc_Value_Array($localUriList);
    //		$structAr['PeriodStart'] = new Zend_XmlRpc_Value_DateTime(time()-604800);
    //		$structAr['PeriodEnd'] = new Zend_XmlRpc_Value_DateTime(time());
    //		$struct = new Zend_XmlRpc_Value_Struct($structAr);
    //
    //		$resp = $this->_rpc->call('samurai.HistoryGetByDate',array(0 => $struct));
    //		$ret = false;
    //		if($resp['StatusCode'] === 200) {
    //			$ret = $resp['History'];
    //
    //		}
    //		return $ret;
    //	}

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
            $ret = &$this->allDevices;
        }
        if (($faxes = $this->getFaxDevices())) {
            foreach($faxes as $fax) {
                $this->allDevices[$i] = $fax;
                $this->allDevices[$i]['type'] = 'fax';
                $i++;
            }
        }
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
     * send SMS
     *
     * @param string $_caller
     * @param string $_callee
     * @param string $_content
     */

    public function sendSms($_caller,$_callee,$_content)
    {

        $_callee = preg_replace('/\+/','',$_callee);

        $structAr['LocalUri'] = new Zend_XmlRpc_Value_String('sip:'.$_caller.'@sipgate.net');
        $structAr['RemoteUri'] = new Zend_XmlRpc_Value_String('sip:'.$_callee.'@sipgate.net');
        $structAr['TOS'] = new Zend_XmlRpc_Value_String('text');
        $structAr['Content'] = new Zend_XmlRpc_Value_String($_content);
        $struct = new Zend_XmlRpc_Value_Struct($structAr);

        return $this->_rpc->call('samurai.SessionInitiate',array(0 => $struct));

    }

}
?>