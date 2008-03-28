<?php
/**
 * abstract pdf generation class
 *
 * @package     Tinebase
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * defines the datatype for simple registration object
 * 
 * @package     Tinebase
 * @subpackage	Export
 */
abstract class Tinebase_Export_Pdf extends Zend_Pdf
{
    /**
     * @todo    add some attributes (sizes, ...)
     *
     */

    /**
     * content font size
     * 
     * @var integer
     *
     */
    protected $contentFontSize = 8;
    
    /**
     * footer font size
     * 
     * @var integer
     *
     */
    protected $footerFontSize = 10;
    
    /**
     * content line height
     * 
     * @var integer
     *
     */
    protected $contentLineHeight = 16;     
    
    /**
     * the constructor
     *
     * @param   integer $_contentFontSize
     * @param   integer $_footerFontSize
     * @param   integer $_contentLineHeight
     */
	public function __construct($_contentFontSize = NULL, $_footerFontSize = NULL, $_contentLineHeight = NULL)
	{
		parent::__construct();
		
		// add first page 
		$this->pages[] = $this->newPage(Zend_Pdf_Page::SIZE_A4); 		
		
		// set params
		if ( $_footerFontSize !== NULL ) {
			$this->footerFontSize = $_footerFontSize;
		}
        if ( $_contentFontSize !== NULL ) {
            $this->contentFontSize = $_contentFontSize;
        }
        if ( $_contentLineHeight !== NULL ) {
            $this->contentLineHeight = $_contentLineHeight;
        }
	}
	
	/**
     * create pdf
     *
     * @param	array $_record record data
     * @param	$_title	the pdf title
     * @param   $_subtitle the subtitle
     * @param	$_note		pdf note (below title)		
     * @param	$_fields	record fields that should appear in the pdf
     * @param	$_image	image for the upper right corner (i.e. contact photo)
     * 
     * @return	string	the contact pdf
     */
	public function generatePdf ( array $_record, $_title = "", $_subtitle = "", $_note = "", $_fields = array(), $_image = NULL)
	{
		$pageNumber = 0;
		$xPos = 50;
		$yPos = 800;

		// title
		if ( !empty($_title) ) {
			$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 18); 
			$this->pages[$pageNumber]->drawText($_title, $xPos, $yPos, 'UTF-8');
		}

