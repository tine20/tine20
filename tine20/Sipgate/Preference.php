<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage	Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011 Alexander Stintzing (http://www.stintzing.net)
 * @version     $Id: Preference.php 11 2011-04-29 05:23:26Z alex $
 *
 */


/**
 * Sipgate preferences
 *
 * @package     Sipgate
 */
class Sipgate_Preference extends Tinebase_Preference_Abstract
{
	/**************************** application preferences/settings *****************/

	/**
	 * The users phone
	 */
	const PHONEID = 'phoneId';

	/**
	 * The users fax
	 */
	const FAXID = 'faxId';

	/**
	 * The users mobile number
	 */
	const MOBILENUMBER = 'mobileNumber';

	/**
	 * @var string application
	 */
	protected $_application = 'Sipgate';

	/**************************** public functions *********************************/

	/**
	 * get all possible application prefs
	 *
	 * @return  array   all application prefs
	 */
	public function getAllApplicationPreferences() {
		$allPrefs = array(
			self::PHONEID,
			self::FAXID,
			self::MOBILENUMBER
		);

		return $allPrefs;
	}

	/**
	 * get translated right descriptions
	 *
	 * @return array with translated descriptions for this applications preferences
	 */
	public function getTranslatedPreferences()
	{
		$translate = Tinebase_Translation::getTranslation($this->_application);

		$prefDescriptions = array(
		self::PHONEID  => array(
                'label'         => $translate->_('Your phone'),
                'description'   => $translate->_('This is your phone'),
		),
		self::FAXID  => array(
                'label'         => $translate->_('Your fax'),
                'description'   => $translate->_('This is your fax'),
		),
		self::MOBILENUMBER  => array(
                'label'         => $translate->_('Your mobile number'),
                'description'   => $translate->_('Used when sending SMS'),
			),
		);

		return $prefDescriptions;
	}

	private function getOptions($_preferenceName,$_options) {

		$preference = $this->_getDefaultBasePreference($_preferenceName);

		$doc = new DomDocument('1.0');
		$options = $doc->createElement('options');
		$doc->appendChild($options);

		foreach($_options as $opt) {

			$value  = $doc->createElement('value');
			$value->appendChild($doc->createTextNode($opt));
			$label  = $doc->createElement('label');
			$label->appendChild($doc->createTextNode($opt));

			$option = $doc->createElement('option');
			$option->appendChild($value);
			$option->appendChild($label);
			$options->appendChild($option);

		}

		$preference->value = '';
		$preference->options = $doc->saveXML();
		return $preference;
	}

	/**
	 * get preference defaults if no default is found in the database
	 *
	 * @param string $_preferenceName
	 * @param string|Tinebase_Model_User $_accountId
	 * @param string $_accountType
	 * @return Tinebase_Model_Preference
	 */
	public function getApplicationPreferenceDefaults($_preferenceName, $_accountId = NULL, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)	{
		switch($_preferenceName) {
			case self::PHONEID:
				$_phones = Sipgate_Controller::getInstance()->getPhoneDevices();
				foreach($_phones as $phone) {
					$dev[] = $phone['SipUri'];
				}
				$pref = $this->getOptions($_preferenceName, $dev);
				break;
			case self::FAXID:
				$_faxes = Sipgate_Controller::getInstance()->getFaxDevices();
				foreach($_faxes as $fax) {
					$dev[] = $fax['SipUri'];
				}
				$pref = $this->getOptions($_preferenceName, $dev);
				break;
			case self::MOBILENUMBER:
				$pref = $this->getOptions($_preferenceName,Tinebase_Core::getConfig()->sipgate->numbers->mobile);
				break;
			default:
				throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
		}
		return $pref;
	}

}
