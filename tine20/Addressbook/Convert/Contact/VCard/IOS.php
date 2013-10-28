<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert an iOS vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_IOS extends Addressbook_Convert_Contact_VCard_Abstract
{
    // iOS/5.0.1 (9A405) dataaccessd/1.0
    const HEADER_MATCH = '/(iPhone|iOS)\/(?P<version>\S+)/';
    
    protected $_emptyArray = array(
        'adr_one_countryname'   => null,
        'adr_one_locality'      => null,
        'adr_one_postalcode'    => null,
        #'adr_one_region'        => null,
        'adr_one_street'        => null,
        #'adr_one_street2'       => null,
        'adr_two_countryname'   => null,
        'adr_two_locality'      => null,
        'adr_two_postalcode'    => null,
        #'adr_two_region'        => null,
        'adr_two_street'        => null,
        #'adr_two_street2'       => null,
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
     * @see Addressbook_Convert_Contact_VCard_Abstract::_toTine20ModelParseTel()
     */
    protected function _toTine20ModelParseTel(&$data, \Sabre\VObject\Property $property)
    {
        $telField = null;
        
        if (isset($property['TYPE'])) {
            // CELL
            if ($property['TYPE']->has('cell') && $property['TYPE']->has('voice') && !$property['TYPE']->has('iphone')) {
                $telField = 'tel_cell';
            } elseif ($property['TYPE']->has('cell') && $property['TYPE']->has('iphone')) {
                $telField = 'tel_cell_private';
            }
        }
        
        if (!empty($telField)) {
            $data[$telField] = $property->getValue();
        } else {
            parent::_toTine20ModelParseTel($data, $property);
        }
    }
    
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @param  Addressbook_Model_Contact  $_record
     * @return \Sabre\VObject\Component\VCard
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_record->toArray(), true));
        
        $card = new \Sabre\VObject\Component\VCard();
        
        // required vcard fields
        $this->_fromTine20ModelRequiredFields($_record, $card);
        
        // optional fields
        $card->add('ORG', array($_record->org_name, $_record->org_unit));
        
        $card->add('TITLE', $_record->title);
        
        $card->add('TEL', $_record->tel_work, array('TYPE' => array('WORK', 'VOICE')));
        
        $card->add('TEL', $_record->tel_home, array('TYPE' => array('HOME', 'VOICE')));
        
        $card->add('TEL', $_record->tel_cell, array('TYPE' => array('CELL', 'VOICE', 'PREF')));
        
        $card->add('TEL', $_record->tel_cell_private, array('TYPE' => array('CELL', 'IPHONE')));
        
        $card->add('TEL', $_record->tel_fax, array('TYPE' => array('FAX', 'WORK')));
        
        $card->add('TEL', $_record->tel_fax_home, array('TYPE' => array('FAX', 'HOME')));
        
        $card->add('TEL', $_record->tel_pager, array('TYPE' => 'PAGER'));
        
        $card->add('ADR', array(null, $_record->adr_one_street2, $_record->adr_one_street, $_record->adr_one_locality, $_record->adr_one_region, $_record->adr_one_postalcode, $_record->adr_one_countryname), array('TYPE' => 'WORK'));
        
        $card->add('ADR', array(null, $_record->adr_two_street2, $_record->adr_two_street, $_record->adr_two_locality, $_record->adr_two_region, $_record->adr_two_postalcode, $_record->adr_two_countryname), array('TYPE' => 'HOME'));
        
        $card->add('EMAIL', $_record->email, array('TYPE' => array('INTERNET', 'WORK', 'PREF')));
        
        $card->add('EMAIL', $_record->email_home, array('TYPE' => array('INTERNET', 'HOME')));
        
        $card->add('URL', $_record->url, array('TYPE' => 'WORK'));
        
        $card->add('URL', $_record->url_home, array('TYPE' => 'HOME'));
        
        $card->add('NOTE', $_record->note);
        
        $this->_fromTine20ModelAddPhoto($_record, $card);
        
        $this->_fromTine20ModelAddGeoData($_record, $card);
        
        // categories
        if (isset($_record->tags) && count($_record->tags) > 0) {
            $card->add('CATEGORIES', (array) $_record->tags->name);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card;
    }
}
