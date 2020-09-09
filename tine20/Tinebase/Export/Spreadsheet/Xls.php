<?php
/**
 * Tinebase xls generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

// set include path for phpexcel
set_include_path(dirname(dirname(dirname(dirname(__FILE__)))) . '/library/PHPExcel' . PATH_SEPARATOR . get_include_path() );

/**
 * Tinebase xls generation class
 * 
 * @package     Tinebase
 * @subpackage  Export
 * 
 */
class Tinebase_Export_Spreadsheet_Xls extends Tinebase_Export_Spreadsheet_Abstract implements Tinebase_Record_IteratableInterface
{
    /**
     * current row number
     * 
     * @var integer
     */
    protected $_currentRowIndex = 0;
    
    /**
     * the phpexcel object
     * 
     * @var PHPExcel
     */
    protected $_excelObject = NULL;
    
    /**
     * format strings
     * 
     * @var string
     */
    protected $_format = 'xls';

    /**
     * generate export
     * 
     * @return PHPExcel
     */
    public function generate()
    {
        $this->_createDocument();
        $this->_setDocumentProperties();
        
        $this->_addHeader();
        $this->_exportRecords();
        
        $this->_setColumnWidths();
        
        return $this->getDocument();
    }
    
    /**
     * sets the colunm widths by config column->width
     */
    protected function _setColumnWidths()
    {
        $index = 0;
        foreach($this->_config->columns->column as $field) {
            if ($this->_groupBy !== NULL && $this->_groupBy == $field->identifier) {
                continue;
            }
            
            if (isset($field->width)) {
                $this->_excelObject->getActiveSheet()->getColumnDimensionByColumn($index)->setWidth((string) $field->width);
            }
            
            $index++;
        }
    }
    
    /**
     * add header
     */
    protected function _addHeader()
    {
        $patterns = array(
            '/\{date\}/',
            '/\{user\}/',
        );
        
        $replacements = array(
            Zend_Date::now()->toString(Zend_Locale_Format::getDateFormat($this->_locale), $this->_locale),
            Tinebase_Core::getUser()->accountDisplayName,
        );
        
        $this->_currentRowIndex = 1;
        
        $columnId = 0;
        
        if ($this->_config->headers) {
            foreach($this->_config->headers->header as $headerCell) {
                // replace data
                $value = preg_replace($patterns, $replacements, $headerCell);
                
                $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow(0, $this->_currentRowIndex, $value);
                
                $this->_currentRowIndex++;
            }
        
            $this->_currentRowIndex++;
        }
        
        if (isset($this->_config->header) && $this->_config->header) {
            $this->_addHead();
        }
    }
    
    /**
     * get excel object
     * 
     * @return PHPExcel
     */
    public function getDocument()
    {
        return $this->_excelObject;
    }
    
    /**
     * get export content type
     * 
     * @return string
     */
    public function getDownloadContentType()
    {
        $contentType = ($this->_config->writer == 'Excel2007') 
            // Excel 2007 content type
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            // Excel 5 content type or other
            : 'application/vnd.ms-excel';
                
        return $contentType;
    }
    
    /**
     * return download filename
     * @param string $_appName
     * @param string $_format
     */
    public function getDownloadFilename($_appName = null, $_format = null)
    {
        $result = parent::getDownloadFilename($_appName, $_format);
        
        if ($this->_config->writer == 'Excel2007') {
            // excel2007 extension is .xlsx
            $result .= 'x';
        }
        
        return $result;
    }
    
    /**
     * output result
     */
    public function write()
    {
        $xlsFormat = ($this->_config->writer) ? $this->_config->writer : 'Excel5';
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating and sending xls to client (Format: ' . $xlsFormat . ').');
        $xlswriter = PHPExcel_IOFactory::createWriter($this->_excelObject, $xlsFormat);
        
        // precalculating formula values costs tons of time, because sum formulas are like SUM C1:C65000
        $xlswriter->setPreCalculateFormulas(FALSE);
        
        $xlswriter->save('php://output');
    }
    
    /**
     * create new excel document
     * 
     * @return void
     */
    protected function _createDocument()
    {
        $templateFile = $this->_getTemplateFilename();
        
        if ($templateFile !== NULL) {
            
            if (! $this->_config->reader || $this->_config->reader == 'autodetection') {
                $this->_excelObject = PHPExcel_IOFactory::load($templateFile);
            } else {
                $reader = PHPExcel_IOFactory::createReader($this->_config->reader);
                $this->_excelObject = $reader->load($templateFile);
            }
            
            // need to unregister the zip stream wrapper because it is overwritten by PHPExcel!
            // TODO file a bugreport to PHPExcel 
            @stream_wrapper_restore("zip");
            
            $activeSheet = isset($this->_config->sheet) ? $this->_config->sheet : 1;
            $this->_excelObject->setActiveSheetIndex($activeSheet);
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new PHPExcel object.');
            $this->_excelObject = new PHPExcel();
        }
    }
    
    /**
     * get cell value
     * 
     * @param Zend_Config $_field
     * @param Tinebase_Record_Interface $_record
     * @param string $_cellType
     * @return string
     */
    protected function _getCellValue(Zend_Config $_field, Tinebase_Record_Interface $_record, &$_cellType)
    {
        switch ($_field->type) {
            case 'datetime':
            case 'date':
                if ($_record->{$_field->identifier} instanceof DateTime) {
                    if (! isset($_field->timestamp) || $_field->timestamp == 1) {
                        $result = PHPExcel_Shared_Date::PHPToExcel($_record->{$_field->identifier}->getTimestamp());
                    } else {
                        $result = parent::_getCellValue($_field, $_record, $_cellType);
                    }
                } else {
                    $result = $_record->{$_field->identifier};
                }
                
                // empty date cells, get displayed as 30.12.1899
                if (empty($result)) {
                    $result = NULL;
                }
                break;
            default:
                $result = parent::_getCellValue($_field, $_record, $_cellType);
                break;
        }
        
        return $result;
    }
    
