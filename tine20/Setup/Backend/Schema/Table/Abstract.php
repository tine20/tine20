<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */


abstract class Setup_Backend_Schema_Table_Abstract extends Setup_Backend_Schema_Abstract
{
    
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
     public $charset = 'utf8';
    
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
     * add one field to the table definition
     *
     * @param Setup_Backend_Schema_Field $_declaration
     */
    public function addField(Setup_Backend_Schema_Field_Abstract $_field)
    {
        $this->fields[] = $_field;
    }
    
    
    /**
     * add one index to the table definition
     *
     * @param Setup_Backend_Schema_Index_Abstract $_definition
     */
    public function addIndex(Setup_Backend_Schema_Index_Abstract $_index)
    {
        $this->indices[] = $_index;
    }
    
    /**
     * Override method to cut table prefix from {@param $_name} if present 
     * 
     * Setter for {@see $name} property
     * 
     * @param string $_name
     * @return void
     */      
    public function setName($_name)
    {
        $name = (string)$_name;
        if (SQL_TABLE_PREFIX == substr($name, 0, strlen(SQL_TABLE_PREFIX))) {
            $name = substr($name, strlen(SQL_TABLE_PREFIX));
        }
        parent::setName($name);
    }
    
    public function equals(Setup_Backend_Schema_Table_Abstract $_table)
    {
        if (
            $this->name != $_table->name ||
            count($this->fields) !== count($_table->fields)
        ) {
            return false;
        }

        foreach ($this->fields as $index => $field) {
            if (!$field->equals($_table->fields[$index])) {
                return false;
            }
        }
        return true;
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
