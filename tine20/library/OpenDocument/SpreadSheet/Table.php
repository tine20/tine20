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
 
class OpenDocument_SpreadSheet_Table implements Iterator, Countable
{
    protected $_rows = array();
    
    protected $_columns = array();
    
    protected $_position = 0;
    
    protected $_tableName;
    
    public function __construct($_tableName = null) 
    {
        $this->_tableName = $_tableName;
    }
    
    /**
     * add new row an return reference
     *
     * @return OpenDocument_SpreadSheet_Row
     */
    public function appendRow()
    {
        $row = new OpenDocument_SpreadSheet_Row();
        
        $this->_rows[] = $row;
        $this->_position++;
        
        return $row;
    }
    
    /**
     * add new row an return reference
     *
     * @return OpenDocument_SpreadSheet_Row
     */
    public function appendColumn()
    {
        $column = new OpenDocument_SpreadSheet_Column();
        
        $this->_columns[] = $column;
        
        return $column;
    }
    
    public function generateXML(SimpleXMLElement $_spreadSheet)
    {
        $table = $_spreadSheet->addChild('table', NULL, OpenDocument_Document::NS_TABLE);
        $table->addAttribute('table:name', $this->_tableName, OpenDocument_Document::NS_TABLE);
                
        foreach($this->_columns as $column) {
            $column->generateXML($table);
        }
        
        foreach($this->_rows as $row) {
            $row->generateXML($table);
        }
    }
    
    function rewind() {
        $this->_position = 0;
    }

    function current() {
        return $this->_rows[$this->_position];
    }

    function key() {
        return $this->_position;
    }

    function next() {
        ++$this->_position;
    }

    function valid() {
        return isset($this->_rows[$this->_position]);
    }
    
    public function count()
    {
        return count($this->_rows);
    }
}