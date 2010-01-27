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
 * 
 * @todo        parse existing tables/rows/columns/cells when using template file
 */

/**
 * create opendocument files
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 */
 
class OpenDocument_SpreadSheet implements Iterator, Countable
{
    const CONTENT_TYPE = 'application/vnd.oasis.opendocument.spreadsheet';

    protected $_tables = array();
    
    protected $_position = 0;
    
    /**
     * the content.xml document
     *
     * @var SimpleXMLElement
     */
    protected $_spreadSheet;
        
    public function getContentType()
    {
        return self::CONTENT_TYPE;
    }
    
    public function __construct(SimpleXMLElement $_parentNode)
    {
        $this->_spreadSheet = $_parentNode;
    }
    
    public function getTables()
    {
        $tables = $this->_spreadSheet->xpath('//office:body/office:spreadsheet/table:table');
        
        $result = array();
        
        foreach($tables as $table) {
            $attributes = $table->attributes(self::TABLE_URN);
            $result[(string)$attributes['name']] = new OpenDocument_SpreadSheet_Table($table);
        }
        
        return $result;
    }
    
    public function tableExists($_tableName)
    {
        $table = $this->_spreadSheet->xpath("//office:body/office:spreadsheet/table:table[@table:name='$_tableName']");
        
        if(count($table) === 0) {
            return false;
        }
        
        return true;
    }
    
    public function getTable($_tableName)
    {
        $table = $this->_spreadSheet->xpath("//office:body/office:spreadsheet/table:table[@table:name='$_tableName']");
        
        if(count($table) === 0) {
            return false;
        }
        
        return new OpenDocument_SpreadSheet_Table($table[0]);
    }
    
    /**
     * add new table and return reference
     *
     * @param string|optional $_tableName
     * @return OpenDocument_SpreadSheet_Table
     */
    public function appendTable($_tableName, $_styleName = null)
    {
        $table = OpenDocument_SpreadSheet_Table::createTable($this->_spreadSheet, $_tableName, $_styleName = null);
        
        return $table;
    }
    
    function rewind() {
        $this->_position = 0;
    }

    function current() {
        return $this->_tables[$this->_position];
    }

    function key() {
        return $this->_position;
    }

    function next() {
        ++$this->_position;
    }

    function valid() {
        return isset($this->_tables[$this->_position]);
    }
    
    public function count()
    {
        return count($this->_tables);
    }
}