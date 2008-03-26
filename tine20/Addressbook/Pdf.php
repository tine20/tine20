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
					'Business Contact Info' => 'separator',
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
		
					'Private Contact Info' => 'separator',
			        'adr_two_street' => 'Street',
			        'adr_two_street2' => 'Street 2',
			        'adr_two_postalcode' => 'Postalcode',
					'adr_two_locality' => 'City',
			        'adr_two_region' => 'Region',
					'adr_two_countryname' => 'Country',
			        'email_home' => 'Email Home',
			        'tel_home' => 'Telephone Home',
					'tel_cell_private' => 'Telephone Cellphone Private',
			        'tel_fax_home' => 'Telephone Fax Home',
			        'url_home' => 'URL Home',
		
			        'Other Infos' => 'separator',
			        'bday' => 'Birthday',
			        'title' => 'Title',
					'id' => 'Contact ID',
		
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