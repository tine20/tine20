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
 * 
 * @todo        extend Tinebase_Export_Abstract
 */


/**
 * defines the datatype for simple registration object
 * 
 * @package     Tinebase
 * @subpackage	Export
 * 
 */
abstract class Tinebase_Export_Pdf extends Zend_Pdf
{
    /**
     * page number
     * 
     * @var integer
     *
     */
    protected $_pageNumber;
    
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
    protected $footerFontSize = 7;
    
    /**
     * content line height
     * 
     * @var integer
     *
     */
    protected $contentLineHeight = 16;     
    
    /**
     * content line height
     * 
     * @var integer
     *
     */
    protected $contentBlockLineHeight = 10;   

    /**
     * zend pdf font
     */
    protected $_font = NULL;
    
    /**
     * zend pdf bold font
     */
    protected $_fontBold = NULL;
    
    /**
     * normal font type 
     */
    protected $_fontName = Zend_Pdf_Font::FONT_HELVETICA; 
    
    /**
     * bold font type
     */
    protected $_fontNameBold = Zend_Pdf_Font::FONT_HELVETICA_BOLD; 

    /**
     * embed font in pdf
     *
     * @var boolean
     */
    protected $_embedFont = TRUE;
    
    /**
     * encoding
     */
    protected $_encoding = 'UTF-8';
    //protected $_encoding = 'CP1252';
    
    /**
     * the constructor
     *
     * @param   integer $_contentFontSize
     * @param   integer $_footerFontSize
     * @param   integer $_contentLineHeight
     * @param   integer $_contentBlockLineHeight
     *      */
	public function __construct($_additionalOptions = array(), $_contentFontSize = NULL, $_footerFontSize = NULL, $_contentLineHeight = NULL, $_contentBlockLineHeight = NULL)
	{
		parent::__construct();
		
		// get config
		$config = Tinebase_Core::getConfig()->pdfexport;
		
		// add first page 
		$this->pages[] = $this->newPage(Zend_Pdf_Page::SIZE_A4); 	
		$this->_pageNumber = 0;	
		
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
        if ( $_contentBlockLineHeight !== NULL ) {
            $this->contentBlockLineHeight = $_contentBlockLineHeight;
        }
        
        // set fonts
        if (!empty($config->fontpath) && file_exists($config->fontpath)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' use font file: ' . $config->fontpath);
                         
            $boldpath = $config->get('fontboldpath', $config->fontpath);
            $embed = ($config->fontembed) ? 0 : Zend_Pdf_Font::EMBED_DONT_EMBED;
            
            // try to use ttf / type 1 / opentype / postscript fonts
            $this->_font = Zend_Pdf_Font::fontWithPath($config->fontpath, $embed);
            $this->_fontBold = Zend_Pdf_Font::fontWithPath($boldpath, $embed);
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' use zend_pdf font: ' . $this->_fontName);
            
