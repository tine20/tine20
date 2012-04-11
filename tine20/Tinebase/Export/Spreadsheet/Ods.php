<?php
/**
 * Tinebase Ods generation class
 *
 * @package     Tinebase
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add alternating row styles again?
 */

/**
 * Tinebase Ods generation class
 * 
 * @package     Tinebase
 * @subpackage    Export
 */
class Tinebase_Export_Spreadsheet_Ods extends Tinebase_Export_Spreadsheet_Abstract implements Tinebase_Record_IteratableInterface
{
    /**
     * user styles
     *
     * @var array
     */
    protected $_userStyles = array(
        '<number:date-style style:name="nShortDate" number:automatic-order="true" 
                xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">
            <number:day number:style="long"/>
            <number:text>.</number:text>
            <number:month number:style="long"/>
            <number:text>.</number:text>
            <number:year number:style="long"/>
        </number:date-style>',
        '<number:number-style style:name="N2"
                xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0">
            <number:number number:decimal-places="2" number:min-integer-digits="1"/>
        </number:number-style>',    
        '<style:style style:name="ceHeader" style:family="table-cell" 
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:table-cell-properties fo:background-color="#ccffff"/>
            <style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/>
            <style:text-properties fo:font-weight="bold"/>
        </style:style>',
        '<style:style style:name="ceBold" style:family="table-cell" style:data-style-name="N2"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:text-properties fo:font-weight="bold"/>
        </style:style>',
        '<style:style style:name="ceAlternate" style:family="table-cell"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:table-cell-properties fo:background-color="#ccccff"/>
        </style:style>',
        '<style:style style:name="ceAlternateCentered" style:family="table-cell"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:table-cell-properties fo:background-color="#ccccff"/>
            <style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/>
        </style:style>',
        '<style:style style:name="ceShortDate" style:family="table-cell" style:data-style-name="nShortDate"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/>
        </style:style>',
        '<style:style style:name="numberStyle" style:family="table-cell" style:data-style-name="N2"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:paragraph-properties fo:text-align="right"/>
        </style:style>',
        '<style:style style:name="numberStyleAlternate" style:family="table-cell" style:data-style-name="N2"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:table-cell-properties fo:background-color="#ccccff"/>
            <style:paragraph-properties fo:text-align="right"/>
        </style:style>',
    );
    
    /**
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array();
    
    /**
     * the opendocument object
     * 
     * @var OpenDocument_Document
     */
    protected $_openDocumentObject = NULL;
    
    /**
     * spreadsheet table
     * 
     * @var OpenDocument_SpreadSheet_Table
     */
    protected $_activeTable = NULL;
    
    /**
     * generate export
     * 
     * @return string filename
     */
    public function generate()
    {
        $this->_createDocument();
        
        // build export table (use current table if using template)
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating export for ' . $this->_modelName . ' . ' . $this->_getDataTableName());
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config->toArray(), TRUE));
        
        $spreadSheet = $this->_openDocumentObject->getBody();
        
        // append / use existing table
        if($spreadSheet->tableExists($this->_getDataTableName()) === true) {
            $this->_activeTable = $spreadSheet->getTable($this->_getDataTableName());
        } else {
            $this->_activeTable = $spreadSheet->appendTable($this->_getDataTableName());
        }
        
        // add header (disabled at the moment)
        if (isset($this->_config->header) && $this->_config->header) {
            $this->_addHead($this->_activeTable);
        }
        
        $this->_exportRecords();
        
        // create file
        $result = $this->_openDocumentObject->getDocument();
        return $result;
    }
    
    /**
     * get download content type
     * 
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'application/vnd.oasis.opendocument.spreadsheet';
    }
    
    /**
     * create new open document document
     * 
     * @return void
     */
    protected function _createDocument()
    {
        // check for template file
        $templateFile = $this->_getTemplateFilename();
                        
        $this->_openDocumentObject = new OpenDocument_Document(OpenDocument_Document::SPREADSHEET, $templateFile, Tinebase_Core::getTempDir(), $this->_userStyles);
    }
    
    /**
     * get open document object
     * 
     * @return OpenDocument_Document
     */
    public function getDocument()
    {
        return $this->_openDocumentObject;
    }
    
    /**
     * add ods head (headline, column styles)
     */
    protected function _addHead()
    {
        $row = $this->_activeTable->appendRow();
        
        // add header (replace placeholders)
        if (isset($this->_config->headers)) {
            
            $patterns = array(
                '/\{date\}/', 
                '/\{user\}/',
            );
            
            $replacements = array(
                Zend_Date::now()->toString(Zend_Locale_Format::getDateFormat($this->_locale), $this->_locale),
                Tinebase_Core::getUser()->accountDisplayName,
            );
            
            foreach($this->_config->headers->header as $headerCell) {
                // replace data
                $value = preg_replace($patterns, $replacements, $headerCell);
                $cell = $row->appendCell($value, OpenDocument_SpreadSheet_Cell::TYPE_STRING);
            }
        }
        
        $row = $this->_activeTable->appendRow();
        
        // add table headline
        $row = $this->_activeTable->appendRow();
        foreach($this->_config->columns->column as $field) {
            $headerValue = ($field->header) ? $field->header : $field->identifier;
            $cell = $row->appendCell($headerValue, OpenDocument_SpreadSheet_Cell::TYPE_STRING);
            $cell->setStyle('ceHeader');
        }
    }
    
    /**
     * format strings
     * 
     * @var string
     */
    protected $_format = 'ods';
    
    /**
     * add body rows
     *
     * @param Tinebase_Record_RecordSet $records
     */
    public function processIteration($_records)
    {
        $this->_resolveRecords($_records);
        
        // add record rows
        $i = 0;
        foreach ($_records as $record) {
            
            $row = $this->_activeTable->appendRow();

            foreach ($this->_config->columns->column as $field) {
                
                //$altStyle = 'ceAlternate';
                
                // get type and value for cell
                $cellType = $this->_getCellType($field->type);
                $cellValue = $this->_getCellValue($field, $record, $cellType);
                
                // create cell with type and value and add style
                $cell = $row->appendCell($cellValue, $cellType);
                
                // add formula
                if ($field->formula) {
                    $cell->setFormula($field->formula);
                }

                /*
                if ($i % 2 == 1) {
                    $cell->setStyle($altStyle);
                }
                */
                
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($field->toArray(), true));
            }
            $i++;
        }
        
    }
    
    /**
     * add style/width to column
     *
     * @param string $_styleName
     * @param string $_columnWidth (for example: '2,5cm')
     */
    protected function _addColumnStyle($_styleName, $_columnWidth) 
    {
        $this->_openDocumentObject->addStyle('<style:style style:name="' . $_styleName . '" style:family="table-column" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><style:table-column-properties style:column-width="' . $_columnWidth . '"/></style:style>');
    }
    
    /**
     * get name of data table
     * 
     * @return string
     */
    protected function _getDataTableName()
    {
        return $this->_translate->_('Data');
    }
    
    /**
     * get cell type
     * 
     * @param string $_fieldType
     * @return string
     */
    protected function _getCellType($_fieldType)
    {
        switch($_fieldType) {
            case 'date':
            case 'datetime':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_DATE;
                break;
            case 'time':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_TIME;
                break;
            case 'currency':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_CURRENCY;
                break;
            case 'percentage':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_PERCENTAGE;
                break;
            case 'float':
            case 'number':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_FLOAT;
                break;
            default:
                $result = OpenDocument_SpreadSheet_Cell::TYPE_STRING;
        }
        
        return $result;
    }
}
