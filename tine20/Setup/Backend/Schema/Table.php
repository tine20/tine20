<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Table.php 1703 2008-04-03 18:16:32Z lkneschke $
 */

 /**
 * Data definition for tables used in setup
 *
 * @package     Setup
 */
class Setup_Backend_Schema_Table
{
    /**
     * the name of the table
     *
     * @var string
     */
    public $name;
    
    /**
     * the table comment
     *
     * @var string
     */
    public $comment;
    
    /**
     * the table version
     *
     * @var int
     */
    public $version;
    
    
    /**
     * the table engine (innodb)
     *
     * @var int
     */
    public $engine = 'InnoDB';
    
    
    /**
     * the table engine (innodb)
     *
     * @var int
     */
    public $charset = 'UTF8';
    
        
    
    /**
     * the table columns
     *
     * @var array
     */
    public $fields = array();
    
    /**
     * the table indices
     *
     * @var array
     */
    public $indices = array();
        
    
    /**
     * the constructor
     *
     * @param mixed $_tableDefinition
     */
    public function __construct($_tableDefinition)
    {
        /*
         * while parsing an XML-document, tables are stored in a SimpleXMLElement structure
         */
        if ($_tableDefinition instanceof SimpleXMLElement) {
            $this->_setTableFromXml($_tableDefinition);
        
        /*
         * for easy cut&paste in update scripts table definitions can also stored as a string
         */        
        } else if (isset($_tableDefinition['XmlString'])) {
            $this->_setTableFromString($_tableDefinition);
            
        /*
         * while reading databases information schema, definitions are delivered as a stdClass object
         */    
        } else if ($_tableDefinition instanceof stdClass) {
            $this->_setTableFromObject($_tableDefinition);

        } else {
            throw new Exception('Can\'t create table');
        }
    
        $this->_addIndexInformation();
        
    }
    
    
    /**
     * set Table from simpleXMLObject
     *
     */
    protected function _setTableFromXml(SimpleXMLElement $_tableDefinition)
    {
        $this->name = (string) $_tableDefinition->name;
        $this->comment = (string) $_tableDefinition->comment;
        $this->version = (string) $_tableDefinition->version;
        //$this->charset = (string) $_tableDefinition->charset;
        //$this->engine = (string) $_tableDefinition->engine;
        
        // do not worry about genus, in simpleXML, several field(s) are stored in an array named field  (pretty much the same on indices)
        $this->_setFieldsFromXML($_tableDefinition->declaration->field);
        $this->_setIndicesFromXML($_tableDefinition->declaration->index);
    }
    
    /**
     * convert string if possible to simpleXMLElement and starts setting table
     *
     */    
    protected function _setTableFromString(array $_tableDefinition)
    {
        try {
            $xmlObject = new SimpleXMLElement($_tableDefinition['XmlString']);
        } catch (Exception $e) {
            echo $e->getMessage(); 
            exit;
        }
        $this->_setTableFromXml($xmlObject);
    }
    
    /**
     * set Table from database information schema
     *
     */
    protected function _setTableFromObject(stdClass $_tableDefinition)
    {
        //collect information von information schema - 
        //        $this->name = substr($_tableInfo['TABLE_NAME'], strlen( SQL_TABLE_PREFIX ));

        //        $version = explode(';', $_tableInfo['TABLE_COMMENT']);
        //        $this->version = substr($version[0],9);    
    }
    
    
    
    /**
     * set all fields (columns) from simpleXMLObject
     *
     */
    protected function _setFieldsFromXML(SimpleXMLElement $_fieldDefinitions)
    {
        foreach ($_fieldDefinitions as $fieldDefinition) {
            $this->_addField(new Setup_Backend_Schema_Field($fieldDefinition, 'XML'));
        }
    }

    /**
     * set all fields (columns) from simpleXMLObject
     *
     */
    protected function _setIndicesFromXML(SimpleXMLElement $_indicesDefinitions)
    {
        foreach ($_indicesDefinitions as $indexDefinition) {
            $this->_addIndex(new Setup_Backend_Schema_Index($indexDefinition, 'XML'));
        }
    }
    
    
    /**
     * add one field to the table definition
     *
     * @param Setup_Backend_Schema_Field $_declaration
     */
    protected function _addField(Setup_Backend_Schema_Field $_field)
    {
        $this->fields[] = $_field;
    }
    
    
    /**
     * add one index to the table definition
     *
     * @param Setup_Backend_Schema_Index $_definition
     */
    protected function _addIndex(Setup_Backend_Schema_Index $_index)
    {
        $this->indices[] = $_index;
    }
    
    /**
     * put information whether field is key or not to all fields definitions
     *
     */
    protected function _addIndexInformation()
    {
        foreach ($this->fields as $field) {
            $field->fixFieldKey($this->indices);
        }
    }
}
