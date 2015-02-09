<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <kontakt@michaelspahn.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a telefonbuch (http://www.dastelefonbuch.de/) vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_Telefonbuch extends Addressbook_Convert_Contact_VCard_Abstract
{
    protected $_emptyArray = array();

    /**
     * converts vcard to Addressbook_Model_Contact
     *
     * @param  \Sabre\VObject\Component|stream|string  $blob       the vcard to parse
     * @param  Tinebase_Record_Abstract                $_record    update existing contact
     * @param  array                                   $options    array of options
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($blob, Tinebase_Record_Abstract $_record = null, $options = array())
    {
        $vcard = self::getVObject($blob);

        if ($_record instanceof Addressbook_Model_Contact) {
            $contact = $_record;
        } else {
            $contact = new Addressbook_Model_Contact(null, false);
        }

        $data = $this->_emptyArray;

        foreach ($vcard->children() as $property) {
            switch ($property->name) {
                case 'VERSION':
                case 'PRODID':
                case 'UID':
                    // do nothing
                    break;

                case 'ADR':
                    $parts = $property->getParts();

                    // work address
                    $data['adr_one_street2']     = $parts[1];
                    $data['adr_one_street']      = $parts[2];
                    $data['adr_one_locality']    = $parts[3];
                    $data['adr_one_region']      = $parts[4];
                    $data['adr_one_postalcode']  = $parts[5];
                    $data['adr_one_countryname'] = $parts[6];
                    break;

                case 'CATEGORIES':
                    $tags = Tinebase_Model_Tag::resolveTagNameToTag($property->getParts(), 'Addressbook');
                    if (! isset($data['tags'])) {
                        $data['tags'] = $tags;
                    } else {
                        $data['tags']->merge($tags);
                    }
                    break;

                case 'EMAIL':
                    $this->_toTine20ModelParseEmail($data, $property, $vcard);
                    break;

                case 'FN':
                    $data['n_fn'] = $property->getValue();
                    $data['n_given'] = $data['n_fn'];
                    break;

                case 'N':
                    $parts = $property->getParts();

                    $data['n_family'] = $parts[0];
                    $data['n_middle'] = isset($parts[2]) ? $parts[2] : null;
                    $data['n_prefix'] = isset($parts[3]) ? $parts[3] : null;
                    $data['n_suffix'] = isset($parts[4]) ? $parts[4] : null;
                    break;

                case 'NOTE':
                    $data['note'] = $property->getValue();
                    break;

                case 'ORG':
                    $parts = $property->getParts();

                    $data['org_name'] = $parts[0];
                    $data['org_unit'] = isset($parts[1]) ? $parts[1] : null;
                    break;

                case 'PHOTO':
                    $data['jpegphoto'] = $property->getValue();
                    break;

                case 'TEL':
                    $this->_toTine20ModelParseTel($data, $property);
                    break;

                case 'URL':
                    switch (strtoupper($property['TYPE'])) {
                        case 'HOME':
                            $data['url_home'] = $property->getValue();
                            break;

                        case 'WORK':
                        default:
                            $data['url'] = $property->getValue();
                            break;
                    }
                    break;

                case 'TITLE':
                    $data['title'] = $property->getValue();
                    break;

                case 'BDAY':
                    $this->_toTine20ModelParseBday($data, $property);
                    break;

                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                    break;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($data, true));

        // Some email clients will only set a contact with FN (formatted name) without surname
        if (empty($data['n_family']) && empty($data['org_name']) && (!empty($data['n_fn']))) {
            if (strpos($data['n_fn'], ",") > 0) {
                list($lastname, $firstname) = explode(",", $data['n_fn'], 2);
                $data['n_family'] = trim($lastname);
                $data['n_given']  = trim($firstname);

            } elseif (strpos($data['n_fn'], " ") > 0) {
                list($firstname, $lastname) = explode(" ", $data['n_fn'], 2);
                $data['n_family'] = trim($lastname);
                $data['n_given']  = trim($firstname);

            } else {
                $data['n_family'] = $data['n_fn'];
            }
        }

        $contact->setFromArray($data);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($contact->toArray(), true));

        if (isset($options[self::OPTION_USE_SERVER_MODLOG]) && $options[self::OPTION_USE_SERVER_MODLOG] === true) {
            $contact->creation_time = $_record->creation_time;
            $contact->last_modified_time = $_record->last_modified_time;
            $contact->seq = $_record->seq;
        }

        return $contact;
    }
}
