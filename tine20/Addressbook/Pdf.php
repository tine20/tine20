<?php
/**
 * contact pdf generation class
 *
 * @package     Addressbook
 * @subpackage	PDF
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * defines the datatype for simple registration object
 * 
 * @package     Addressbook
 * @subpackage	PDF
 */
class Addressbook_Pdf extends Tinebase_Export_Pdf
{
    /**
     * create contact pdf
     *
     * @param	Addressbook_Model_Contact contact data
     *
     * @return	string	the contact pdf
     */
    public function contactPdf ( Addressbook_Model_Contact $_contact )
    {
        $translate = new Zend_Translate('gettext', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'translations', null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        $translate->setLocale(Zend_Registry::get('locale'));
         
        $contactFields = array (
            $translate->_('Business Contact Data') => 'separator',
            'org_name' => $translate->_('Organisation'),
            'org_unit' => $translate->_('Organisation Unit'),
            'adr_one_street' => $translate->_('Street'),
            'adr_one_street2' => $translate->_('Street 2'),
            'adr_one_postalcode' => $translate->_('Postalcode'),
            'adr_one_locality' => $translate->_('City'),
            'adr_one_region' => $translate->_('Region'),
            'adr_one_countryname' => $translate->_('Country'),
            'email' => $translate->_('Email'),
            'tel_work' => $translate->_('Telephone Work'),
            'tel_cell' => $translate->_('Telephone Cellphone'),
            'tel_car' => $translate->_('Telephone Car'),
            'tel_fax' => $translate->_('Telephone Fax'),
            'tel_pager' => $translate->_('Telephone Page'),
            'url' => $translate->_('URL'),
            'role' => $translate->_('Role'),
            'assistent' => $translate->_('Assistant'),
            'tel_assistent' => $translate->_('Assistant Telephone'),
            
            $translate->_('Private Contact Data') => 'separator',
            'adr_two_street' => $translate->_('Street'),
            'adr_two_street2' => $translate->_('Street 2'),
            'adr_two_postalcode' => $translate->_('Postalcode'),
            'adr_two_locality' => $translate->_('City'),
            'adr_two_region' => $translate->_('Region'),
            'adr_two_countryname' => $translate->_('Country'),
            'email_home' => $translate->_('Email Home'),
            'tel_home' => $translate->_('Telephone Home'),
            'tel_cell_private' => $translate->_('Telephone Cellphone Private'),
            'tel_fax_home' => $translate->_('Telephone Fax Home'),
            'url_home' => $translate->_('URL Home'),
            
            $translate->_('Other Data') => 'separator',
            'bday' => $translate->_('Birthday'),
            'title' => $translate->_('Title'),
        
            //'id' => 'Contact ID',    
            //'owner' => 'Owner',
            //'n_prefix' => 'Name Prefix',
            //'n_suffix' => 'Name Suffix',
		);
        
        //@todo	include contact photo here
        $contactPhoto = Zend_Pdf_Image::imageWithPath(dirname(dirname(__FILE__)).'/images/empty_photo.jpg');		
        
        return $this->generatePdf($_contact, $_contact->n_fn, $_contact->note, $contactFields, $contactPhoto );        
	}
	

	/**
     * create contact list pdf
     *
     * @param	array Addressbook_Model_Contact contact data
     * 
     * @return	string	the contact list pdf
     * 
     * @todo	implement
     */
	public function contactListPdf ( array $_contacts )
	{
		return $this->generateListPdf($_contacts);
	}
		

}