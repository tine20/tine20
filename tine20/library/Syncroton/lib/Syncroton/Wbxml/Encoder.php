<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  Wbxml
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:Encoder.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class to convert XML to WBXML
 *
 * @package     Wbxml
 * @subpackage  Wbxml
 */
 
class Syncroton_Wbxml_Encoder extends Syncroton_Wbxml_Abstract
{
    /**
     * stack of dtd objects
     *
     * @var array
     */
    protected $_dtdStack = array();
    
    /**
     * stack of stream resources
     *
     * @var array
     */
    protected $_streamStack = array();
    
    /**
     * stack of levels when to pop data from the other stacks
     *
     * @var array
     */
    protected $_popStack = array();
        
    /**
     * count level of tags
     *
     * @var string
     */
    protected $_level = 0;
    
    /**
     * when to take data next time from the different stacks
     *
     * @var unknown_type
     */
    protected $_nextStackPop = NULL;
    
    /**
     * collect data trough different calls to _handleCharacters
     *
     * @var string
     */
    protected $_currentTagData = NULL;
    
    /**
     * the current tag as read by the parser
     *
     * @var string
     */
    protected $_currentTag = NULL;
    
    /**
     * the constructor
     *
     * @param resource $_stream
     * @param string $_charSet
     * @param integer $_version
     */
    public function __construct($_stream, $_charSet = 'UTF-8', $_version = 2)
    {
        $this->_stream = $_stream;
        $this->_charSet = $_charSet;
        $this->_version = $_version;
    }

    /**
     * initialize internal variables and write wbxml header to stream
     *
     * @param string $_urn
     * @todo check if dpi > 0, instead checking the urn
     */
    protected function _initialize($_dom)
    {
        $this->_dtd = Syncroton_Wbxml_Dtd_Factory::factory($_dom->doctype->name);
        $this->_codePage = $this->_dtd->getCurrentCodePage();
        
        // the WBXML version
        $this->_writeByte($this->_version);
        
        if($this->_codePage->getDPI() === NULL) {
            // the document public identifier
            $this->_writeMultibyteUInt(1);
        } else {
            // the document public identifier
            // defined in string table
            $this->_writeMultibyteUInt(0);
            // the offset of the DPI in the string table
            $this->_writeByte(0);
        }        
        
        // write the charSet
        $this->_writeCharSet($this->_charSet);
        
        if($this->_codePage->getDPI() === NULL) {
            // the length of the string table
            $this->_writeMultibyteUInt(0);
        } else {
            // the length of the string table
            $this->_writeMultibyteUInt(strlen($this->_codePage->getDPI()));
            // the dpi
            $this->_writeString($this->_codePage->getDPI());      
        }  
    }
    
    /**
     * write charset to stream
     *
     * @param string $_charSet
     * @todo add charset lookup table. currently only utf-8 is supported
     */
    protected function _writeCharSet($_charSet)
    {
        switch(strtoupper($_charSet)) {
            case 'UTF-8':
                $this->_writeMultibyteUInt(106);
                break;
                
            default:
                throw new Syncroton_Wbxml_Exception('unsuported charSet ' . strtoupper($_charSet));
                break;
        }
        
    }
    
    /**
     * start encoding of xml to wbxml
     *
     * @param string $_xml the xml string
     * @return resource stream
     */
    public function encode(DOMDocument $_dom)
    {
        $_dom->formatOutput = false;
        
        $this->_initialize($_dom);
        
        $parser = xml_parser_create_ns($this->_charSet, ';');
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, '_handleStartTag', '_handleEndTag');
        xml_set_character_data_handler($parser, '_handleCharacters');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        
        if (!xml_parse($parser, $_dom->saveXML())) {
            #file_put_contents(tempnam(sys_get_temp_dir(), "xmlerrors"), $_dom->saveXML());
            throw new Syncroton_Wbxml_Exception(sprintf('XML error: %s at line %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser)
            ));
        }

