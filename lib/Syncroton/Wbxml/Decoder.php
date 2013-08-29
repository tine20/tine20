<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  Wbxml
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:Decoder.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class to convert WBXML to XML
 *
 * @package     Wbxml
 * @subpackage  Wbxml
 */
 
class Syncroton_Wbxml_Decoder extends Syncroton_Wbxml_Abstract
{
    /**
     * type of Document Public Identifier 
     *
     * @var string the type can be Syncroton_Wbxml_Abstract::DPI_STRINGTABLE or Syncroton_Wbxml_Abstract::DPI_WELLKNOWN
     */
    protected $_dpiType;
    
    /**
     * the string table
     *
     * @var array
     */
    protected $_stringTable = array();
    
    /**
     * the xml document
     *
     * @var DOMDocument
     */
    protected $_dom;
    
    /**
     * the main name space / aka the namespace of first tag
     *
     * @var string
     */
    protected $_mainNameSpace;

    /**
     * the constructor will try to read all data until the first tag
     *
     * @param resource $_stream
     */
    public function __construct($_stream, $_dpi = NULL)
    {
        if(!is_resource($_stream) || get_resource_type($_stream) != 'stream') {
            throw new Syncroton_Wbxml_Exception('$_stream must be a stream');
        }
        if($_dpi !== NULL) {
            $this->_dpi = $_dpi;
        }
        
        $this->_stream = $_stream;
        
        $this->_version = $this->_getByte();
        
        if(feof($this->_stream)) {
            throw new Syncroton_Wbxml_Exception_UnexpectedEndOfFile();
        }
        
        $this->_getDPI();
        
        $this->_getCharset();
        
        $this->_getStringTable();
        
        // resolve DPI as we have read the stringtable now
        // this->_dpi contains the string table index
        if($this->_dpiType === Syncroton_Wbxml_Abstract::DPI_STRINGTABLE) {
            $this->_dpi = $this->_stringTable[$this->_dpi];
        }
        
        #$this->_dtd = Syncroton_Wbxml_Dtd_Factory::factory($this->_dpi);
        $this->_dtd = Syncroton_Wbxml_Dtd_Factory::factory(Syncroton_Wbxml_Dtd_Factory::ACTIVESYNC);
    }
    
    /**
     * return the Document Public Identifier
     *
     * @param integer $_uInt unused param, needed to satisfy abstract class method signature
     * @return string
     */
    public function getDPI($_uInt = 0)
    {
        return $this->_dpi;
    }
    
    /**
     * return the wbxml version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }
    
    /**
     * decodes the tags 
     *
     * @return DOMDocument the decoded xml
     */
    public function decode()
    {
        $openTags = NULL;
        $node = NULL;
        $this->_codePage = $this->_dtd->getCurrentCodePage();
        
        while (!feof($this->_stream)) {
            $byte = $this->_getByte();
            
            switch($byte) {
                case Syncroton_Wbxml_Abstract::END:
                    $node = $node->parentNode;
                    $openTags--;
                    break;
                    
                case Syncroton_Wbxml_Abstract::OPAQUE:
                    $length = $this->_getMultibyteUInt();
                    if($length > 0) {
                        $opaque = $this->_getOpaque($length);
                        try {
                            // let see if we can decode it. maybe the opaque data is wbxml encoded content
                            $opaqueDataStream = fopen("php://temp", 'r+');
                            fputs($opaqueDataStream, $opaque);
                            rewind($opaqueDataStream);
                            
                            $opaqueContentDecoder = new Syncroton_Wbxml_Decoder($opaqueDataStream);
                            $dom = $opaqueContentDecoder->decode();
                            
                            fclose($opaqueDataStream);
                            
                            foreach($dom->childNodes as $newNode) {
                                if($newNode instanceof DOMElement) {
                                    $newNode = $this->_dom->importNode($newNode, true);
                                    $node->appendChild($newNode);
                                }
                            }
                        } catch (Exception $e) {
                            // if not, just treat it as a string
                            $node->appendChild($this->_dom->createTextNode($opaque)); 
                        }
                    }
                    break;
                    
                case Syncroton_Wbxml_Abstract::STR_I:
                    $string = $this->_getTerminatedString();
                    $node->appendChild($this->_dom->createTextNode($string)); 
                    break;
                    
                case Syncroton_Wbxml_Abstract::SWITCH_PAGE:
                    $page = $this->_getByte();
                    $this->_codePage = $this->_dtd->switchCodePage($page);
                    #echo "switched to codepage $page\n";
                    break;
                    
                default:
                    $tagHasAttributes   = (($byte & 0x80) != 0);
                    $tagHasContent      = (($byte & 0x40) != 0);
                    // get rid of bit 7+8
                    $tagHexCode         = $byte & 0x3F;

                    try {
                        $tag = $this->_codePage->getTag($tagHexCode);
                    } catch (Syncroton_Wbxml_Exception $swe) {
                        // tag can not be converted to ASCII name
                        $tag = sprintf('unknown tag 0x%x', $tagHexCode);
                    }
                    $nameSpace    = $this->_codePage->getNameSpace();
                    $codePageName = $this->_codePage->getCodePageName();
                    
                    #echo "Tag: $nameSpace:$tag\n";
                    
                    if ($node === NULL) {
                        // create the domdocument
                        $node    = $this->_createDomDocument($nameSpace, $tag);
                        $newNode = $node->documentElement;
                    } else {
                        if (!$this->_dom->isDefaultNamespace($nameSpace)) {
                            $this->_dom->documentElement->setAttribute('xmlns:' . $codePageName, $nameSpace);
                        }
                        $newNode = $node->appendChild($this->_dom->createElementNS('uri:' . $codePageName, $tag));
                    }
                    
                    if ($tagHasAttributes) {
                        $attributes = $this->_getAttributes();
                    }
                    
                    if ($tagHasContent == true) {
                        $node = $newNode;
                        $openTags++;
                    }
                    
                    break;
            }
        }

        return $this->_dom;
    }
    
