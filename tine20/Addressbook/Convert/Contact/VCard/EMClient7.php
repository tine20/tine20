<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Johannes Nohl <lab@nohl.eu>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a eM Client 7 (beta) vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_EMClient7 extends Addressbook_Convert_Contact_VCard_Abstract //Addressbook_Convert_Contact_VCard_EMClient
{
    // eM Client 7 (release candidate) user agent is "eM Client/7.0.26128.0" (beta was "MailClient/7.0.25432.0")
    const HEADER_MATCH = '/eM Client\/(?P<version>.*)/';

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
        'bday'                  => null,
        'email'                 => null,
        'email_home'            => null,
        'jpegphoto'             => null,
        'note'                  => null,
        'salutation'            => null,
        'title'                 => null,
        'url'                   => null,
        'url_home'              => null,
        'n_family'              => null,
        'n_fileas'              => null,
        'n_fn'                  => null,
        'n_given'               => null,
        'n_middle'              => null,
        'org_name'              => null,
        'org_unit'              => null,
        'tel_cell'              => null,
        'tel_cell_private'      => null,
        'tel_fax'               => null,
        'tel_fax_home'          => null,
        'tel_home'              => null,
        'tel_work'              => null,
        'tel_work'              => null,
        'lon'                   => null,
        'lat'                   => null,
        'tags'                  => null,
    );

    protected $countries = null;

/*
 * === divergences to Abstract.php ===
 */

    /**
     * converts Addressbook_Model_Contact to vcard
     *
     * @param  Addressbook_Model_Contact  $_record
     * @return \Sabre\VObject\Component\VCard
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        $card = $this->_fromTine20ModelRequiredFields($_record);

        $card->add('TEL', $_record->tel_work, array('TYPE' => array('WORK', 'VOICE')));
        $card->add('TEL', $_record->tel_home, array('TYPE' => array('HOME', 'VOICE')));
        $card->add('TEL', $_record->tel_cell, array('TYPE' => 'CELL'));
        $card->add('TEL', $_record->tel_cell_private, array('TYPE' => 'OTHER'));
        $card->add('TEL', $_record->tel_fax, array('TYPE' => array('FAX', 'WORK')));
        $card->add('TEL', $_record->tel_fax_home, array('TYPE' => array('FAX', 'HOME')));

        $card->add('ADR', array(
            null, $_record->adr_one_street2, $_record->adr_one_street, $_record->adr_one_locality, 
            $_record->adr_one_region, $_record->adr_one_postalcode, 
            $this->_fromTine20ParseCountry($_record->adr_one_countryname)), 
            array('TYPE' => 'WORK')
        );
        $card->add('ADR', array(
            null, $_record->adr_two_street2, $_record->adr_two_street, $_record->adr_two_locality, 
            $_record->adr_two_region, $_record->adr_two_postalcode, 
            $this->_fromTine20ParseCountry($_record->adr_two_countryname)), 
            array('TYPE' => 'HOME')
        );

        $card->add('EMAIL', $_record->email, array('TYPE' => 'PREF'));
        $card->add('EMAIL', $_record->email_home, array('TYPE' => 'HOME'));

        $card->add('URL', $_record->url);

        $card->add('NOTE', $_record->note);

        $this->_fromTine20ModelAddBirthday($_record, $card);
        $this->_fromTine20ModelAddPhoto($_record, $card);
        $this->_fromTine20ModelAddGeoData($_record, $card);
        $this->_fromTine20ModelAddCategories($_record, $card);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());

        return $card;
    }

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
        $contact = parent::toTine20Model($blob, $_record, $options);
        $contact['adr_two_countryname'] = $this->_toTine20ParseCountry($contact['adr_two_countryname']);
        $contact['adr_one_countryname'] = $this->_toTine20ParseCountry($contact['adr_one_countryname']);
        return $contact;
    }

    /**
     * (non-PHPdoc)
     */
    protected function _fromTine20ParseCountry($code)
    {
        $translatedCountry = $code;
        if ($this->countries === null) $this->countries = Tinebase_Translation::getCountryList()['results'];
        foreach($this->countries as $countries) {
            if ($countries['shortName'] == $code) {
                $translatedCountry = $countries['translatedName'];
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                     Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' country code ' . $code . ' changed to full name ' . $translatedCountry);
                break;
            }
        }
        return $translatedCountry;
    }

    /**
     * (non-PHPdoc)
     */
    protected function _toTine20ParseCountry($country)
    {
        if (strlen($country) < 3) return $country;

        $translatedCode = $country;
        if ($this->countries === null) $this->countries = Tinebase_Translation::getCountryList()['results'];
        foreach($this->countries as $countries) {
            if (strcasecmp($countries['translatedName'], $country) === 0) {
                $translatedCode = $countries['shortName'];
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                     Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' country name ' . $country . ' changed to its iso code ' . $translatedCode);
                break;
            }
        }
        return $translatedCode;
    }

    /**
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::_toTine20ModelParseEmail()
     */
    protected function _toTine20ModelParseEmail(&$data, \Sabre\VObject\Property $property, \Sabre\VObject\Component\VCard $vcard)
    {
        /*
         *  eM Client supports three types of mail
         */
        if ($property['TYPE']->has('home')) {
            $data['email_home'] = $property->getValue();
        } elseif ($property['TYPE']->has('work') && ($data['email'] == '')) {
            $data['email'] = $property->getValue();
        } elseif ($property['TYPE']->has('pref') || ($data['email'] == '')) {
            $data['email'] = $property->getValue();
        }
    }

    /**
     * parse telephone
     *
     * @param array $data
     * @param \Sabre\VObject\Property $property
     */
    protected function _toTine20ModelParseTel(&$data, \Sabre\VObject\Property $property)
    {
        parent::_toTine20ModelParseTel($data, $property);

        if ($property['TYPE']->has('other')) {
            $data['tel_cell_private'] = $property->getValue();
        }
    }

}
