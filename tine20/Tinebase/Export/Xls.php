<?php
/**
 * Tinebase xls generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Ods.php 10912 2009-10-12 14:40:25Z p.schuele@metaways.de $
 * 
 */

// set include path for phpexcel
set_include_path(dirname(dirname(dirname(__FILE__))) . '/library/PHPExcel' . PATH_SEPARATOR . get_include_path() );

/**
 * Tinebase xls generation class
 * 
 * @package     Tinebase
 * @subpackage  Export
 * 
 */
class Tinebase_Export_Xls extends Tinebase_Export_Abstract
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
     * generate export
     * 
     * @return string filename
     */
    public function generate()
    {
        $this->_createDocument();
        $this->_setDocumentProperties();
        
        $records = $this->_getRecords();
        
        // add header
        if (isset($this->_config->header) && $this->_config->header) {
            $this->_currentRowIndex = 1;
            $this->_addHead();
        } else {
            $this->_currentRowIndex = 2;
        }
        
        // add body
        $this->_addBody($records);
        
        return $this->getDocument();
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
    public function getDownloadFilename($_appName, $_format)
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
        
        // precalcualting formula values costs tons of time, because sum formulas are like SUM C1:C65000
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
            
            $this->_excelObject->setActiveSheetIndex(1);
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
        switch($_field->type) {
            case 'datetime':
            case 'date':
                if ($_record->{$_field->identifier} instanceof Zend_Date) {
                    $result = PHPExcel_Shared_Date::PHPToExcel($_record->{$_field->identifier}->getTimestamp());
                } else {
                    $result = $_record->{$_field->identifier};
                }
                
                // empty date cells, get displayed as 30.12.1899
                if(empty($result)) {
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
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_excelObject->getProperties(), true));
        }
    }
    
    /**
     * add xls head (headline, column styles)
     */
    protected function _addHead()
    {
        $columnId = 0;
        foreach($this->_config->columns->column as $field) {
            $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $field->header);
        }
        
        $this->_currentRowIndex++;
    }
    
    /**
     * add body rows
     *
     * @param Tinebase_Record_RecordSet $records
     * 
     * @todo add formulas
     */
    protected function _addBody($_records)
    {
        // add record rows
        $i = 0;
        foreach ($_records as $record) {
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            $columnId = 0;
            
            foreach ($this->_config->columns->column as $field) {
                
                // get type and value for cell
                $cellType = (isset($field->type)) ? $field->type : 'string';
                $cellValue = $this->_getCellValue($field, $record, $cellType);
                
                // add formula
                if ($field->formula) {
                    //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding formula: ' . $field->formula);
                    $cellValue = $field->formula;
                }
                
                $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $cellValue);
            }
            
            $i++;
            $this->_currentRowIndex++;
        }
        
        // save number of records
        $this->_excelObject->setActiveSheetIndex(0);
        $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow(5, 2, count($_records));
    }
}