        // subtitle
        if ( !empty($_subtitle) ) {
        	$yPos -= 20;
            $this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 15); 
            $this->pages[$pageNumber]->drawText($_subtitle, $xPos, $yPos, 'UTF-8');
        }
		
		// write note (3 lines)
		if ( !empty($_note) ) {
			$lineCharCount = 95;
			$splitString = wordwrap($_note, $lineCharCount, "\n");
			$noteArray = explode("\n",$splitString);
			if ( sizeof($noteArray) > 3 ) {
				$noteArray[2] .= "[...]";
			}
			$noteArray = array_slice ($noteArray, 0, 3);
	
			foreach ( $noteArray as $chunk ) {
				$yPos -= 20;
				$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10); 
				$this->pages[$pageNumber]->drawText( $chunk, $xPos, $yPos, 'UTF-8');
			}
		}
		
		// photo
		if ( $_image !== NULL ) {
			//$xPos += 450;
			//$yPos -= 40;
			$this->pages[$pageNumber]->drawImage($_image, $xPos+450, $yPos, $xPos+500, $yPos + 75 );
		}
				
		
		// fill data array for table
		$data = array ();
		foreach ( $_fields as $field => $label ) {
			if ( $label === 'separator' ) {
                // if 2 separators follow each other, remove the last 2 elements
                if ( sizeof($data) > 0 && $data[sizeof($data)-1][1] === 'separator' ) {
                    array_pop ( $data );
                }
				
                $data[] = array ( $field,  $label );
				
				
			} elseif ( !empty($_record[$field]) ) {
			    $data[] = array ( $label, $_record[$field] );
			}
		}
        // if 2 separators follow each other, remove the last 2 elements
        if ( sizeof($data) > 0 && $data[sizeof($data)-1][1] === 'separator' ) {
            array_pop ( $data );
        }

		// create table
		if ( !empty($data) ) {
			$this->CreateTable( array(), $data, 50, 730 );
		}
		
		// write footer
		$this->CreateFooter();
		
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
		$padding = 5;
		$marginBottom = 75;
		
		$xPos = $_posX;
		$yPos = $_posY;
		$pageNumber = $_pageNumber; 
		
        // print headline (no longer used?)
		/*if ( !empty($_headline) ) { 
            // Set headline font
			$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 14); 
			
			for ( $i=0; $i < sizeof($_headline); $i++) {
				if ( $i !== 0 && $border ) {
					$this->pages[$pageNumber]->drawLine ( $xPos, $_posY + $this->contentLineHeight, $xPos, $_posY - $padding );
					$xPos += $padding;
				}
				$this->pages[$pageNumber]->drawText($_headline[$i], $xPos, $yPos, 'UTF-8');
				$xPos += $cellWidth;	
			}
			$yPos -= $padding;
			if ( $border ) {
				$this->pages[$pageNumber]->drawLine ( $_posX, $yPos, $_posX + ($cellWidth*sizeof($_headline)), $yPos );
			}
		}*/
		
		// content
        $this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $this->contentFontSize); 
        
		foreach ( $_content as $row ) {
						
			$yPos -= $this->contentLineHeight;
			
			if ( $yPos <= $marginBottom ) {
				// add new page 
				$page = $this->newPage(Zend_Pdf_Page::SIZE_A4); 
				$this->pages[] = $page; 	
				$yPos = $_posY;
				$pageNumber++;			
				$this->pages[$pageNumber]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $this->contentFontSize);
			}
			
			$xPos = $_posX;
			for ( $i=0; $i < sizeof($row); $i++) {

	            // leave some more space between sections
	            if ( isset($row[$i+1]) && $row[$i+1] === 'separator' ) {
	            	$yPos -= 10;
	            }
				
				if ( $row[$i] === 'separator' ) {
					$this->pages[$pageNumber]->drawLine ( $_posX, $yPos - $padding, $_posX + ($cellWidth*sizeof($row)), $yPos - $padding );
					$this->pages[$pageNumber]->drawLine ( $xPos, $yPos - $padding, $xPos, $yPos - 2*$padding);
					continue;
				}
			
				if ( $i !== 0 && $border ) {
					$this->pages[$pageNumber]->drawLine ( $xPos, $yPos + $this->contentLineHeight - 2*$padding, $xPos, $yPos - 2*$padding );
					$xPos += $padding;
				}
				
				$this->pages[$pageNumber]->drawText($row[$i], $xPos, $yPos, 'UTF-8');
				$xPos += $cellWidth;
			}

		}
		
	}

	/**
     * create footer on all pages
     * 
	 */
	public function CreateFooter ()
	{

		// get translations from addressbook
		// @todo  create translation file for exports?
		$locale = Zend_Registry::get('locale');
		$translationsFile = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'Addressbook/translations';
        $translate = new Zend_Translate('gettext', $translationsFile, null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        $translate->setLocale( $locale );
		
		$xPos = 50;
		$yPos = 30;
		
		$creationDate = $translate->_('Export Date').": ".
		  Zend_Date::now()->toString(Zend_Locale_Format::getDateFormat($locale), $locale )." ".
		  Zend_Date::now()->toString(Zend_Locale_Format::getTimeFormat($locale), $locale );;

		$creationURL = $translate->_('Created by').": ";
		if ( isset($_SERVER['SERVER_NAME']) ) {
		  $creationURL .= 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
		} else {
		  $creationURL .= 'Tine 2.0';
		}
		
		for ( $i=0; $i<sizeof($this->pages); $i++ ) {
			$this->pages[$i]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $this->footerFontSize ); 
			$this->pages[$i]->drawText ( $creationDate, $xPos, $yPos);
			$yPos -= 18;
			$this->pages[$i]->drawText ( $creationURL, $xPos, $yPos);
		}
	}	

}