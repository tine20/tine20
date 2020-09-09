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
 * class to convert a generic vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_Generic extends Addressbook_Convert_Contact_VCard_Abstract
{
    protected $_emptyArray = array();
    
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @param  Addressbook_Model_Contact  $_record
     * @return Sabre\VObject\Component
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        // initialize vcard object
        $card = $this->_fromTine20ModelRequiredFields($_record);
        
        $card->add('TEL', $_record->tel_work, array('TYPE' => 'WORK'));
        
        $card->add('TEL', $_record->tel_home, array('TYPE' => 'HOME'));
        
        $card->add('TEL', $_record->tel_cell, array('TYPE' => array('CELL', 'WORK')));
        
        $card->add('TEL', $_record->tel_cell_private, array('TYPE' => array('CELL', 'HOME')));

        $card->add('TEL', $_record->tel_fax, array('TYPE' => array('FAX', 'WORK')));
        
        $card->add('TEL', $_record->tel_fax_home, array('TYPE' => array('FAX', 'HOME')));
        
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
           __METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card;
    }
    
}
