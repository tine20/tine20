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

		$contactFields = array ( 
					'Business Contact Info' => 'separator',
			        'org_name' => 'Organisation',
			        'org_unit' => 'Organisation Unit',
					'adr_one_street' => 'Street',
			        'adr_one_street2' => 'Street 2',
			        'adr_one_postalcode' => 'Postalcode' ,
					'adr_one_locality' => 'City',
			        'adr_one_region' => 'Region',
					'adr_one_countryname' => 'Country',
			        'email' => 'Email',
					'tel_work' => 'Telephone Work',
			        'tel_cell' => 'Telephone Cellphone',
					'tel_car' => 'Telephone Car',
					'tel_fax' => 'Telephone Fax',
					'tel_pager' => 'Telephone Page',
			        'url' => 'URL',
					'role' => 'Role',
					'assistent' => 'Assistant',
			        'tel_assistent' => 'Assistant Telephone',
		
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