            $this->_font = Zend_Pdf_Font::fontWithName($this->_fontName);
            $this->_fontBold = Zend_Pdf_Font::fontWithName($this->_fontNameBold);
        }
	}
		
    /**
     * create pdf
     *
     * @param   array $_record record data
     * @param   string $_title the pdf title
     * @param   string $_subtitle the subtitle
     * @param   array $_tags the tags
     * @param   string $_note      pdf note (below title)
     * @param   string $_titleIcon icon next to the title      
     * @param   Zend_Pdf_Image $_image image for the upper right corner (i.e. contact photo)
     * @param   bool $_tableBorder
     * @return  string  the contact pdf
     * 
     */
    public function generatePdf(array $_record, 
                                    $_title = "", 
                                    $_subtitle = "", 
                                    $_tags = array(),
                                    $_note = "", 
                                    $_titleIcon = "",
                                    $_image = NULL, 
                                    $_linkedObjects = array(), 
                                    $_tableBorder = true)
    {
        $xPos = 50;
        $yPos = 800;
        $yPosImage = 720;
        $translate = Tinebase_Translation::getTranslation('Tinebase');        
        
        // add page
        if (!isset($this->pages[$this->_pageNumber])) { 
            $this->pages[] = $this->newPage(Zend_Pdf_Page::SIZE_A4);
        }        
        
        // title
        if ( !empty($_title) ) {
            $this->pages[$this->_pageNumber]->setFont($this->_font, 18); 
            $this->_writeText($_title, $xPos, $yPos);
        }

        // title icon
        if ( !empty($_titleIcon) ) {
            $titleImage = dirname(dirname(dirname(__FILE__))).$_titleIcon;
            $icon = Zend_Pdf_Image::imageWithPath($titleImage);
            $this->pages[$this->_pageNumber]->drawImage( $icon, $xPos-35, $yPos-20, $xPos-3, $yPos+12 );
        }
        
        // subtitle
        if ( !empty($_subtitle) ) {
            $yPos -= 20;
            $this->pages[$this->_pageNumber]->setFont($this->_font, 15); 
            $this->_writeText($_subtitle, $xPos, $yPos);        
        }

        // tags
        if ( !empty($_tags) ) {
            $yPos -= 15;
            $this->pages[$this->_pageNumber]->setFont($this->_font, 11);
            $tagsString = $translate->_('Tags') . ": ";
            foreach ($_tags as $tag) {
                $tagsString .= $tag['name'] . ' ';
            }            
            
            $this->_writeText($tagsString, $xPos, $yPos);
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
                $this->pages[$this->_pageNumber]->setFont($this->_font, 10); 
                $this->_writeText($chunk, $xPos, $yPos);
            }
        }
        
        // photo
        if ( $_image !== NULL ) {
            //$xPos += 450;
            $this->pages[$this->_pageNumber]->drawImage( $_image, $xPos+430, $yPosImage-40, $xPos+510, $yPosImage + 80 );
        }

        // debug record
        
        // fill data array for table
        $data = array ();
        foreach ( $_record as $recordRow ) {
            if ( $recordRow['type'] === 'separator' ) {
                // if 2 separators follow each other, remove the last 2 elements
                if ( sizeof($data) > 0 && $data[sizeof($data)-1][1] === 'separator' ) {
                    array_pop ( $data );
                }
                
                $data[] = array ( $recordRow['label'], "separator" );            
                
            } elseif ( !empty($recordRow['value']) ) {
                $data[] = array ( $recordRow['label'], $recordRow['value']   );
            }
        }
        // if 2 separators follow each other, remove the last 2 elements
        if ( sizeof($data) > 0 && $data[sizeof($data)-1][1] === 'separator' ) {
            array_pop ( $data );
        }
                
        // add linked objects (i.e. contacts for lead export)
        if ( !empty($_linkedObjects) ) {
            
            // loop linked objects and remove empty rows (with empty value)
            foreach ( $_linkedObjects as $linked ) {
                if ( is_array($linked[1]) ) {
                    foreach ( $linked[1] as $value ) {
                        if ( !empty($value) && !preg_match("/^[\s]*$/",$value) ) {
                            $data[] = $linked;
                            break;
                        }
                    }
                } elseif ( !empty($linked[1]) ) {
                    $data[] = $linked;
                }                
            }            
        }
        
        // debug $data
        
        // create table
        if ( !empty($data) ) {
            $this->_CreateTable($data, 50, 710, $_tableBorder);
        }
                
        // write footer
        $this->_CreateFooter();
        
        // increase page number
        $this->_pageNumber++;        
    }			

    /**
     * create a table
     * 
     * @param   array   $_content content
     * @param   integer $_posX xpos (upper left corner)
     * @param   integer $_posY ypos (upper left corner)
     * @param   bool    $_border    activate border
     * 
     */
    protected function _CreateTable ( $_content, $_posX = 100, $_posY = 700, $_border = true )
    {
        $cellWidth = 150;
        $padding = 5;
        $marginBottom = 75;
            
        $xPos = $_posX;
        $yPos = $_posY;
                
        // content
        $this->pages[$this->_pageNumber]->setFont($this->_font, $this->contentFontSize); 
        $this->pages[$this->_pageNumber]->setLineColor( new Zend_Pdf_Color_GrayScale(0.7) );
        
        foreach ( $_content as $row ) {
                        
            $yPos -= $this->contentLineHeight;
            
            if ( $yPos <= $marginBottom ) {
                // add new page 
                $page = $this->newPage(Zend_Pdf_Page::SIZE_A4); 
                $this->pages[] = $page;     
                $yPos = $_posY;
                $this->_pageNumber++;          
                $this->pages[$this->_pageNumber]->setFont($this->_font, $this->contentFontSize);
                $this->pages[$this->_pageNumber]->setLineColor( new Zend_Pdf_Color_GrayScale(0.7) );
            }
            
            $xPos = $_posX;
            for ( $i=0; $i < sizeof($row); $i++) {

                // leave some more space between sections
                if ( isset($row[$i+1]) && ( $row[$i+1] === 'separator' || $row[$i+1] === 'headline' ) ) {
                    $yPos -= 10;
                    $this->pages[$this->_pageNumber]->setFont($this->_fontBold, $this->contentFontSize);
                } else {
                    $this->pages[$this->_pageNumber]->setFont($this->_font, $this->contentFontSize);                    
                }
                
                if ( $row[$i] === 'separator' ) {
                    if ( $_border ) {                            
                        $this->pages[$this->_pageNumber]->drawLine ( $_posX, $yPos - $padding, $_posX + ($cellWidth*sizeof($row)), $yPos - $padding );
                        $this->pages[$this->_pageNumber]->drawLine ( $xPos, $yPos - $padding, $xPos, $yPos - 2*$padding);
                    }
                    
                    if ( isset($row[$i+1]) ) {
                        $this->_drawIcon($row[$i+1], $xPos, $yPos);
                    }
                                        
                    //continue;
                    break;
                } elseif ( $row[$i] === 'headline' ) {
                    //if ( $_border ) {                            
                        $this->pages[$this->_pageNumber]->drawLine ( $_posX, $yPos - $padding, $_posX + ($cellWidth*(sizeof($row)+1)), $yPos - $padding );
                    //}
                    continue;
                }
                                
                if ( $i !== 0 && $_border ) {
                    if ( is_array($row[$i]) ) {
                        $lineHeight = sizeof($row[$i]) * $this->contentBlockLineHeight;
                    } else {
                        $lineHeight = 0;
                    }                    
                    $this->pages[$this->_pageNumber]->drawLine ( $xPos, $yPos + $this->contentLineHeight - 2*$padding, $xPos, $yPos - 2*$padding - $lineHeight);
                    $xPos += $padding;
                }
                    
                if ( is_array($row[$i]) ) {
                    $blockLineHeight = 0;
                    foreach ( $row[$i] as $text ) {
                        if (is_array($text)) {
                            $this->_drawIcon($text['icon'], $xPos, $yPos);
                        } else {
                            $yPos -= $blockLineHeight;
                            $this->_writeText($text, $xPos, $yPos); 
                            $blockLineHeight = $this->contentBlockLineHeight;                                                    
                        }
                    }
                } else {
                    $this->_writeText($row[$i], $xPos, $yPos);
                }
                
                $xPos += $cellWidth;
            }

        }
        
    }
	
	/**
     * create footer on all pages
     * 
	 */
	protected function _CreateFooter ()
	{
		// get translations from tinebase
		$translate = Tinebase_Translation::getTranslation('Tinebase');
		
		$xPos = 50;
		$yPos = 30;
		
		$creationDate = Tinebase_Translation::dateToStringInTzAndLocaleFormat(); 
		  
		$creationURL = $translate->_('Created by').": ";
		$creationURL .= 'http://www.tine20.org';
		
		for ($i=0; $i<sizeof($this->pages); $i++) {
			$this->pages[$i]->setFont($this->_font, $this->footerFontSize);
			$this->pages[$i]->setFillColor(new Zend_Pdf_Color_GrayScale(0.5));
			$this->_writeText($creationDate, $xPos, $yPos, $i);
			//$yPos -= 18;
			$xPos += 380;
            $this->_writeText($creationURL, $xPos, $yPos, $i);
		}
	}	

	/**
	 * draws a text in the pdf and checks correct encoding
	 *
	 * @param  string $_string the string to draw
	 * @param  int $_xPos
	 * @param  int $_yPos
     * @param  int $_page page number (optional)
     * @throws Tinebase_Exception_UnexpectedValue
     * 
     * @todo don't use mb_check_encoding
	 */
	protected function _writeText($_string, $_xPos, $_yPos, $_page = NULL) {
	
	    $page = ($_page !== NULL) ? $_page : $this->_pageNumber;
	    
	    if (! extension_loaded('mbstring') || mb_check_encoding($_string, $this->_encoding)) {	

	        //echo $_string;
	        
	        @$this->pages[$page]->drawText($_string, $_xPos, $_yPos, $this->_encoding);

	    } else {
	        throw new Tinebase_Exception_UnexpectedValue('Detected an illegal character in input string: ' . $_string);
	    }
	}
	
    /**
     * add notes and activities to pdf
     *
     * @param array $record
     * @param Tinebase_Record_RecordSet $_notes
     */
    protected function _addActivities($record, $_notes)
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        if (!empty($_notes)) {
            
            $noteTypes = Tinebase_Notes::getInstance()->getNoteTypes();

            $record[] = array(
                'label' => $translate->_('Activities'),
                'type'  => 'separator',
            );
            
            foreach ($_notes as $note) {
                if ($note instanceOf Tinebase_Model_Note) {
                    $noteArray = $note->toArray();
                    $noteText = (strlen($note->note) > 100) ? substr($note->note, 0, 99) . '...' : $note->note;
                    $noteType = $noteTypes[$noteTypes->getIndexById($note->note_type_id)];
    
                    $time = Tinebase_Translation::dateToStringInTzAndLocaleFormat($note->creation_time);
                      
                    $createdBy = '(' . $noteArray['created_by'] . ')';
                    $record[] = array(
                        'label' => $time,
                        'type'  => 'multiRow',
                        'value' => array(
                            array('icon' => '/' . $noteType->icon),
                            $noteText,
                            $createdBy,
                        )                
                    );
                }
            }
        }
        
        return $record;
    }
    
    /**
     * add icon
     *
     * @param string $_icon
     * @param integer $xPos
     * @param integer $yPos
     */
    protected function _drawIcon($_icon, $_xPos, $_yPos)
    {
        $iconFilename = dirname(dirname(dirname(__FILE__))).$_icon;
        // add icon
        if (is_file($iconFilename)) {
            $icon = Zend_Pdf_Image::imageWithPath($iconFilename);
            $this->pages[$this->_pageNumber]->drawImage($icon, $_xPos-170, $_yPos-6, $_xPos-154, $_yPos + 10);                            
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' icon file not found: ' . $iconFilename);
        }
    }
}