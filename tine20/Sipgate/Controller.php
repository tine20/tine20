<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011 Alexander Stintzing (http://www.stintzing.net)
 * @version     $Id: Controller.php 26 2011-05-03 01:42:01Z alex $
 *
 */

/**
 * controller class for the Sipgate application
 *
 * @package     Sipgate
 */
class Sipgate_Controller extends Tinebase_Controller_Abstract
{
	/**
	 * call backend type
	 *
	 * @var string
	 */
	protected $_callBackendType = NULL;

	/**
	 * Application name
	 * @var string
	 */
	protected $applicationName = NULL;

	/**
	 * Holds the Preferences
	 * @var Sipgate_Preference
	 */
	protected $_pref = NULL;

	/**
	 * the constructor
	 *
	 * don't use the constructor. use the singleton
	 */
	private function __construct() {
		$this->_applicationName = 'Sipgate';
		$this->_pref = new Sipgate_Preference();
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
	 * @var Sipgate_Controller
	 */
	private static $_instance = NULL;

	/**
	 * the singleton pattern
	 *
	 * @return Sipgate_Controller
	 */
	public static function getInstance()
	{
		if (self::$_instance === NULL) {
			self::$_instance = new Sipgate_Controller();
		}

		return self::$_instance;
	}

	/**
	 * dial number
	 *
	 * @param   int $_callee
	 * @throws  Sipgate_Exception_NotFound
	 */
	public function dialNumber($_callee)
	{
		$_caller = $this->_pref->getValue('phoneId');
		$backend = Sipgate_Backend_Factory::factory();
		return $backend->dialNumber($_caller, $_callee);
	}

	/**
	 * gets the Session Status by an Id
	 * @param string $sessionId
	 */
	public function getSessionStatus($sessionId) {
		if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in Controller submitted!');
		$backend = Sipgate_Backend_Factory::factory();
		return $backend->getSessionStatus($sessionId);
	}

	/**
	 * Closes the Session by an Id
	 * @param string $sessionId
	 */
	public function closeSession($sessionId) {
		if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in Controller submitted!');
		$backend = Sipgate_Backend_Factory::factory();
		return $backend->closeSession($sessionId);
	}

	/**
	 *
	 * Gets the devices
	 */
	public function getAllDevices() {
		$backend = Sipgate_Backend_Factory::factory();
		return $backend->getAllDevices();
	}

	/**
	 *
	 * Gets the Phones
	 */
	public function getPhoneDevices() {
		$backend = Sipgate_Backend_Factory::factory();
		return $backend->getPhoneDevices();
	}

	/**
	 *
	 * Gets the Faxes
	 */
	public function getFaxDevices() {
		$backend = Sipgate_Backend_Factory::factory();
		return $backend->getFaxDevices();
	}

	/**
	 * Gets the CallHistory of the specified sipUri
	 *
	 * @param String $_sipUri
	 */

	public function getCallHistory($_sipUri) {

//		$filter = array(array("field" => "telephone","operator" => "contains","value" => "86870"));
//		$paging = array("sort"=>"email","dir"=>"ASC","start"=>0, "limit"=>1);
//
////		 var_dump($this->_search($filter, $paging, Addressbook_Controller_Contact::getInstance(), 'Addressbook_Model_ContactFilter'));
//
//
//
//		$history = Sipgate_Backend_Factory::factory()->getCallHistory($_sipUri);
//		foreach($history as $entry) {
//			die(var_dump($entry));
//		}
////
////
////
		return Sipgate_Backend_Factory::factory()->getCallHistory($_sipUri);
	}


	/**
	 * send SMS
	 *
	 * @param   string $_number
	 * @param   string $_content
	 */
	public function sendSms($_number,$_content)
	{
		$_sender = $this->_pref->getValue('mobileNumber');
		$backend = Sipgate_Backend_Factory::factory();
		return $backend->sendSms($_sender, $_number, $_content);
	}


}
