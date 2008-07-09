<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Field.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


abstract class Setup_Backend_Schema_Table_Abstract
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