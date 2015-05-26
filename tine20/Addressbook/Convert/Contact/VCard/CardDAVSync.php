<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
2015/05/12 by Ingo Ratsdorf ingo@envirology.co.nz
CardDAVSync send the following data ONLY:

BEGIN:VCARD
VERSION:3.0
FN:Ratsdorf\, Janus
N:Ratsdorf;Janus;;;
UID:56e09493b665c30dfbc3de103b20e424d1c709a6
ORG:;
TEL;TYPE=WORK:
TEL;TYPE=HOME:+64 9 411 8 444
TEL;TYPE=HOME;TYPE=CELL:+64 27 345 1070
TEL;TYPE=FAX:
TEL;TYPE=FAX;TYPE=HOME:
TEL;TYPE=PAGER:
TEL;TYPE=OTHER:
ADR;TYPE=WORK:;;;;;;
ADR;TYPE=HOME:;;35 Taylor Road;Auckland;Waimauku;0882;NEW ZEALAND
EMAIL;TYPE=WORK:
EMAIL;TYPE=HOME:janus@envirology.co.nz
URL;TYPE=WORK:https://www.facebook.com/janus.ratsdorf
URL;TYPE=HOME:
NOTE:
BDAY:2001-09-02
PHOTO;ENCODING=B;TYPE=JPEG: [..]
TEL:0000000985
itemtel333336888.TEL;TYPE=VOICE:333336888
itemtel333336888.X-ABLABEL:assistant
TEL;TYPE=PAGER:114114469
TEL:
PRODID:-//dmfs.org//mimedir.vcard//EN
REV:20150512T080704Z
END:VCARD

*/


/**
 * class to convert a SOGO vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_CardDAVSync extends Addressbook_Convert_Contact_VCard_Abstract
{
    // CardDAV-Sync free/0.4.12 (samsung; kltedv; Android 4.4.2; en_NZ; org.dmfs.carddav.sync/99)
    const HEADER_MATCH = '/(CardDAV-Sync|CardDAV-Sync free)\/(?P<version>.*)/';
    
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
        #'salutation'            => null,
        'title'                 => null,
        'url'                   => null,
        'url_home'              => null,
        'n_family'              => null,
        'n_fileas'              => null,
        #'n_fn'                  => null,
        'n_given'               => null,
        #'n_middle'              => null,
        #'n_prefix'              => null,
        #'n_suffix'              => null,
        'org_name'              => null,
        'org_unit'              => null,
        #'pubkey'                => null,
        #'room'                  => null,
        'tel_assistent'         => null,
        #'tel_car'               => null,
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
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null, $options = array())
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
        if (!isset($property['TYPE'])) {
            // CardDAVSync sends OTHER just as TEL:12345678 without any TYPE
            $data['tel_other'] = $property->getValue();
        }

        parent::_toTine20ModelParseTel($data, $property);

    }

    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @param  Addressbook_Model_Contact  $_record
     * @return string
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

        $card->add('TEL', $_record->tel_cell_private, array('TYPE' => array('CELL', 'HOME')));

        $card->add('TEL', $_record->tel_fax, array('TYPE' => array('FAX')));

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
        
        $this->_fromTine20ModelAddGeoData($_record, $card);
        
        //$this->_fromTine20ModelAddCategories($_record, $card);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card;
    }
    

}
