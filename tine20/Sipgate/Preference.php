<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
         * the international prefix
         */
        const INTERNATIONAL_PREFIX = 'internationalPrefix';

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
                  self::MOBILENUMBER,
                  self::INTERNATIONAL_PREFIX
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
                    
                    self::INTERNATIONAL_PREFIX => array(
                        'label'         => $translate->_('International calling code'),
                        'description'   => $translate->_('Your international calling code (e.g. +1 in the United States, +49 in Germany)'),
                        )
                );

                return $prefDescriptions;
        }

        private function getOptions($_preferenceName, $_options) {
                $preference = $this->_getDefaultBasePreference($_preferenceName);

                if($_preferenceName == self::INTERNATIONAL_PREFIX) {
                    $file = dirname(__FILE__) . '/Setup/calling-codes.xml';
                    $preference->options = file_get_contents($file);
                } else {
                    $doc = new DomDocument('1.0');
                    $options = $doc->createElement('options');
                    $doc->appendChild($options);
                    foreach ($_options as $opt) {
                        if($_preferenceName == self::FAXID || $_preferenceName == self::PHONEID) {
                            if ($_preferenceName == self::FAXID && $opt->tos != 'fax') {
                                continue;
                            } elseif ($_preferenceName == self::PHONEID && $opt->tos != 'voice') {
                                continue;
                            }
                            $id = $opt->id;
                            $text = $opt->e164_out . ', ' . $opt->uri_alias . ', ' . $opt->sip_uri;
                        } else {
                            $id = $opt['id'];
                            $text = $opt['text']; 
                        }
                        $value  = $doc->createElement('value');
                        $value->appendChild($doc->createTextNode($id));
                        $label  = $doc->createElement('label');
                        $label->appendChild($doc->createTextNode($text));
                        
                        $option = $doc->createElement('option');
                        $option->appendChild($value);
                        $option->appendChild($label);
                        $options->appendChild($option);
                    }
                    $preference->options = $doc->saveXML();
                    // save last option in pref value per default
                    if($opt) $preference->value = $id;
                }
                
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
        public function getApplicationPreferenceDefaults($_preferenceName, $_accountId = NULL, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
        {
            if ($_preferenceName == self::MOBILENUMBER) {
                $c = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
                $possibleLines = array();
                if(!empty($c->tel_cell)) $possibleLines[] = array('id' => 'tel_cell', 'text' => $c->tel_cell);
                if(!empty($c->tel_cell_private)) $possibleLines[] = array('id' => 'tel_cell_private', 'text' => $c->tel_cell_private);
                if(!empty($c->tel_other)) $possibleLines[] = array('id' => 'tel_other', 'text' => $c->tel_other);
            } elseif ($_preferenceName != self::INTERNATIONAL_PREFIX) {
                $possibleLines = Sipgate_Controller_Line::getInstance()->getUsableLines();
            }
            
            switch($_preferenceName) {
                case self::PHONEID:
                    $pref = $this->getOptions($_preferenceName, $possibleLines);
                    break;
                case self::FAXID:
                    $pref = $this->getOptions($_preferenceName, $possibleLines);
                    break;
                case self::MOBILENUMBER:
                    $pref = $this->getOptions($_preferenceName, $possibleLines);
                    break;
                case self::INTERNATIONAL_PREFIX:
                    $pref = $this->getOptions($_preferenceName);
                    break;
                default:
                    throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
            }
            return $pref;
        }
}
