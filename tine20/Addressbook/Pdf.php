<?php
/**
 * contact pdf generation class
 *
 * @package     Addressbook
 * @subpackage	PDF
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
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

		$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 18); 
		$this->pages[$pageNumber]->drawText($_contact->n_fn, $xPos, $yPos);
		
		// Get PDF document as a string 
		$pdfData = $this->render(); 
		
		return $pdfData; 		
	}
	
	/********************** old functions follow ********************/
	
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
		$cellWidth = 120;
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
				$this->CreateHeader($pageNumber);				
			}
			
			$xPos = $_posX;
			for ( $i=0; $i < sizeof($row); $i++) {
			
				if ( $i !== 0 && $border ) {
					$this->pages[$pageNumber]->drawLine ( $xPos, $yPos + $cellHeight, $xPos, $yPos );
					$xPos += $padding;
				}
				
				$this->pages[$pageNumber]->drawText($row[$i], $xPos, $yPos);
				$xPos += $cellWidth;
			}

		}
		
	}
	
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