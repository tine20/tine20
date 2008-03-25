<?php
/**
 * contact pdf generation class
 *
 * @package     Addressbook
 * @subpackage	PDF
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * defines the datatype for simple registration object
 * 
 * @package     Addressbook
 * @subpackage	PDF
 */
class Addressbook_Pdf extends Zend_Pdf
{
	
	/**
     * the constructor
     *
     */
	public function __construct()
	{
		parent::__construct();
		
		// add first page 
		$this->pages[] = $this->newPage(Zend_Pdf_Page::SIZE_A4); 		
	}
	
	/**
     * create contact pdf
     *
     * @param	Addressbook_Model_Contact contact data
     * 
     * @return	string	the contact pdf
     */
	public function contactPdf ( Addressbook_Model_Contact $_contact )
	{
		$pageNumber = 0;
		$xPos = 50;
		$yPos = 800;

		// name
		$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 18); 
		$this->pages[$pageNumber]->drawText($_contact->n_fn, $xPos, $yPos);

		// note
		$yPos -= 20;
		$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10); 
		$this->pages[$pageNumber]->drawText($_contact->note, $xPos, $yPos);
		
		// photo
		//@todo	include contact photo here
		$xPos += 450;
		$yPos -= 40;
		$image = Zend_Pdf_Image::imageWithPath('images/empty_photo.jpg');		
		$this->pages[$pageNumber]->drawImage($image, $xPos, $yPos, $xPos+50, $yPos + 75 );
		
		$contactFields = array ( 
					'Business Address' => 'separator',
					'adr_one_countryname' => 'Country',
			        'adr_one_locality' => 'Locality',
			        'adr_one_postalcode' => 'Postalcode' ,
			        'adr_one_region' => 'Region',
			        'adr_one_street' => 'Street',
			        'adr_one_street2' => 'Street 2',
					'Private Address' => 'separator',
					'adr_two_countryname' => 'Country',
			        'adr_two_locality' => 'Locality',
			        'adr_two_postalcode' => 'Postalcode',
			        'adr_two_region' => 'Region',
			        'adr_two_street' => 'Country',
			        'adr_two_street2' => 'Country 2',
					'Other Infos' => 'separator',
					'assistent' => 'Assistant',
			        'bday' => 'Birthday',
			        'email' => 'Email',
			        'email_home' => 'Email Home',
			        'id' => 'ID',
			        //'owner' => 'Owner',
			        'role' => 'Role',
			        'title' => 'Title',
			        'url' => 'URL',
			        'url_home' => 'URL Home',
			        //'n_family' => 'Family Name',
			        //'n_fileas' => 'File As',
			        'n_prefix' => 'Name Prefix',
			        'n_suffix' => 'Name Suffix',
			        'org_name' => 'Organisation',
			        'org_unit' => 'Organisation Unit',
			        'tel_assistent' => 'Assistant Telephone',
			        'tel_car' => 'Telephone Car',
			        'tel_cell' => 'Telephone Cellphone',
			        'tel_cell_private' => 'Telephone Cellphone Private',
			        'tel_fax' => 'Telephone Fax',
			        'tel_fax_home' => 'Telephone Fax Home',
			        'tel_home' => 'Telephone Home',
			        'tel_pager' => 'Telephone Page',
			        'tel_work' => 'Telephone Work',
		);
		
		// fill data array
		$contactData = array ();
		foreach ( $contactFields as $field => $label ) {
			//$contactData[$field] = $_contact->$field;
			if ( $label === 'separator' ) {
				$contactData[] = array ( $field,  $label );
			} elseif ( !empty($_contact->$field) ) {
				$contactData[] = array ( $label,  $_contact->$field );
			}
		}
		
		// create table
		$this->CreateTable( array(), $contactData, 75, 750 );
		
		// Get PDF document as a string 
		$pdfData = $this->render(); 
		
		return $pdfData; 		
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
		$pageNumber = 0;
		$xPos = 50;
		$yPos = 800;

		$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 18); 
		$this->pages[$pageNumber]->drawText("Contacts", $xPos, $yPos);
		
		// Get PDF document as a string 
		$pdfData = $this->render(); 
		
		return $pdfData; 		
	}
		
	/**
     * create a table
     * 
     * @param 	array	headline fields
     * @param	array	content
     * @param 	integer	xpos (upper left corner)
     * @param 	integer	ypos (upper left corner)
     * @param	integer	pagenumber for table
     * @param	bool	activate border
     * 
     */
	public function CreateTable ( $_headline, $_content, $_posX = 100, $_posY = 700, $_pageNumber = 0, $border = true )
	{
		$cellWidth = 150;
		$cellHeight = 30; 
		$padding = 5;
		$marginBottom = 75;
		$xPos = $_posX;
		$yPos = $_posY;
		$pageNumber = $_pageNumber; 
		
		// Set headline font 
		$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 14); 
		
		// headline
		for ( $i=0; $i < sizeof($_headline); $i++) {
			if ( $i !== 0 && $border ) {
				$this->pages[$pageNumber]->drawLine ( $xPos, $_posY + $cellHeight, $xPos, $_posY - $padding );
				$xPos += $padding;
			}
			$this->pages[$pageNumber]->drawText($_headline[$i], $xPos, $yPos);
			$xPos += $cellWidth;	
		}
		$yPos -= $padding;
		if ( $border ) {
			$this->pages[$pageNumber]->drawLine ( $_posX, $yPos, $_posX + ($cellWidth*sizeof($_headline)), $yPos );
		}
		
		$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12); 
		
		// content
		foreach ( $_content as $row ) {
			$yPos -= $cellHeight;
			
			if ( $yPos <= $marginBottom ) {
				// add new page 
				$page = $this->newPage(Zend_Pdf_Page::SIZE_A4); 
				$this->pages[] = $page; 	
				$yPos = $_posY;
				$pageNumber++;			
				$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);
				//$this->CreateHeader($pageNumber);				
			}
			
			$xPos = $_posX;
			for ( $i=0; $i < sizeof($row); $i++) {
				
				if ( $row[$i] === 'separator' ) {
					$this->pages[$pageNumber]->drawLine ( $_posX, $yPos - $padding, $_posX + ($cellWidth*sizeof($row)), $yPos - $padding );
					continue;
				}
			
				if ( $i !== 0 && $border ) {
					$this->pages[$pageNumber]->drawLine ( $xPos, $yPos + $cellHeight - 10, $xPos, $yPos - 10 );
					$xPos += $padding;
				}
				
				$this->pages[$pageNumber]->drawText($row[$i], $xPos, $yPos, 'UTF-8');
				$xPos += $cellWidth;
			}

		}
		
	}
	
	/********************** old functions follow ********************/
	
	/**
     * create a table
     * 
     * @param 	integer	page number
     * 
     * 
	 */
	public function CreateHeader ( $_pageNumber = 0 )
	{
		$yPos = 760;
		$xPos = 50;
		$headerText = "Header Text";

		// load image (logo)
		$image = Zend_Pdf_Image::imageWithPath('logo.jpg');		
		$this->pages[$_pageNumber]->drawImage($image, $xPos, $yPos, $xPos + $image->getPixelWidth(), $yPos + $image->getPixelHeight() );
		
		// set font & add text
		$this->pages[$_pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12); 
		$this->pages[$_pageNumber]->drawText($headerText, $xPos, $yPos - 30);
		
	}
	
	/**
     * create a table
     * 
     * @param 	integer	page number
     * 
     * @todo	implement!
	 */
	public function CreateFooter ( $_pageNumber = 0 )
	{
		
	}
}