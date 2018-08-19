<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ingo Ratsdorf <ingo@envirology.co.nz>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a CalDAVSynchronizer vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_CalDAVSynchronizer extends Addressbook_Convert_Contact_VCard_Abstract
{
    // "CalDavSynchronizer/1.15"
    const HEADER_MATCH = '/CalDavSynchronizer\/(?P<version>\S+)/';

    protected $_emptyArray = array(
        'adr_one_countryname'   => null,
        'adr_one_locality'      => null,
        'adr_one_postalcode'    => null,
        'adr_one_region'        => null,
        'adr_one_street'        => null,
        'adr_one_street2'       => null,
        'adr_two_countryname'   => null,
        'adr_two_locality'      => null,
        'adr_two_postalcode'    => null,
        'adr_two_region'        => null,
        'adr_two_street'        => null,
        'adr_two_street2'       => null,
        'assistent'             => null,
        'bday'                  => null,
        #'calendar_uri'          => null,
        'email'                 => null,
        'email_home'            => null,
        'jpegphoto'             => null,
        #'freebusy_uri'          => null,
        'note'                  => null,
        'role'                  => null,
        'salutation'            => null,
        'title'                 => null,
        'url'                   => null,
        'url_home'              => null,
        'n_family'              => null,
        'n_fileas'              => null,
        #'n_fn'                  => null,
        'n_given'               => null,
        'n_middle'              => null,
        'n_prefix'              => null,
        'n_suffix'              => null,
        'org_name'              => null,
        'org_unit'              => null,
        #'pubkey'                => null,
        'room'                  => null,
        'tel_assistent'         => null,
        'tel_car'               => null,
        'tel_cell'              => null,
        'tel_cell_private'      => null,
        'tel_fax'               => null,
        'tel_fax_home'          => null,
        'tel_home'              => null,
        'tel_pager'             => null,
        'tel_work'              => null,
        'tel_other'             => null,
        #'tel_prefer'            => null,
        #'tz'                    => null,
        #'geo'                   => null,
        #'lon'                   => null,
        #'lat'                   => null,
        'tags'                  => null,
        'notes'                 => null,
    );

    /**
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::toTine20Model()
     */
    public function toTine20Model($_blob, Tinebase_Record_Interface $_record = null, $options = array())
    {
        $contact = parent::toTine20Model($_blob, $_record, $options);
	Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' contact (RAW) ' . print_r($_blob, true));
	Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' tine20 contact ' . print_r($contact->toArray(), true));


        if (!empty($contact->url)) {
            $contact->url = strtr($contact->url, array('http\:' => 'http:'));
        }
        if (!empty($contact->url_home)) {
            $contact->url_home = strtr($contact->url_home, array('http\:' => 'http:'));
        }

        return $contact;
    }

    /**
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::_toTine20ModelParseTel()
     */
    protected function _toTine20ModelParseTel(&$data, \Sabre\VObject\Property $property)
    {
        parent::_toTine20ModelParseTel($data, $property);

	$telfield = null;

	if (isset($property['TYPE'])) {
            // CELL
            if ($property['TYPE']->has('cell')) {
                if ($property['TYPE']->has('work')) {
                    $telField = 'tel_cell';
                } else {
                    // this will map TEL;TYPE=CELL;TYPE=PREF: to private mobile; point of discussion whether this is private or work.
                    $telField = 'tel_cell_private';
                }

            // PAGER
            } elseif ($property['TYPE']->has('pager')) {
                $telField = 'tel_pager';

            // FAX
            } elseif ($property['TYPE']->has('fax')) {
                if ($property['TYPE']->has('work')) {
                    $telField = 'tel_fax';
                } elseif ($property['TYPE']->has('home')) {
                    $telField = 'tel_fax_home';
                }

            // HOME
            } elseif ($property['TYPE']->has('home')) {
                $telField = 'tel_home';
            // WORK
            } elseif ($property['TYPE']->has('work')) {
                $telField = 'tel_work';
            // CAR
            } elseif ($property['TYPE']->has('car')) {
		// not yet supported by CalkDAVSynchronizer
                $telField = 'tel_car';
            // ASSISTENT
            } elseif ($property['TYPE']->has('assistant')) {
                // not yet supported by CalDAVSynchronizer
                $telField = 'tel_assistent';
            } else {
	    // OTHER
                $telField = 'tel_other';
            }
        }

        if (!empty($telField)) {
            $data[$telField] = $property->getValue();
        }

    }

    /**
     * parse email address field
     *
     * @param  array                           $data      reference to tine20 data array
     * @param  \Sabre\VObject\Property         $property  mail property
     * @param  \Sabre\VObject\Component\VCard  $vcard     vcard object
     */
    protected function _toTine20ModelParseEmail(&$data, \Sabre\VObject\Property $property, \Sabre\VObject\Component\VCard $vcard)
    {
        foreach ($property['TYPE'] as $typeProperty) {
            if (strtolower($typeProperty) == 'internet') {
                if (empty($data['email'])) {
                    // do not replace existing value; this will first get the primary email mapped to work
                    $data['email'] = $property->getValue();
                } else {
                    // secondary email goes to private
                    $data['email_home'] = $property->getValue();
                }
            }
        }

    }

    /**
     * converts Addressbook_Model_Contact to vcard
     *
     * @param  Addressbook_Model_Contact  $_record
     * @return string
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_record->toArray(), true));

        // initialize vcard object
        $card = $this->_fromTine20ModelRequiredFields($_record);

        $card->add('TEL', $_record->tel_work, array('TYPE' => 'WORK'));

        $card->add('TEL', $_record->tel_home, array('TYPE' => 'HOME'));

        $card->add('TEL', $_record->tel_cell, array('TYPE' => array('CELL', 'WORK')));

        $card->add('TEL', $_record->tel_cell_private, array('TYPE' => array('CELL', 'HOME')));

        $card->add('TEL', $_record->tel_fax, array('TYPE' => array('FAX', 'WORK')));

        $card->add('TEL', $_record->tel_fax_home, array('TYPE' => array('FAX', 'HOME')));

        $card->add('TEL', $_record->tel_pager, array('TYPE' => 'PAGER'));

        $card->add('TEL', $_record->tel_other, array('TYPE' => 'OTHER'));

        $card->add('ADR', array(null, $_record->adr_one_street2, $_record->adr_one_street, $_record->adr_one_locality, $_record->adr_one_region, $_record->adr_one_postalcode, $_record->adr_one_countryname), array('TYPE' => 'WORK'));

        $card->add('ADR', array(null, $_record->adr_two_street2, $_record->adr_two_street, $_record->adr_two_locality, $_record->adr_two_region, $_record->adr_two_postalcode, $_record->adr_two_countryname), array('TYPE' => 'HOME'));

        $card->add('EMAIL', $_record->email, array('TYPE' => 'WORK'));

        $card->add('EMAIL', $_record->email_home, array('TYPE' => 'HOME'));

        $card->add('URL', $_record->url, array('TYPE' => 'WORK'));

        $card->add('URL', $_record->url_home, array('TYPE' => 'HOME'));

        $card->add('NOTE', $_record->note);

        $this->_fromTine20ModelAddBirthday($_record, $card);

        $this->_fromTine20ModelAddPhoto($_record, $card);

        // CarDAVSynchronizer does not sync geo data
        //$this->_fromTine20ModelAddGeoData($_record, $card);

        $this->_fromTine20ModelAddCategories($_record, $card);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());

        return $card;
    }


}
