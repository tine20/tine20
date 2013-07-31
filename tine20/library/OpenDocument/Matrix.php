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
 * class for a matrix used by OpenDocument_Document->replaceMarkers()
 *
 * @package    OpenDocument
 * @subpackage Matrix
 */
class OpenDocument_Matrix implements IteratorAggregate, Countable
{

    /**
     * info to resolve the X legend
     *
     * @var array
     */
    protected $_colInfo = NULL;

    /**
     * info to resolve the Y legend
     *
     * @var array
     */
    protected $_rowInfo = NULL;
    
    protected $_indexIdMappingCols = NULL;
    
    /**
     * holds the reversed state of the matrix
     * 
     * @var bool
     */
    protected $_reversed = FALSE;
    
    /**
     * 
     * @var string
     */
    protected $_colLegendDescription = 'Cols'; // _('Cols')
    
    /**
     *
     * @var string
     */
    protected $_rowLegendDescription = 'Rows'; // _('Rows')
    
    

    protected $_matrix = NULL;

    /**
     * the value type for the cells
     * currently string, float and function are supported
     * defaults to 'string'
     *
     * @var string
     */
    protected $_valueType = NULL;
    
    /**
     * if this is set to true, the legend will be returned
     *
     * @var bool
     */
    protected $_returnLegend = TRUE;

    const TYPE_STRING     = 'string';
    const TYPE_FLOAT      = 'float';
    const TYPE_FUNCTION   = 'function';
    
    /**
     * creates a rectangular matrix from a 2 dimensional array by the given record sets and fills it with the given data.
     * if the second record set is not given, the first will be used as second one, also.
     * 
     *
     * @param array $data
     * @param array $colInfo
     * @param array $rowInfo
     * @param string $valueType
     */
    public function __construct(array $data, $colInfo = NULL, $rowInfo = NULL, $valueType = self::TYPE_STRING)
    {
        $this->_colInfo   = $colInfo;
        $this->_rowInfo   = $rowInfo ? $rowInfo : $colInfo;
        $this->_valueType = $valueType;
        $this->_matrix    = array();

        if ($colInfo !== NULL) {
            foreach ($this->_rowInfo as $id => $title) {
                if (array_key_exists($id, $data)) {
                    $d = $data[$id];
                } else {
                    $d = array_flip($this->_colInfo);
                    foreach($d as $key => &$val) {
                        $val = 0;
                    }
                }
                
                $this->_matrix[$id] = new OpenDocument_Matrix_List($d, $this->_colInfo, $this->_valueType);
            }
        }
    }
    
    /**
     * returns the column legend
     * 
     * @return array
     */
    public function getColumnLegend()
    {
        $legend = array();
        foreach($this->_colInfo as $id => $title) {
            $legend[$id] = $title;
        }
        return $legend;
    }
    
    /**
     * returns the row legend
     * 
     * @return array
     */
    public function getRowLegend()
    {
        $legend = array();
        
        foreach ($this->_rowInfo as $id => $title) {
            $legend[$id] = $title;
        }
        
        if ($this->_reversed) {
            $legend = array_reverse($legend, TRUE);
        }
        
        return $legend;
    }

    /**
     * returns the column legend description
     *
     * @return string
     */
     
    public function getColumnLegendDescription()
    {
        return $this->_colLegendDescription;
    }
    
    /**
     * returns the row legend description
     * 
     * @return string
     */
    public function getRowLegendDescription()
    {
        return $this->_rowLegendDescription;
    }
    
    /**
     * sets the column legend description
     * 
     * @param string $text
     */
     
    public function setColumnLegendDescription($text)
    {
        $this->_colLegendDescription = $text;
    }
    
    /**
     * sets the row legend description
     * 
     * @param string $text
     */
    public function setRowLegendDescription($text)
    {
        $this->_rowLegendDescription = $text;
    }
    
    /**
     * counts items in this->_matrix
     * 
     * @return number
     */
    public function count()
    {
        return count($this->_matrix);
    }
    
    /**
     * returns the iterator
     *
     * @return ArrayIterator
    */
    public function getIterator() {
        return new ArrayIterator($this->_matrix);
    }
    
    /**
     * returns the iteratable as array
     * 
     * @return array
     */
    public function toArray()
    {
        $ret = array();
        foreach($this->_matrix as $key => $list) {
            $ret[$key] = $list->toArray();
        }
        return $ret;
    }
    
    /**
     * summarizes the whole matrix
     * 
     * @return number
     */
    public function sum()
    {
        $sum = 0;
        foreach($this->_matrix as $list) {
            $sum += $list->sum();
        }
        return $sum;
    }
    
    /**
     * summarizes the column given by the index or record id (index must be int, otherwise string)
     * 
     * @param integer|string $index
     * @return number
     */
    public function sumColumn($index)
    {
        $sum = 0;
        
        if (is_int($index)) {
            foreach ($this->_matrix as $list) {
                $sum = $sum + (int) $list->getAt($index);
            }
        } elseif (strlen($index) == 40) {
            foreach($this->_matrix as $list) {
                $sum = $sum + (int) $list->getById($index);
            }
        } else {
            throw new Tinebase_Exception_InvalidArgument('Param not supported!');
        }
        
        return $sum;
    }
    
    /**
     * 
     * @return array
     */
    public function getRowInfo()
    {
        return $this->_rowInfo;
    }
    
    /**
     * 
     * @return array
     */
    public function getColInfo()
    {
        return $this->_colInfo;
    }
    
    /**
     * reverses the order of the matrix
     * 
     * @return OpenDocument_Matrix
     */
    public function reverse()
    {
        $this->_matrix = array_reverse($this->_matrix, true);
        $this->_reversed = ! $this->_reversed;
        return $this;
    }
}