    /**
     * creates the root of the xml document
     *
     * @return DOMDocument
     */
    protected function _createDomDocument($_nameSpace, $_tag)
    {
        $this->_dom = $this->_dtd->getDomDocument($_nameSpace, $_tag);
        
        return $this->_dom;
    }

    /**
     * read the attributes of the current tag
     *
     * @todo implement logic
     */
    protected function _getAttributes()
    {
        die("fetching attributes not yet implemented!\n");
    }

    /**
     * get document public identifier
     *
     * the identifier can be all welknown identifier (see section 7.2) or a string from the stringtable
     */
    protected function _getDPI()
    {
        $uInt = $this->_getMultibyteUInt();
        
        if($uInt == 0) {
            // get identifier from stringtable
            $this->_dpiType = Syncroton_Wbxml_Abstract::DPI_STRINGTABLE;
            // string table identifier, can be resolved only after reading string table
            $this->_dpi = $this->_getByte();
        } else {
            // wellknown identifier
            $this->_dpiType = Syncroton_Wbxml_Abstract::DPI_WELLKNOWN;
            $this->_dpi = Syncroton_Wbxml_Abstract::getDPI($uInt);
        }
    }
    
    /**
     * see http://www.iana.org/assignments/character-sets (MIBenum)
     * 106: UTF-8
     *
     */
    protected function _getCharset()
    {
        $uInt = $this->_getMultibyteUInt();
        
        switch($uInt) {
            case 106:
                $this->_charSet = 'UTF-8';
                break;
                
            default:
                throw new Syncroton_Wbxml_Exception('unsuported charSet: ' . $uInt);
                break;
        }
    }
    
    /**
     * get string table and store strings indexed by start
     *
     * @todo validate spliting at 0 value
     */
    protected function _getStringTable()
    {
        $length = $this->_getMultibyteUInt();
        
        if($length > 0) {
            $rawStringTable = $this->_getOpaque($length);
            $index = NULL;
            $string = NULL;
            
            for($i = 0; $i < strlen($rawStringTable); $i++) {
                if($index === NULL) {
                    $index = $i;
                }
                if(ord($rawStringTable[$i]) != 0) {
                    $string .= $rawStringTable[$i];
                }
                
                // either the string has ended or we reached a \0
                if($i+1 == strlen($rawStringTable) || ord($rawStringTable[$i]) == 0){
                    $this->_stringTable[$index] = $string;
                    $index = NULL;
                    $string = NULL;
                }
            }
        }
    }    
}