    /**
     * set properties
     * 
     * @return void
     */
    protected function _setDocumentProperties()
    {
        // set metadata/properties
        if ($this->_config->writer == 'Excel2007') {
            $this->_excelObject->getProperties()
                ->setCreator(Tinebase_Core::getUser()->accountDisplayName)
                ->setLastModifiedBy(Tinebase_Core::getUser()->accountDisplayName)
                ->setTitle('Tine 2.0 ' . $this->_applicationName . ' Export')
                ->setSubject('Office 2007 XLSX Test Document')
                ->setDescription('Export for ' . $this->_applicationName . ', generated using PHP classes.')
                ->setKeywords("tine20 openxml php")
                ->setCreated(Zend_Date::now()->get());
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_excelObject->getProperties(), true));
        }
    }
    
    /**
     * add xls head (headline, column styles)
     */
    protected function _addHead()
    {
        $columnId = 0;
        
        foreach($this->_config->columns->column as $field) {
            if ($this->_groupBy !== NULL && $this->_groupBy == $field->identifier) {
                continue;
            }
            $headerValue = ($field->header) ? $this->_translate->translate($field->header) : $field->identifier;
            $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $headerValue);
        }
        
        $this->_currentRowIndex++;
    }
    
    /**
     * adds a header for each group
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _addGroupHeader($group)
    {
        // find out fieldconfig, if not found already
        if (! $this->_groupByFieldConfig) {
            $this->_columnCount = 0;
            foreach ($this->_config->columns->column as $field) {
                if ($field->identifier == $this->_groupBy) {
                    $this->_groupByFieldConfig = $field;
                    $this->_groupByFieldType = (isset($field->type)) ? $field->type : 'string';
                }
                
                $this->_columnCount++;
            }
        } else {
            $this->_currentRowIndex++;
            $this->_currentRowIndex++;
        }
        
        $fontColor       = 'b79511';
        $backgroundColor = '008bcf';
        $fontSize        = 16;
        
        if ($this->_config->grouping->groupheader) {#
            $gh = $this->_config->grouping->groupheader;
            
            $fontColor       = $gh->fontcolor ? (string) $gh->fontcolor : $fontColor;
            $backgroundColor = $gh->backgroundcolor ? (string) $gh->backgroundcolor : $backgroundColor;
            $fontSize        = $gh->fontsize ? (int) $gh->fontsize : $fontSize;
        }
        
        $cell = $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow(0, $this->_currentRowIndex, $group, TRUE);
        
        $styleArray = array(
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => $fontColor),
                'size'  => $fontSize,
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => $backgroundColor)
            )
        );
        
        $this->_excelObject->getActiveSheet()->getStyle($cell->getCoordinate())->applyFromArray($styleArray);
        
        $this->_excelObject->getActiveSheet()->mergeCellsByColumnAndRow(0, $this->_currentRowIndex, ($this->_columnCount - 2), $this->_currentRowIndex);
        
        $this->_currentRowIndex++;
        
        if ($this->_config->grouping->header) {
            $this->_addHead();
        }
        
        $this->_currentRowIndex++;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Export_Abstract::_onAfterExportRecords()
     */
    protected function _onAfterExportRecords($result)
    {
        // save number of records (only if we have more than 1 sheets / records are on the second sheet by default)
        if ($this->_excelObject->getSheetCount() > 1) {
            $this->_excelObject->setActiveSheetIndex(0);
            $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow(5, 2, $result['totalcount']);
        }
    }
    
    /**
     * add body rows
     *
     * @param Tinebase_Record_RecordSet $records
     * 
     * @todo add formulas
     */
    public function processIteration($_records)
    {
        $this->_resolveRecords($_records);
        
        $lastGroup = NULL;

        // add record rows
        $i = 0;
        foreach ($_records as $record) {
            if ($this->_groupBy !== NULL && ($lastGroup === null ||
                    (!empty($record->{$this->_groupBy}) && $lastGroup !== $record->{$this->_groupBy})))
            {
                $lastGroup = empty($record->{$this->_groupBy}) ? '' : $record->{$this->_groupBy};
                $this->_addGroupHeader($lastGroup);
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . print_r($record->toArray(), true));
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . print_r($this->_config->columns->toArray(), true));
            
            $columnId = 0;
            
            foreach ($this->_config->columns->column as $field) {
                // don't show group by field
                if ($this->_groupBy !== NULL && $field->identifier == $this->_groupBy) {
                    continue;
                }
                
                // get type and value for cell
                $cellType = (isset($field->type)) ? $field->type : 'string';
                $cellValue = $this->_getCellValue($field, $record, $cellType);
                
                // add formula
                if ($field->formula) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Adding formula: ' . $field->formula);
                    $cellValue = $field->formula;
                }
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . ' Setting col/row' . $columnId . ' / ' . $this->_currentRowIndex . ' = ' . $cellValue);
                
                $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $cellValue);
            }
            
            $i++;
            $this->_currentRowIndex++;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Export_Abstract::_exportRecords()
     */
    protected function _exportRecords()
    {
        parent::_exportRecords();
        
        $sheet = $this->_excelObject->getActiveSheet();
        
        for ($i = 0; $i < $this->_columnCount; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(TRUE);
        }
    }
}
