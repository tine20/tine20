<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <kontakt@michaelspahn.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a DavDROID vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_DavDroid extends Addressbook_Convert_Contact_VCard_Abstract
{
    // DAVdroid/0.7.2
    const HEADER_MATCH = '/DAVdroid\/(?P<version>.*)/';

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
        'title'                 => null,
        'url'                   => null,
        'url_home'              => null,
        'n_family'              => null,
        'n_fileas'              => null,
        'n_given'               => null,
        'org_name'              => null,
        'org_unit'              => null,
        'tel_cell'              => null,
        'tel_fax'               => null,
        'tel_home'              => null,
        'tel_pager'             => null,
        'tel_work'              => null,
        'tags'                  => null,
        'notes'                 => null,
    );
    
    /**
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::toTine20Model()
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null, $options = array())
    {
        $contact = parent::toTine20Model($_blob, $_record, $options);
        
        if (!empty($contact->url)) {
            $contact->url = strtr($contact->url, array('http\:' => 'http:'));
        }
        if (!empty($contact->url_home)) {
            $contact->url_home = strtr($contact->url_home, array('http\:' => 'http:'));
        }
        
        return $contact;
    }

    /**
     * Convert from tine to model
     *
     * @param Tinebase_Record_Abstract $_record
     * @return \Sabre\VObject\Component
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_record->toArray(), true));
        
        // initialize vcard object
        $card = $this->_fromTine20ModelRequiredFields($_record);
        $card->add('TEL', $_record->tel_work, array('TYPE' => 'WORK'));
        $card->add('TEL', $_record->tel_home, array('TYPE' => 'HOME'));
        $card->add('TEL', $_record->tel_cell, array('TYPE' => 'CELL'));
        $card->add('TEL', $_record->tel_fax, array('TYPE' => 'FAX'));
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
