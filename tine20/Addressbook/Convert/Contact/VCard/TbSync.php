<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Maximilian Schwerin <post@mschwerin.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class to convert a TbSync vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_TbSync extends Addressbook_Convert_Contact_VCard_Abstract
{
    // Thunderbird CalDAV/CardDAV
    // Thunderbird CalDAV/CardDAV Lightning/68.10.0
    const HEADER_MATCH = '/Thunderbird CalDAV\/CardDAV/';

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
        #'assistent'             => null,
        'bday'                  => null,
        #'calendar_uri'          => null,
        'email'                 => null,
        'email_home'            => null,
        'jpegphoto'             => null,
        #'freebusy_uri'          => null,
        'note'                  => null,
        #'role'                  => null,
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
        #'room'                  => null,
        #'tel_assistent'         => null,
        #'tel_car'               => null,
        'tel_cell'              => null,
        'tel_cell_private'      => null,
        'tel_fax'               => null,
        'tel_fax_home'          => null,
        'tel_home'              => null,
        'tel_pager'             => null,
        'tel_work'              => null,
        #'tel_other'             => null,
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

        return $contact;
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

        $card->add('TEL', $_record->tel_work, array('TYPE' => array('VOICE', 'WORK')));

        $card->add('TEL', $_record->tel_home, array('TYPE' => array('VOICE', 'HOME')));

        $card->add('TEL', $_record->tel_cell, array('TYPE' => array('CELL', 'WORK')));

        $card->add('TEL', $_record->tel_cell_private, array('TYPE' => array('CELL', 'HOME')));

        $card->add('TEL', $_record->tel_fax, array('TYPE' => array('FAX', 'WORK')));

        $card->add('TEL', $_record->tel_fax_home, array('TYPE' => array('FAX', 'HOME')));

        $card->add('TEL', $_record->tel_pager, array('TYPE' => 'PAGER'));

        $card->add('ADR', array(null, $_record->adr_one_street2, $_record->adr_one_street, $_record->adr_one_locality, $_record->adr_one_region, $_record->adr_one_postalcode, $_record->adr_one_countryname), array('TYPE' => 'WORK'));

        $card->add('ADR', array(null, $_record->adr_two_street2, $_record->adr_two_street, $_record->adr_two_locality, $_record->adr_two_region, $_record->adr_two_postalcode, $_record->adr_two_countryname), array('TYPE' => 'HOME'));

        $card->add('EMAIL', $_record->email, array('TYPE' => 'WORK'));

        $card->add('EMAIL', $_record->email_home, array('TYPE' => 'HOME'));

        $card->add('URL', $_record->url, array('TYPE' => 'WORK'));

        $card->add('URL', $_record->url_home, array('TYPE' => 'HOME'));

        $card->add('NOTE', $_record->note);

        $this->_fromTine20ModelAddBirthday($_record, $card);

        $this->_fromTine20ModelAddPhoto($_record, $card);

        $this->_fromTine20ModelAddGeoData($_record, $card);

        $this->_fromTine20ModelAddCategories($_record, $card);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());

        return $card;
    }


}
