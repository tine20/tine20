<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Json.php 26 2011-05-03 01:42:01Z alex $
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Sipgate application
 *
 * @package     Sipgate
 */
class Sipgate_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{

	/**
	 * app name
	 *
	 * @var string
	 */
	protected $_applicationName = 'Sipgate';

	/**
	 * dial number
	 *
	 * @param  String $_number phone number
	 * @return Array
	 */
	public function dialNumber($_number) {
		$_number = 'sip:'.preg_replace('/\+/','',$_number).'@sipgate.net';
		$resp = Sipgate_Controller::getInstance()->dialNumber($_number);
		if($resp['StatusCode'] === 200) {
			$ret = array('success' => true,'state' => $resp);
		}
		else {
			$ret = array('error' => true,'response' => $resp);
		}
		return $ret;
	}

	/**
	 *
	 * returns the Call History of the uri
	 * @param String $_sipUri
	 * @param Integer $_start
	 * @param Integer $_stop
	 * @param Integer $_pstart
	 * @param Integer $_plimit
	 */

	public function getCallHistory($_sipUri, $_start, $_stop, $_pstart = 0, $_plimit = 20) {

		if(empty($_sipUri)) throw new Sipgate_Exception('Sip-Uri leer!');
		if($_sipUri == 'root') return array();

		// Kein Plan, wo die Vorwahlen herkommen:
		$stripPrefix = array('sip:2300','sip:2301','sip:2400','sip:','@sipgate.net','anonymous');
		$stripRepl = array('','','','','');

		$paging = array("start" => 0, "limit" => 1);
		$ret = array();
		$history = Sipgate_Controller::getInstance()->getCallHistory($_sipUri, $_start, $_stop, $_pstart, $_plimit);
		if($history['totalcount'] > 0) {
			foreach($history['items'] as &$hEntry) {
				$rUri = str_replace($stripPrefix,$stripRepl,$hEntry['RemoteUri']);
				$hEntry['Timestamp'] = strtotime($hEntry['Timestamp']);
				$hEntry['LocalNumber'] = '+' . preg_replace('/\D*/i','',$hEntry['LocalUri']);
				$filter = array(array("field" => "telephone","operator" => "contains","value" => $rUri));
				$s = $this->_search($filter, $paging, Addressbook_Controller_Contact::getInstance(), 'Addressbook_Model_ContactFilter');

				if($s['totalcount'] === '1') {
					$hEntry['RemoteParty'] = $s['results'][0]['n_fn'] . ' ( +' . $rUri . ' )';
					$hEntry['RemoteRecord'] = $s['results'][0];
				}
				else {
					if(empty($rUri)) $hEntry['RemoteParty'] = 'unknown';
					else $hEntry['RemoteParty'] = '+' . $rUri;
					$hEntry['RemoteRecord'] = false;
				}
				if(empty($rUri)) $hEntry['RemoteNumber'] = 'unknown';
				else $hEntry['RemoteNumber'] = '+' . $rUri;

			}
			$ret = &$history;



		}

		return $ret;
	}


	/**
	 * send SMS
	 *
	 * @param  int    $_number  phone number
	 * @param  int    $_content the content
	 * @return array
	 */
	public function sendSms($_number,$_content)	{

		//		$resp['responseText'] = '"response":{"StatusCode":200,"SessionID":"","StatusString":"Method success"}';

		if(($resp = Sipgate_Controller::getInstance()->sendSms($_number,$_content)))
			return array('success' => true,'response'  => $resp);
		return array('success' => false,'response'  => $resp);
	}

	/**
	 * @return array
	 */
	public function getPhoneDevices() {
		$phones = Sipgate_Controller::getInstance()->getPhoneDevices();
		if($phones) {
			$ret = array('success' => true,'phones' => $phones);
		}
		else {
			$ret = array('success' => false);
		}
		return $ret;

	}
	/**
	 * @return array
	 */
	public function getFaxDevices() {
		$faxes = Sipgate_Controller::getInstance()->getFaxDevices();
		if($faxes) {
			$ret = array('success' => true,'phones' => &$faxes);
		}
		else {
			$ret = array('error' => true);
		}
		return $ret;
	}

	/**
	 * get Session Status
	 *
	 * @param  string $sessionId
	 * @return array
	 */
	public function getSessionStatus($sessionId) {
		if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in JSON-Frontend submitted!');

		if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug('getting Status ' . $sessionId);

		return Sipgate_Controller::getInstance()->getSessionStatus($sessionId);
	}

	/**
	 * close Session
	 *
	 * @param string $sessionId
	 * @return array
	 */

	public function closeSession($sessionId) {
		if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in JSON-Frontend submitted!');
		return Sipgate_Controller::getInstance()->closeSession($sessionId);
	}

	/**
	 * Returns registry data
	 *
	 * @return mixed array 'variable name' => 'data'
	 */
	public function getRegistryData()
	{
        try {
    		$phoneId = Tinebase_Core::getPreference($this->_applicationName)->getValue(Sipgate_Preference::PHONEID);
    		$faxId = Tinebase_Core::getPreference($this->_applicationName)->getValue(Sipgate_Preference::FAXID);
    		$mobileNumber = Tinebase_Core::getPreference($this->_applicationName)->getValue(Sipgate_Preference::MOBILENUMBER);
    		$devices = Sipgate_Controller::getInstance()->getAllDevices();
        } catch (Sipgate_Exception_Backend $seb) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . ' (' . __LINE__ . ') Could not get Sipgate registry data: ' . $seb->getMessage());
            return array();
        } catch (Zend_Exception $ze) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . ' (' . __LINE__ . ') Could not connect to sipgate: ' . $ze->getMessage());
            return array();
        }

		return array(
			'phoneId' => $phoneId,
			'faxId' => $faxId,
			'mobileNumber' => $mobileNumber,
			'Devices' => $devices
		);
	}
}