        xml_parser_free($parser);
    }

    /**
     * get's called by xml parser when tag starts
     *
     * @param resource $_parser
     * @param string $_tag current tag prefixed with namespace
     * @param array $_attributes list of tag attributes
     */
    protected function _handleStartTag($_parser, $_tag, $_attributes)
    {
        $this->_level++;
        $this->_currentTagData = null;
        
        // write data for previous tag happens whith <tag1><tag2>
        if($this->_currentTag !== NULL) {
            $this->_writeTag($this->_currentTag, $this->_attributes, true);
        }

        list($nameSpace, $this->_currentTag) = explode(';', $_tag);

        if($this->_codePage->getNameSpace() != $nameSpace) {
            $this->_switchCodePage($nameSpace);
        }

        $this->_attributes = $_attributes;
        
    }
    
    /**
     * strip uri: from nameSpace
     *
     * @param unknown_type $_nameSpace
     * @return unknown
     */
    protected function _stripNameSpace($_nameSpace)
    {
        return substr($_nameSpace, 4);
    }
    
    /**
     * get's called by xml parser when tag ends
     *
     * @param resource $_parser
     * @param string $_tag current tag prefixed with namespace
     */
    protected function _handleEndTag($_parser, $_tag)
    {
        #echo "$_tag Level: $this->_level == $this->_nextStackPop \n";
        
        if($this->_nextStackPop !== NULL && $this->_nextStackPop == $this->_level) {
            #echo "TAG: $_tag\n";
            $this->_writeByte(Syncroton_Wbxml_Abstract::END);
            
            $subStream = $this->_stream;
            $subStreamLength = ftell($subStream);
            
            $this->_dtd             = array_pop($this->_dtdStack);
            $this->_stream          = array_pop($this->_streamStack);
            $this->_nextStackPop    = array_pop($this->_popStack);
            $this->_codePage        = $this->_dtd->getCurrentCodePage();
            
            rewind($subStream);
            #while (!feof($subStream)) {$buffer = fgets($subStream, 4096);echo $buffer;}
            $this->_writeByte(Syncroton_Wbxml_Abstract::OPAQUE);
            $this->_writeMultibyteUInt($subStreamLength);
            
            $writenBytes = stream_copy_to_stream($subStream, $this->_stream);
            if($writenBytes !== $subStreamLength) {
                //echo "$writenBytes !== $subStreamLength\n";
                throw new Syncroton_Wbxml_Exception('blow');
            }
            fclose($subStream);
            #echo "$this->_nextStackPop \n"; exit;
        } else {
            if ($this->_currentTag !== NULL && $this->_currentTagData !== NULL) {
                $this->_writeTag($this->_currentTag, $this->_attributes, true, $this->_currentTagData);
                $this->_writeByte(Syncroton_Wbxml_Abstract::END);
            } elseif ($this->_currentTag !== NULL && $this->_currentTagData === NULL) {
                // for example <UTC/> tag with no data, jumps directly from _handleStartTag to _handleEndTag 
                $this->_writeTag($this->_currentTag, $this->_attributes);
                // no end tag required, tag has no content
            } else {
                $this->_writeByte(Syncroton_Wbxml_Abstract::END);
            }
        }   
        
        #list($urn, $tag) = explode(';', $_tag); echo "</$tag> ($this->_level)\n";

        // reset $this->_currentTag, as tag got writen to stream already
        $this->_currentTag = NULL;
        
        $this->_level--;
    }

    /**
     * collects data(value) of tag
     * can be called multiple lines if the value contains linebreaks
     *
     * @param resource $_parser the xml parser
     * @param string $_data the data(value) of the tag
     */
    protected function _handleCharacters($_parser, $_data)
    {
        $this->_currentTagData .= $_data;
    }
    
    /**
     * writes tag with data to stream
     *
     * @param string $_tag
     * @param array $_attributes
     * @param bool $_hasContent
     * @param string $_data
     */
    protected function _writeTag($_tag, $_attributes=NULL, $_hasContent=false, $_data=NULL)
    {
        if($_hasContent == false && $_data !== NULL) {
            throw new Syncroton_Wbxml_Exception('$_hasContent can not be false, when $_data !== NULL');
        }
        
        // handle the tag
        $identity = $this->_codePage->getIdentity($_tag);
        
        if (is_array($_attributes) && isset($_attributes['uri:Syncroton;encoding'])) {
            $encoding = 'opaque';
            unset($_attributes['uri:Syncroton;encoding']);
        } else {
            $encoding = 'termstring';
        }
        
        if(!empty($_attributes)) {
            $identity |= 0x80;
        }
        
        if($_hasContent == true) {
            $identity |= 0x40;
        }
        
        $this->_writeByte($identity);
        
        // handle the data
        if($_data !== NULL) {
            if ($encoding == 'opaque') {
                $this->_writeOpaqueString(base64_decode($_data));
            } else {
                $this->_writeTerminatedString($_data);
            }
        }
        
        $this->_currentTagData = NULL;
    }

    /**
     * switch code page
     *
     * @param string $_urn
     */
    protected function _switchCodePage($_nameSpace)
    {
        try {
            $codePageName = $this->_stripNameSpace($_nameSpace);
            if(!defined('Syncroton_Wbxml_Dtd_ActiveSync::CODEPAGE_'. strtoupper($codePageName))) {
                throw new Syncroton_Wbxml_Exception('codepage ' . $codePageName . ' not found');
            }
            // switch to another codepage
            // no need to write the wbxml header again
            $codePageId = constant('Syncroton_Wbxml_Dtd_ActiveSync::CODEPAGE_'. strtoupper($codePageName));
            $this->_codePage = $this->_dtd->switchCodePage($codePageId);
            
            $this->_writeByte(Syncroton_Wbxml_Abstract::SWITCH_PAGE);
            $this->_writeByte($codePageId);
        } catch (Syncroton_Wbxml_Dtd_Exception_CodePageNotFound $e) {
            // switch to another dtd
            // need to write the wbxml header again
            // put old dtd and stream on stack
            $this->_dtdStack[] = $this->_dtd;
            $this->_streamStack[] = $this->_stream;
            $this->_popStack[] = $this->_nextStackPop;
            $this->_nextStackPop = $this->_level;
            
            $this->_stream = fopen("php://temp", 'r+');
            
            $this->_initialize($_urn);
        }
    }
}