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
    
    protected $_table;
    
    public function __construct(SimpleXMLElement $_table)
    {
        $this->_table = $_table;
    }
    
    /**
     * add new row and return reference
     *
     * @param string|optional $_tableName
     * @return OpenDocument_SpreadSheet_Row
     */
    public function appendRow($_styleName = null)
    {
        $row = OpenDocument_SpreadSheet_Row::createRow($this->_table, $_styleName);
        
        return $row;
    }
    
    static public function createTable(SimpleXMLElement $_parent, $_tableName, $_styleName = null)
    {
        $tableElement = $_parent->addChild('table', null, OpenDocument_Document::NS_TABLE);
        $tableElement->addAttribute('table:name', $_tableName, OpenDocument_Document::NS_TABLE);
        
        if($_styleName !== null) {
            $tableElement->addAttribute('table:style-name', $_styleName, OpenDocument_Document::NS_TABLE);
        }
        
        $table = new OpenDocument_SpreadSheet_Table($tableElement);
        
        return $table;
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