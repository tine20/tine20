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
     * holds style names for each column
     * 
     * @var array
     */
    protected $_columnStyles = array();
    
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
        if ($spreadSheet->tableExists($this->_getDataTableName()) === true) {
            $this->_activeTable = $spreadSheet->getTable($this->_getDataTableName());
        } else {
            $this->_activeTable = $spreadSheet->appendTable($this->_getDataTableName());
        }
        
        $this->_setColumnStyles();
        
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
     * defines column styles by the config xml
     */
    protected function _setColumnStyles()
    {
        $index = 1;
        $classPrefix = 'co';
        $defaultStyles = NULL;
        
        if ($this->_config->defaultColumnStyle) {
            $defaultStyles = array();
            foreach ($this->_config->defaultColumnStyle as $name => $style) {
                $defaultStyles[$name] = (string) $style;
            }
            $this->_addColumnStyle('co0', $defaultStyles);
        }
        
        foreach($this->_config->columns->column as $column) {

            if ($column->style) {
                if (! $defaultStyles) {
                    $msg = 'If a column contains style, the "defaultColumnStyle" has to be defined!';
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $msg . ' Definition Name: ' . (string) $this->_config->name);
                    }
                    
                    throw new Tinebase_Exception_UnexpectedValue($msg);
                }
                
                $columnStyles = array();
                foreach($column->style as $name => $style) {
                    $columnStyles[$name] = (string) $style;
                }
                
                $this->_addColumnStyle($classPrefix . $index, $columnStyles);
                
                $this->_columnStyles[$index] = $classPrefix . $index;
            } else {
                $this->_columnStyles[$index] = 'co0';
            }
            
            $index++;
        }
        
        foreach($this->_columnStyles as $key => $style) {
            $this->_activeTable->appendColumn($style);
        }
    }
    /**
     * add ods head (headline, column styles)
     */
    protected function _addHead()
    {
        // add header (replace placeholders)
        if (isset($this->_config->headers)) {
            
            $row = $this->_activeTable->appendRow();
            
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
                
                if ($this->_config->headerStyle) {
                    $cell->setStyle((string) $this->_config->headerStyle);
                }
            }
        }
        
        // add table headline
        $row = $this->_activeTable->appendRow();
        
        $i18n = $this->_translate->getAdapter();
        
        foreach($this->_config->columns->column as $field) {
            $headerValue = ($field->header) ? $i18n->translate($field->header) : $field->identifier;
            $cell = $row->appendCell($headerValue, OpenDocument_SpreadSheet_Cell::TYPE_STRING);
            
            if (isset($field->headerStyle)) {
                $cell->setStyle((string) $field->headerStyle);
            } else {
                $cell->setStyle('ceHeader');
            }
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
                
                if ($field->columnStyle) {
                    $cell->setStyle((string) $field->columnStyle);
                }
                
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
     * @param string $styleName
     * @param string $values
     */
    protected function _addColumnStyle($styleName, $values)
    {
        $xml = '<style:style style:name="' . $styleName . '" style:family="table-column"><style:table-column-properties';

        foreach($values as $attr => $value) {
            $xml .= ' style:' . $attr . '="' . $value . '"';
        }
        
        $xml .= ' /></style:style>';
        
        $this->_openDocumentObject->addStyle(array($xml));
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
