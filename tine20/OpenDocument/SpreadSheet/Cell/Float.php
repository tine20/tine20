<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * create opendocument files
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 */
 
class OpenDocument_SpreadSheet_Cell_Float extends OpenDocument_SpreadSheet_Cell_Abstract
{
    public function saveXML(SimpleXMLElement $_table)
    {
        $cell = $_table->addChild('table-cell', NULL, OpenDocument_Document::NS_TABLE);
        $cell->addAttribute('office:value', $this->_encodeValue(), OpenDocument_Document::NS_OFFICE);    
        $cell->addAttribute('office:value-type', 'float', OpenDocument_Document::NS_OFFICE);    
        
        $this->_addAttributes($cell);
        
        $cell->addChild('text:p', $this->_encodeValue(), 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');    
    }
}