<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * class for a list OpenDocument_Matrix
 *
 * @package    OpenDocument
 * @subpackage Matrix_List
 */
class OpenDocument_Matrix_List implements IteratorAggregate, Countable
{
    
    /*
     * possible value types to create
     */
    const TYPE_STRING     = 'string';
    const TYPE_FLOAT      = 'float';
    const TYPE_FUNCTION   = 'function';
    
    /**
     * records to resolve the list
     * 
     * @var OpenDocument_Record_RecordSet
     */
    protected $_records = NULL;
    
    protected $_reversed = FALSE;
    
    /**
     * maps index to ids
     * 
     * @var array
     */
    protected $_indexIdMapping = NULL;
    
    /**
     * holds all lines
     * 
     * @var array
     */
    protected $_list = NULL;
    
    /**
     * the value type for the cells
     * currently string and float are supported
     * defaults to 'string'
     * 
     * @var string
     */
    protected $_valueType = NULL;
    
    /**
     * the constructor
     * 
     * @param array $data
     * @param array $info
     * @param string $valueType
     */
    public function __construct($data, $info = NULL, $valueType = self::TYPE_FLOAT)
    {
        $this->_info      = $info;
        $this->_valueType = $valueType;
        
        if ($info !== NULL) {
            foreach ($this->_info as $id => $title) {
                $_this->_indexIdMapping[] = $id;
                $this->_list[$id] = (isset($data[$id]) ? $data[$id] : ($this->_valueType == self::TYPE_FLOAT ? 0 : ''));
            }
        } else {
            $this->_list = $data;
        }
    }
    
    /**
     * reverses the direction
     */
    public function reverse()
    {
        $this->_list = array_reverse($this->_list, TRUE);
        $this->_reversed = ! $this->_reversed;
    }
    
    /**
     * returns value from a specific row
     * 
     * @param integer $index
     * @return integer
     */
    public function getAt($index)
    {
        return $this->_list[$this->_indexIdMappingRows[$index]];
    }
    
    /**
     * returns an item by its id
     * 
     * @param string $id
     * @return multitype:
     */
    public function getById($id)
    {
        return $this->_list[$id];
    }
    
    /**
     * counts items in this->_list
     *
     * @return number
     */
    public function count()
    {
        return count($this->_list);
    }
    
    /**
     * returns the iterator
     * 
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_list);
    }
    
    /**
     * returns the value type
     * 
     * @return string
     */
    public function getValueType()
    {
        return $this->_valueType;
    }
    
    /**
     * returns the iteratable as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_list;
    }
    
    /**
     *
     * @return number
     */
    public function sum()
    {
        return array_sum($this->_list);
    }
}