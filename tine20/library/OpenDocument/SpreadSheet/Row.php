<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * create opendocument files
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 */
 
class OpenDocument_SpreadSheet_Row implements Iterator, Countable
{
    protected $_cells = array();
    
    #protected $_styles = array();
    
    protected $_attributes = array();
    
    protected $_position = 0;
    
    /**
     * 
     * @var SimpleXMLElement
     */
    protected $_row;
    
    public function __construct(SimpleXMLElement $_row)
    {
        $this->_row = $_row;
    }
    
    public function getBody()
    {
        return $this->_row;
    }
    
    /**
     * add new cell and return reference
     *
     * @param string|optional $_tableName
     * @return OpenDocument_SpreadSheet_Cell
     */
    public function appendCell($_value, $_type = null, $additionalAttributes = array())
    {
        $cell = OpenDocument_SpreadSheet_Cell::createCell($this->_row, $_value, $_type, $additionalAttributes);
        return $cell;
    }
    
    public function appendCoveredCell()
    {
        $cell = OpenDocument_SpreadSheet_Cell::createCoveredCell($this->_row);
        return $cell;
    }
    
    public function setStyle($_styleName)
    {
        $this->_attributes['table:style-name'] = $_styleName;
    }

    /**
     * 
     * @param SimpleXMLElement $_parent
     * @param string $_styleName
     * @param SimpleXMLElement $_referenceRow
     * @param string $_position
     * 
     * @return OpenDocument_SpreadSheet_Row
     */
    static public function createRow($_parent, $_styleName = NULL, $_reference = NULL, $_refIndex = 0, $_position = 'after')
    {
        if ($_reference == NULL) {
            $rowElement = $_parent->addChild('table-row', NULL, OpenDocument_Document::NS_TABLE);
            
            if ($_styleName !== NULL) {
                $rowElement->addAttribute('table:style-name', $_styleName, OpenDocument_Document::NS_TABLE);
            }
        } else {
            
            $rowElement = $_parent->addChild('table:table-row', NULL, OpenDocument_Document::NS_TABLE);
            
            if ($_position == 'after') {
                $rowElement = OpenDocument_Shared_SimpleXML::simplexml_insert_after($rowElement, $_reference, $_refIndex);
            } else {
                $rowElement = OpenDocument_Shared_SimpleXML::simplexml_insert_before($rowElement, $_reference, $_refIndex);
            }
            
        }
        
        $row = new self($rowElement);
        
        return $row;
    }
    
    public function generateXML(SimpleXMLElement $_table)
    {
        $row = $_table->addChild('table-row', NULL, OpenDocument_Document::NS_TABLE);
        foreach($this->_attributes as $key => $value) {
            $row->addAttribute($key, $value, OpenDocument_Document::NS_TABLE);
        }
        
        foreach($this->_cells as $cell) {
            $cell->generateXML($row);
        }
    }
    
    function rewind() {
        $this->_position = 0;
    }

    function current() {
        return $this->_cells[$this->_position];
    }

    function key() {
        return $this->_position;
    }

    function next() {
        ++$this->_position;
    }

    function valid() {
        return isset($this->_cells[$this->_position]);
    }
    
    public function count()
    {
        return count($this->_cells);
    }
}