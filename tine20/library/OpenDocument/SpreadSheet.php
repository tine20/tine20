<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
    
    /**
     * returns the content type
     * 
     * @return string
     */
    public function getContentType()
    {
        return self::CONTENT_TYPE;
    }
    
    /**
     * no idea what this is for
     * 
     * @param SimpleXMLElement $_parentNode
     */
    public function __construct(SimpleXMLElement $_parentNode)
    {
        $this->_spreadSheet = $_parentNode;
    }
    
    /**
     * returns the body as simplexml
     * 
     * @var SimpleXMLElement
     */
    public function getBody()
    {
        return $this->_spreadSheet;
    }
    
    /**
     * replaces a marker with a matrix
     * 
     * @param SimpleXMLElement $xml
     * @param OpenDocument_Matrix $matrix
     * @param bool $showLegend
     * @param bool $showLegendDescription
     * @param bool $showSums
     */
    public function replaceMatrix($xml, $matrix, $showLegend = TRUE, $showLegendDescription = TRUE, $showSums = TRUE)
    {
        $table  = $this->_findParentTable($xml);
        $sp     = $this->_getStartingPoint($xml);
        $reference      = $sp['reference'];
        $referenceIndex = $sp['referenceIndex'];
        
        $colLegend = $matrix->getColumnLegend();
        $rowLegend = $matrix->getRowLegend();
        $columnLegendDescription = $matrix->getColumnLegendDescription();
        $rowLegendDescription = $matrix->getRowLegendDescription();
        
        $mcount = $matrix->count();
        
        // show vertical sum legend
        if ($showSums && $showLegend) {
            $row = OpenDocument_SpreadSheet_Row::createRow($table, null, $reference, $referenceIndex, 'after');
            $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
            $row->appendCell(_('Sum'), OpenDocument_SpreadSheet_Cell::TYPE_STRING, array('table:style-name' => "cell-header"));
            
            $index = 0;
            foreach ($matrix->getRowInfo() as $id => $title) {
                $row->appendCell($matrix->sumColumn($id), OpenDocument_SpreadSheet_Cell::TYPE_FLOAT);
                $index++;
            }
            $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
            $row->appendCell($matrix->sum(), OpenDocument_SpreadSheet_Cell::TYPE_FLOAT);
            $row = OpenDocument_SpreadSheet_Row::createRow($table, null, $reference, $referenceIndex, 'after');
        }
        
        // insertion is always after the line before the marker has been, so reversion is needed
        $matrix->reverse();
        $countColumns = count($matrix);
        $i = 1;
        $rowIndex = $countColumns - 1;
        $colIndex = 0;
        
        foreach ($matrix as $key => $list) {
            $row = OpenDocument_SpreadSheet_Row::createRow($table, null, $reference, $referenceIndex, 'after');
            
            if ($showLegendDescription) {
                if ($i < $countColumns) {
                    $row->appendCoveredCell();
                } else {
                    $row->appendCell(
                        $columnLegendDescription,
                        OpenDocument_SpreadSheet_Cell::TYPE_STRING,
                        array(
                            'table:number-rows-spanned' => $mcount,
                            'table:style-name' => "cell-center-middle"
                        )
                    );
                }
                $i++;
            }
            
            if ($showLegend) {
                $val = isset($colLegend[$key]) ? $colLegend[$key] : '';
                $row->appendCell(
                    $val,
                    OpenDocument_SpreadSheet_Cell::TYPE_STRING,
                    array('table:style-name' => "cell-header")
                );
            }
            
            // process values of the matrix
            foreach ($list as $cellValue) {
                $row->appendCell(
                    $cellValue,
                    $list->getValueType(),
                    ($colIndex === $rowIndex ? array('table:style-name' => "cell-color-grey") : array('table:style-name' => 'value-cell-default'))
                );
                $colIndex++;
            }
            $colIndex = 0;
            $rowIndex--;
            
            if ($showSums) {
                $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
                $row->appendCell($list->sum(), OpenDocument_SpreadSheet_Cell:: TYPE_FLOAT, array('table:style-name' => "cell-header"));
            }
        }
        
        // add legend at top
        if ($showLegend || $showLegendDescription) {
            $row = OpenDocument_SpreadSheet_Row::createRow($table, null, $reference, $referenceIndex, 'after');
            $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
        }
        
        if ($showLegend && $showLegendDescription) {
            $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
        }
        
        if ($showLegend) {
            foreach ($rowLegend as $title) {
                $row->appendCell($title, OpenDocument_SpreadSheet_Cell::TYPE_STRING, array('table:style-name' => "cell-header"));
            }
            // show vertical sums
            if ($showSums) {
                $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
                $row->appendCell(_('Sum'), OpenDocument_SpreadSheet_Cell::TYPE_STRING, array('table:style-name' => "cell-header"));
            }
        }
        
        if ($showLegendDescription) {
            
            $row = OpenDocument_SpreadSheet_Row::createRow($table, null, $reference, $referenceIndex, 'after');
            $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
            
            if ($showLegend) {
                $row->appendCell('', OpenDocument_SpreadSheet_Cell::TYPE_STRING);
                $row->appendCell(
                    $rowLegendDescription,
                    OpenDocument_SpreadSheet_Cell::TYPE_STRING,
                    array(
                        'table:number-columns-spanned' => count($list),
                        'table:style-name' => "cell-center-middle"
                    )
                );
            }
        }
    }
    
    /**
     * 
     * @param SimpleXMLElement $xmlElement
     * @param OpenDocument_Matrix_List $list
     * @param string $type
     * @param string $direction
     */
    public function replaceList($xml, $list, $type = OpenDocument_SpreadSheet_Cell::TYPE_STRING, $direction = 'horizontal')
    {
        $table          = $this->_findParentTable($xml);
        $sp             = $this->_getStartingPoint($xml, $rows);
        $reference      = $sp['reference'];
        $referenceIndex = $sp['referenceIndex'];
        
        if ($direction == 'horizontal') {
            $row = OpenDocument_SpreadSheet_Row::createRow($table, null, $reference, $referenceIndex, 'after');
            foreach($list as $item) {
                $row->appendCell($item, $type);
            }
        } elseif ($direction == 'vertical') {
            $list->reverse();
            foreach($list as $item) {
                $row = OpenDocument_SpreadSheet_Row::createRow(
                    $table, null, $reference, $referenceIndex, 'after'
                );
                $row->appendCell($item);
            }
        } else {
            throw new Exception('direction ' . $direction . ' is unsupported!');
        }
    }
    
    /**
     * returns the starting point for replacing, deletes the marker itself
     * 
     * @param SimpleXMLElement $xml
     * @return array
     */
    protected function _getStartingPoint($xml)
    {
        $rows  = $this->_findParentRow($xml, TRUE);
        $prev = $rows[0]->xpath('preceding-sibling::*');
        
        // find reference row, if no preceding sibling gets found, the next one will be used
        if (count($prev) > 0) {
            $reference = $prev;
            $referenceIndex = count($prev) - 1;
        } else {
            $next = $rows[0]->xpath('following-sibling::*');
            $referenceIndex = 0;
            $reference = isset($next[0]) ? $next : NULL;
        }
        
        // delete row node where the marker resides
        unset($rows[0]);
        
        return array('reference' => $reference, 'referenceIndex' => $referenceIndex);
    }
    
    /**
     * finds the parent table of a node
     *
     * @param array|SimpleXMLElement $xml
     * @throws Exception
     * @return SimpleXMLElement
     */
    protected function _findParentTable($xml)
    {
        if (! $xml) {
            throw new Exception('The table of the element could not be found!');
        }
    
        if (is_array($xml) && count($xml) == 1) {
            $xml = $xml[0];
        }
    
        if ($xml->getName() != 'table') {
            return $this->_findParentTable($xml->xpath('parent::*'));
        }
    
        return $xml;
    }
    
    /**
     * finds the parent row of a node (where a marker may sit)
     * 
     * @param array|SimpleXMLElement $xml
     * @param bool $asRef
     * @throws Exception
     * @return SimpleXMLElement
     */
    protected function _findParentRow($xml, $asRef = FALSE)
    {
        if (! $xml) {
            throw new Exception('The row of the element could not be found!');
        }
        
        if (is_array($xml) && count($xml) == 1) {
            $el = $xml[0];
        } else {
            $el = $xml;
        }
        
        if ($el->getName() != 'table-row') {
            return $this->_findParentRow($el->xpath('parent::*'));
        }
    
        return $asRef ? $xml : $el;
    }

    /**
     * returns all tables of the spreadsheet
     * 
     * @return array
     */
    public function getTables()
    {
        $tables = $this->_spreadSheet->xpath('//office:body/office:spreadsheet/table:table');
        
        $result = array();
        
        foreach ($tables as $table) {
            $attributes = $table->attributes(OpenDocument_Document::NS_TABLE);
            $result[(string)$attributes['name']] = new OpenDocument_SpreadSheet_Table($table);
        }
        
        return $result;
    }
    
    /**
     * checks if a table exists (by name)
     * 
     * @param string $_tableName
     * @return bool
     */
    public function tableExists($_tableName)
    {
        $table = $this->_spreadSheet->xpath("//office:body/office:spreadsheet/table:table[@table:name='$_tableName']");
        
        if (count($table) === 0) {
            return FALSE;
        }
        
        return true;
    }
    
    /**
     * returns a table
     * 
     * @param string $_tableName
     * @return boolean|OpenDocument_SpreadSheet_Table
     */
    public function getTable($_tableName)
    {
        $table = $this->_spreadSheet->xpath("//office:body/office:spreadsheet/table:table[@table:name='$_tableName']");
        
        if (count($table) === 0) {
            return FALSE;
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