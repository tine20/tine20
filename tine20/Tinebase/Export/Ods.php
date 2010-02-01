<?php
/**
 * Tinebase Ods generation class
 *
 * @package     Tinebase
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Tinebase Ods generation class
 * 
 * @package     Tinebase
 * @subpackage	Export
 */
class Tinebase_Export_Ods extends Tinebase_Export_Abstract
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
     * first row of body (records)
     * 
     * @var integer
     */
    protected $_firstRow = 4;
    
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
     * generate export
     * 
     * @return string filename
     */
    public function generate()
    {
        // check for template file
        $templateFile = $this->_config->get('template', NULL);
        if ($templateFile !== NULL) {
            $templateFile = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . $this->_applicationName . 
                DIRECTORY_SEPARATOR . 'Export' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateFile;
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Using template file "' . $templateFile . '" for ' . $this->_modelName . ' export.');
        }
                        
        $this->_openDocumentObject = new OpenDocument_Document(OpenDocument_Document::SPREADSHEET, $templateFile, Tinebase_Core::getTempDir(), $this->_userStyles);
        
        $records = $this->_getRecords();
        
        // build export table (use current table if using template)
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating export for ' . $this->_modelName . ' . ' . $this->_getDataTableName());
        
        $spreadSheet = $this->_openDocumentObject->getBody();
        
        // append / use existing table
        if($spreadSheet->tableExists($this->_getDataTableName()) === true) {
            $table = $spreadSheet->getTable($this->_getDataTableName());
        } else {
            $table = $spreadSheet->appendTable($this->_getDataTableName());
        }
        
        // add header
        if (isset($this->_config->header) && $this->_config->header) {
            $this->_addHead($table);
        }
            
        // body
        $this->_addBody($table, $records);
        
        // add footer
        if (isset($this->_config->footer) && $this->_config->footer) {
            $this->_addFooter($table, $lastCell);
        }
        
        // add overview table
        if (isset($this->_config->overviewTable) && $this->_config->overviewTable) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding overview table.');
            $this->_addOverviewTable($lastCell);
        }
        
        // create file
        $result = $this->_openDocumentObject->getDocument();        
        return $result;
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
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * 
     * @todo add filters/replacements again?
     */
    protected function _addHead($table)
    {
        $columnId = 0;
        foreach($this->_config->columns->column as $field) {
            $column = $table->appendColumn();
            $column->setStyle('co' . $columnId);
            if($field->type == 'date') {
                $column->setDefaultCellStyle('ceShortDate');
            }
            $this->_addColumnStyle('co' . $columnId, $field->width);
            
            $columnId++;
        }

        $row = $table->appendRow();
        
        // add header (replace placeholders)
        if (isset($this->_config->headers)) {
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_filter->toArray(), true));
            
            $patterns = array(
                '/\{date\}/', 
                '/\{user\}/',
                //'/\{filter\}/'
            );
            
            /*
            $filters = array();
            foreach ($_filter->toArray() as $filter) {
                switch($filter['field']) {
                    case 'timeaccount_id':
                        if (!empty($filter['value']) && is_array($filter['value'])) {
                            $value = $timeaccounts[$timeaccounts->getIndexById($filter['value'][0])]->title;
                        }
                        break;
                    case 'account_id':
                        $value = Tinebase_User::getInstance()->getUserById($filter['value'])->accountDisplayName;
                        break;
                    default:
                        $value = $filter['value'];
                }
                $filters[] = $filter['field'] . '=' . $value;
            }
            */
            
            $replacements = array(
                Zend_Date::now()->toString(Zend_Locale_Format::getDateFormat($this->_locale), $this->_locale),
                Tinebase_Core::getUser()->accountDisplayName,
                //$this->_translate->_('Filter') . ': ' . implode(', ', $filters)
            );
            
            foreach($this->_config->headers->header as $headerCell) {
                // replace data
                $value = preg_replace($patterns, $replacements, $headerCell);
                $cell = $row->appendCell('string', $value);                
            }
        }
        
        $row = $table->appendRow();
        
        // add table headline
        $row = $table->appendRow();
        foreach($this->_config->columns->column as $field) {
            $cell = $row->appendCell('string', $field->header);
            $cell->setStyle('ceHeader');
        }
    }
    
    /**
     * add body rows
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param Tinebase_Record_RecordSet $records
     * @param array 
     * 
     * @todo    generalize this for other export formats
     */
    protected function _addBody(OpenDocument_SpreadSheet_Table $table, $_records)
    {
        if (isset($this->_config->customFields) && $this->_config->customFields) {
            // we need the sql backend if the export contains custom fields
            // @todo remove that when getMultiple() fetches the custom fields as well
            $recordBackend = new Timetracker_Backend_Timesheet();
        }
        
        // add record rows
        $i = 0;
        foreach ($_records as $record) {
            
            // check if we need to get the complete record with custom fields
            // @todo remove that when getMultiple() fetches the custom fields as well
            if (isset($this->_config->customFields) && $this->_config->customFields) {
                $record = $recordBackend->get($record->getId());
                Tinebase_User::getInstance()->resolveUsers($record, 'account_id');
            }
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            $row = $table->appendRow();

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
                #if ($i % 2 == 1) {
                #    $cell->setStyle($altStyle);
                #}
                */
                
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($field->toArray(), true));
            }        
            $i++;
        }
        
    }
    
    /**
     * add table footer (formulas, ...)
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param integer $lastCell
     */
    protected function _addFooter($table, $lastCell)
    {
    }
    
    /**
     * add overview table
     *
     * @param integer $lastCell
     */
    protected function _addOverviewTable($lastCell)
    {
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
            case 'currency':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_CURRENCY;
                break;
            case 'percentage':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_PERCENTAGE;
                break;
            case 'number':
                $result = OpenDocument_SpreadSheet_Cell::TYPE_FLOAT;
                break;
            default:
                $result = OpenDocument_SpreadSheet_Cell::TYPE_STRING;
        }
        
        return $result;
    }
}
