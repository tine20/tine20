<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */


abstract class Setup_Backend_Schema_Field_Abstract extends Setup_Backend_Schema_Abstract
{
    
    /**
     * the data type (int/varchar/etc)
     *
     * @var string
     */    
    public $type;
    
    /**
     * mysql- feature
     *
     * @var boolean
     */
    public $autoincrement;

    /**
     * only positive values are allowed
     *
     * @var boolean
     */
    public $unsigned;

    /**
     * the data precision
     *
     * @var int
     */
    public $length;
    
    /**
     * the data scale (number of digits after the decimal point)
     *
     * @var int
     */
    public $scale;

    /**
     * if true, there have to be some values
     *
     * @var string
     */
    public $notnull;

    /**
     * value / decimal definition / enum values / default values
     *
     * @var mixed
     */
    public $value;
    
    /**
     * value / decimal definition / enum values / default values
     *
     * @var mixed
     */
    public $default;

    /**
     * field/ column comment
     *
     * @var string
     */
    public $comment;

    /**
     * is index (mysql specific setting)
     *
     * @var boolean
     */
    public $mul;

    /**
     * is primary key
     *
     * @var boolean
     */
    public $primary;

    /**
     * value has to be unique
     *
     * @var boolean
     */
    public $unique;
    
    //abstract protected function _setField($_declaration);
    
    /**
     * homogenize key definition from database and XML
     *
     * @param SimpleXMLElement $_declaration
     */
    public function fixFieldKey(array $_indices)
    {
        foreach ($_indices as $index) {
            if ($this->name == $index->name) {
                if ($index->primary == 'true') {
                    $this->primary = 'true';
                } elseif ($index->unique == 'true') {
                    $this->unique = 'true';
                } else {
                    $this->mul = 'true';
                }
            }
        }
    }    
    
        
    public function toArray()
    {
        if ('decimal' == $this->type) {
            return array();
        } 
        
        return array('name'=> $this->name, 
                    'type' => $this->type,
                    'autoincrement' => $this->autoincrement,
                    'length' => (int) $this->length ,
                    'unsigned' => $this->unsigned,
                    'value' => $this->value,
                    'mul' => $this->mul,
                    'primary' => $this->primary,
                    'unique' => $this->unique,
                   );
    }
    
    /**
     * 
     * @param Setup_Backend_Schema_Field_Abstract $field
     * @return unknown_type
     */
    public function equals(Setup_Backend_Schema_Field_Abstract $_field)
    {
        return (
            $_field->name == $this->name &&
            (
                (
                    $_field->type == $this->type ||
                    $this->_resemblesType($_field)
                ) 
                &&
                (
                    $_field->length == $this->length
                    ||
                    $this->_resemblesLength($_field)
                )
            )
        );
    }
    
    protected function _resemblesLength(Setup_Backend_Schema_Field_Abstract $_field) {
        if (!isset($this->length)) {
            $thisTypeMap = $this->getBackend()->getTypeMapping($this->type);
            return (isset($thisTypeMap['defaultLength']) && $_field->length == $thisTypeMap['defaultLength']);
        }
        
            if (!isset($_field->length)) {
            $fieldTypeMap = $this->getBackend()->getTypeMapping($_field->type);
            return (isset($fieldTypeMap['defaultLength']) && $this->length == $fieldTypeMap['defaultLength']);
        }
        
        return false;
    }
    
    protected function _resemblesType(Setup_Backend_Schema_Field_Abstract $_field) {
        $thisType = $this->type;
        $fieldType = $_field->type;
        if ($fieldType == $thisType) {
            return true;
        }
        
        $thisTypeMap = $this->getBackend()->getTypeMapping($thisType);
        $fieldTypeMap = $this->getBackend()->getTypeMapping($fieldType);
        if ($this->_resemblesTypeCheck($thisTypeMap, $fieldType) ||
            $this->_resemblesTypeCheck($fieldTypeMap, $thisType) ||
            $this->_resemblesTypeCheck($fieldTypeMap, $thisTypeMap['defaultType']) ||
            $this->_resemblesTypeCheck($thisTypeMap, $fieldTypeMap['defaultType'])) {
            return true;
        }
        
        return false;
    }
    
    protected function _resemblesTypeCheck($fieldTypeMap, $expectedType)
    {
        if (isset($fieldTypeMap['lengthTypes'])) {
            foreach ($fieldTypeMap['lengthTypes'] as $length => $type) {
                if ($type == $expectedType) {
                    return true;
                }
            }
        }
        return false;
    }
    
    

}
