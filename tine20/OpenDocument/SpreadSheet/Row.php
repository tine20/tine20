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
 
class OpenDocument_SpreadSheet_Row implements Iterator, Countable
{
    protected $_cells = array();
    
    #protected $_styles = array();
    
    protected $_attributes = array();
    
    protected $_position = 0;
    
    /**
     * add new cell an return reference
     *
     * @return OpenDocument_SpreadSheet_Row
     */
    public function appendCell($_cellType, $_cellValue = null)
    {
        $cell = OpenDocument_SpreadSheet_Cell::factory($_cellType, $_cellValue);
        
        $this->_cells[] = $cell;
        $this->_position++;
        
        return $cell;
    }
    
    public function setStyle($_styleName)
    {
        $this->_attributes['style-name'] = $_styleName;
    }

    public function saveXML(SimpleXMLElement $_table)
    {
        $row = $_table->addChild('table-row', NULL, OpenDocument_Document::NS_TABLE);
        foreach($this->_attributes as $key => $value) {
            $row->addAttribute($key, $value, OpenDocument_Document::NS_TABLE);
        }
        
        foreach($this->_cells as $cell) {
            $cell->saveXML($row);
        }
    }
    
//    public function addStyle($_key, $_value)
//    {
//        $this->_styles[$_key] = $_value;
//    }
    
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