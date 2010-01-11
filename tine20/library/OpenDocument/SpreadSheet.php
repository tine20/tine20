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
    protected $_document;
        
    public function getContentType()
    {
        return self::CONTENT_TYPE;
    }
    
    public function __construct(SimpleXMLElement $_parentNode)
    {
        $this->_document = $_parentNode;
    }

    /**
     * add new table and return reference
     *
     * @param string|optional $_tableName
     * @return OpenDocument_SpreadSheet_Table
     */
    public function appendTable($_tableName = NULL)
    {
        $table = new OpenDocument_SpreadSheet_Table($_tableName);
        
        $this->_tables[] = $table;
        $this->_position++;
        
        return $table;
    }
    
    public function generateXML()
    {
        foreach($this->_tables as $table) {
            $table->generateXML($this->_document);
        }
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $this->_document->generateXML());
        
        return $this->_document->saveXML();
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