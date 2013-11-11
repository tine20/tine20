<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a WebDAV Collaborator vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_WebDAVCollaborator extends Addressbook_Convert_Contact_VCard_Abstract
{
    // WebDAV Collaborator/1.0
    const HEADER_MATCH = '/^WebDAV Collaborator\/(?P<version>.*)/';
    
    protected $_emptyArray = array();
    
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @param  Addressbook_Model_Contact  $_record
     * @return Sabre_VObject_Component
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
        
        $card->add('TEL', $_record->tel_work, array('TYPE' => 'WORK'));
        
        $card->add('TEL', $_record->tel_home, array('TYPE' => 'HOME'));
        
        $card->add('TEL', $_record->tel_cell, array('TYPE' => 'CELL'));
        
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
        
        $this->_fromTine20ModelAddPhoto($_record, $card);
        
        // categories
        if(isset($_record->tags) && count($_record->tags) > 0) {
            $card->add('CATEGORIES', (array) $_record->tags->name);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card;
    }
    
    /**
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::_toTine20ModelParseEmail()
     */
    protected function _toTine20ModelParseEmail(&$data, \Sabre\VObject\Property $property, \Sabre\VObject\Component\VCard $vcard)
    {
        if ($vcard->{'X-OUTLOOK-EMAIL-2'} && $vcard->{'X-OUTLOOK-EMAIL-2'}->getValue() == $property->getValue()) {
            $data['email_home'] = $property->getValue();
        } elseif ($vcard->{'X-OUTLOOK-EMAIL-3'} && $vcard->{'X-OUTLOOK-EMAIL-3'}->getValue() == $property->getValue()) {
            // we don't map email3
        } else {
            $data['email'] = $property->getValue();
        }
    }
    
}
