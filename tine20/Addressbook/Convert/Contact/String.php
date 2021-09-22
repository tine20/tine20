<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract class to convert a string to contact record and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_String implements Tinebase_Convert_Interface
{
    /**
     * unrecognized tokens
     * 
     * @var array
     */    
    protected $_unrecognizedTokens = array();
    
    /**
     * config (parsing rules)
     * 
     * @var Zend_Config
     */    
    protected $_config = null;

    protected $libpostalMapping = [
        //'house' => 'unrecognized', // venue name e.g. "Brooklyn Academy of Music", and building names e.g. "Empire State Building"
        //'category' => 'adr_one_street2', // for category queries like "restaurants", etc.
        //'near' => 'adr_one_street2', // phrases like "in", "near", etc. used after a category phrase to help with parsing queries like "restaurants in Brooklyn"
        'house_number' => 'adr_one_street',//: usually refers to the external (street-facing) building number. In some countries this may be a compount, hyphenated number which also includes an apartment number, or a block number (a la Japan), but libpostal will just call it the house_number for simplicity.
        'road' => 'adr_one_street', //: street name(s)
        //'unit' => 'unrecognized', // an apartment, unit, office, lot, or other secondary unit designator
        //'level' => 'unrecognized', // expressions indicating a floor number e.g. "3rd Floor", "Ground Floor", etc.
        //'staircase' => 'unrecognized', //: numbered/lettered staircase
        //'entrance' => 'unrecognized', //numbered/lettered entrance
        //'po_box' => 'unrecognized', //: post office box: typically found in non-physical (mail-only) addresses
        'postcode' => 'adr_one_postalcode', //: postal codes used for mail sorting
        //'suburb' => 'unrecognized', //: usually an unofficial neighborhood name like "Harlem", "South Bronx", or "Crown Heights"
        'city_district' => 'adr_one_region',//: these are usually boroughs or districts within a city that serve some official purpose e.g. "Brooklyn" or "Hackney" or "Bratislava IV"
        'city' => 'adr_one_locality', //,: any human settlement including cities, towns, villages, hamlets, localities, etc.
        'island' => 'adr_one_region', //: named islands e.g. "Maui"
        'state_district' => 'adr_one_region', // usually a second-level administrative division or county.
        'state' => 'adr_one_region', // : a first-level administrative division. Scotland, Northern Ireland, Wales, and England in the UK are mapped to "state" as well (convention used in OSM, GeoPlanet, etc.)
        'country_region' => 'adr_one_region', //: informal subdivision of a country without any political status
        'country' => 'adr_one_countryname', //: sovereign nations and their dependent territories, anything with an ISO-3166 code.
        'world_region' => 'adr_one_countryname' // currently only used for appending “West Indi
    ];

    /**
     * config filename
     * 
     * @var string
     */
    const CONFIG_FILENAME = 'config/convert_from_string.xml';
    
    /**
     * the constructor
     * 
     * @param  array  $_config  config rules
     */
    public function __construct($_config = array())
    {
        if (! empty($_config)) {
            $this->_config = new Zend_Config($_config);
        } else {
            // read file with Zend_Config_Xml
            if ($filename = Addressbook_Config::getInstance()->{Addressbook_Config::CONTACT_ADDRESS_PARSE_RULES_FILE}) {
                $this->_config = new Zend_Config_Xml($filename);
            }
        }
    }

    /**
     * converts string to Addressbook_Model_Contact
     * 
     * @param  string                          $_blob   the string to parse
     * @param  Tinebase_Record_Interface        $_record  update existing contact
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($_blob, Tinebase_Record_Interface $_record = null)
    {
        if (!$_record) {
            $_record = new Addressbook_Model_Contact([], true);
        }

        if ($this->_config) {
            return $this->regexMethod($_blob, $_record);
        }

        $this->findTelephone($_blob, $_record);
        $this->findWeb($_blob, $_record);
        $this->findOrg($_blob, $_record);
        $this->findNames($_blob, $_record);

        if ($libpUrl = Addressbook_Config::getInstance()->{Addressbook_Config::LIBPOSTAL_REST_URL}) {
            $client = Tinebase_Core::getHttpClient($libpUrl);
            $client->setMethod('POST');
            $client->setRawData(json_encode(['query' => $_blob]));
            $response = $client->request();
            if (200 === $response->getStatus()) {
                foreach (json_decode($response->getBody(), true) as $labelValue) {
                    if (!isset($this->libpostalMapping[$labelValue['label']])) continue;

                    $value = trim($this->matchValueToOriginalData($labelValue['value'], $_blob));
                    $key = $this->libpostalMapping[$labelValue['label']];
                    $_record->{$key} = ($_record->{$key} ? $_record->{$key} . ' ' : '') . $value;
                }
            }
        } else {
            $this->findPostal($_blob, $_record);
            $this->findStreet($_blob, $_record);
        }

        $this->_unrecognizedTokens = array_filter(preg_split('/[\s,]+/', trim($_blob)));

        return $_record;
    }

    protected function findStreet(&$blob, $record)
    {
        if (preg_match('/(^|\W)([\w ]+\.?\s*\d+\s*\w{0,3})($|\W)/um', $blob, $match)) {
            $record->adr_one_street = trim($match[2]);
            $blob = str_replace($match[2], '', $blob);
        }
    }

    protected function findPostal(&$blob, $record)
    {
        if (preg_match('/(^|\D)((\d{5})\s+([\w ]+))$/um', $blob, $match)) {
            $record->adr_one_locality = trim($match[4]);
            $record->adr_one_postalcode = $match[3];
            $blob = str_replace($match[2], '', $blob);
        }
    }

    protected function findNames(&$blob, $record)
    {
        $firstLine = explode(PHP_EOL, $blob)[0];
        if (preg_match('/^([\w\s]+)(,[\w\s]+)?$/u', $firstLine, $match)) {
            if (isset($match[2])) {
                $record->n_family = trim($match[1]);
                $record->n_given = trim(substr($match[2], 1));
            } else {
                $parts = explode(' ', trim($match[1]));
                $record->n_family = trim(array_pop($parts));
                if (!empty($parts)) {
                    $record->n_given = trim(join(' ', $parts));
                }
            }
            $blob = str_replace($match[0], '', $blob);
        }
    }

    protected function findOrg(&$blob, $record)
    {
        if (preg_match_all('/^.*(GmbH|GbR|OHG|KG|AG).*$/um', $blob, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $record->org_name = ($record->org_name ? $record->org_name . ' ' : '') . $match[0];
                $blob = str_replace($match[0], '', $blob);
            }
        }
    }

    protected function findWeb(&$blob, $record)
    {
        $regex = substr(Tinebase_Mail::EMAIL_ADDRESS_CONTAINED_REGEXP, 1, strlen(Tinebase_Mail::EMAIL_ADDRESS_CONTAINED_REGEXP) - 3);
        if (preg_match_all('/(\S*mail.*?)?' . $regex . '/iu', $blob, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $record->email = ($record->email ? $record->email . ' ' : '') . $match[2];
                $blob = str_replace($match[0], '', $blob);
            }
        }

        if (preg_match_all('#((https?://)|www\.)[^\s"]+#iu', $blob, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $record->url = ($record->url ? $record->url . ' ' : '') . $match[0];
                $blob = str_replace($match[0], '', $blob);
            }
        }
    }

    protected function findTelephone(&$blob, $record)
    {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        if (preg_match_all('#((tel|fax|mob|work|home|phone)[^\d+]*?)(\(?\s*[+0][()\d\s.\-/]{7,100})#iu', $blob, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $match[3] = trim($match[3]);
                if ($phoneUtil->isViablePhoneNumber($match[3])) {
                    if (stripos($match[0], 'tel') === 0 || stripos($match[0], 'phone') === 0) {
                        $record->tel_work = ($record->tel_work ? $record->tel_work . ' ' : '') . $match[3];
                    } elseif (stripos($match[0], 'fax') === 0) {
                        $record->tel_fax = ($record->tel_fax ? $record->tel_fax . ' ' : '') . $match[3];
                    } elseif (stripos($match[0], 'mob') === 0) {
                        $record->tel_cell = ($record->tel_cell ? $record->tel_cell . ' ' : '') . $match[3];
                    } elseif (stripos($match[0], 'work') === 0) {
                        $record->tel_work = ($record->tel_work ? $record->tel_work . ' ' : '') . $match[3];
                    } elseif (stripos($match[0], 'home') === 0) {
                        $record->tel_home = ($record->tel_home ? $record->tel_home . ' ' : '') . $match[3];
                    }
                    $blob = str_replace($match[0], '', $blob);
                }
            }
        }
        if (preg_match_all('#\(?\s*[+0][()\d\s.\-/]{7,100}#ium', $blob, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $match[0] = trim($match[0]);
                if ($phoneUtil->isViablePhoneNumber($match[0])) {
                    $record->tel_work = ($record->tel_work ? $record->tel_work . ' ' : '') . $match[0];
                    $blob = str_replace($match[0], '', $blob);
                }
            }
        }
    }

    protected function matchValueToOriginalData($value, &$data)
    {
        $regex = '/' . preg_quote($value) . '/u';
        if (preg_match($regex, $data, $match)) {
            $data = preg_replace($regex, '', $data, 1);
            return $match[0];
        }
        $regex = '/' . preg_quote($value) . '/ui';
        if (preg_match($regex, $data, $match)) {
            $data = preg_replace($regex, '', $data, 1);
            return $match[0];
        }
        $parts = explode(' ', $value);
        if (count($parts) > 1) {
            $value = '';
            foreach ($parts as $part) {
                $value .= ($value !== '' ? ' ' : '') . $this->matchValueToOriginalData($part, $data);
            }
        }
        return $value;
    }

    protected function regexMethod($_blob, Addressbook_Model_Contact $_record): Addressbook_Model_Contact
    {
        $contactData = array();
        $contactString = $_blob;
        
        foreach ($this->_config->rules->rule as $rule) {
            // use the first match
            if (isset($contactData[$rule->field])) {
                continue;
            }
            $matches = array();
            if (preg_match($rule->regex, $contactString, $matches)) {
                $contactData[$rule->field] = trim($matches[1]);
                $_record->{$rule->field} = ($_record->{$rule->field} ? $_record->{$rule->field} . ' ' : '') . trim($matches[1]);
            }
        }
        
        // remaining tokens are $this->_unrecognizedTokens
        foreach($contactData as $value) {
            $contactString = str_replace($value, '', $contactString);
        }
        $this->_unrecognizedTokens = preg_split('/[\s,]+/', $contactString);
        
        return $_record;
    }
    
    /**
    * converts Addressbook_Model_Contact to string
    *
    * @param  Tinebase_Record_Interface  $_record
    * @return string
    */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        return $_record->__toString();
    }
    
    /**
     * returns the unrecognized tokens
     * 
     * @return array
     */
    public function getUnrecognizedTokens()
    {
        return $this->_unrecognizedTokens;
    }
}
