<?php
/**
 * Timetracker Ods generation class
 *
 * @package     Timetracker
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Timetracker Ods generation class
 * 
 * @package     Timetracker
 * @subpackage	Export
 * 
 */
class Timetracker_Export_Ods extends OpenDocument_Document
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
        '<style:style style:name="ceHeader" style:family="table-cell" 
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:table-cell-properties fo:background-color="#ccffff"/>
            <style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/>
            <style:text-properties fo:font-weight="bold"/>
        </style:style>',
        '<style:style style:name="ceBold" style:family="table-cell" 
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:text-properties fo:font-weight="bold"/>
        </style:style>',
        '<style:style style:name="ceAlternate" style:family="table-cell" style:data-style-name="nShortDate"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:table-cell-properties fo:background-color="#ccccff"/>
        </style:style>',
        '<style:style style:name="ceAlternateCentered" style:family="table-cell" style:data-style-name="nShortDate"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:table-cell-properties fo:background-color="#ccccff"/>
            <style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/>
        </style:style>',
        '<style:style style:name="ceShortDate" style:family="table-cell" style:data-style-name="nShortDate"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/>
        </style:style>'
    );
    
    /**
     * translation object
     *
     * @var Zend_Translate
     */
    protected $_translate;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        parent::__construct(OpenDocument_Document::SPREADSHEET);
        
        $this->_translate = Tinebase_Translation::getTranslation('Timetracker');
    }
    
    /**
     * export timesheets to Ods file
     *
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @return string filename
     */
    public function exportTimesheets($_filter) {
        
        // get timesheets by filter
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($_filter);
        $lastCell = count($timesheets)+1;
        
        // build export array
        $fields = $this->_getExportFields();
        
        // build export table
        $table = $this->_addHead($fields);
        $this->_addBody($table, $fields, $timesheets);
        $this->_addFooter($table, $lastCell);
        
        // add overview table
        $this->_addOverview($lastCell);
        
        // create file
        $filename = $this->getDocument();        
        return $filename;
    }
    
    /**
     * add ods head (headline, column styles)
     *
     * @param array $fields
     * @return OpenDocument_SpreadSheet_Table
     */
    protected function _addHead($fields)
    {
        $table      = $this->getBody()->appendTable('Timesheets');

        $columnId = 0;
        foreach($fields as $field) {
            $column = $table->appendColumn();
            $column->setStyle('co' . $columnId);
            if($field['type'] == 'date') {
                $column->setDefaultCellStyle('ceShortDate');
            }
            $this->_addColumnStyle('co' . $columnId, $field['width']);
            
            $columnId++;
        }
        
        $row = $table->appendRow();
        
        // add headline
        foreach($fields as $field) {
            $cell = $row->appendCell('string', $field['header']);
            $cell->setStyle('ceHeader');
        }
        
        return $table;
    }
    
    /**
     * add single export row
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param array $fields
     * @param Tinebase_Record_RecordSet $timesheets
     */
    protected function _addBody($table, $fields, $timesheets)
    {
        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
        
        // resolve accounts
        $accountIds = $timesheets->account_id;
        $accounts = Tinebase_User::getInstance()->getMultiple(array_unique(array_values($accountIds)));

        // add timesheet rows
        $i = 0;
        foreach ($timesheets as $timesheet) {
            $row = $table->appendRow();

            foreach ($fields as $key => $params) {
                switch($params['type']) {
                    case 'timeaccount':
                        $value = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->$params['field'];
                        $cell = $row->appendCell('string', $value);
                        if($i % 2 == 1) {
                            $cell->setStyle('ceAlternate');
                        }
                        break;
                    case 'account':
                        $value = $accounts[$accounts->getIndexById($timesheet->account_id)]->$params['field'];
                        $cell = $row->appendCell('string', $value);
                        if($i % 2 == 1) {
                            $cell->setStyle('ceAlternate');
                        }
                        break;
                    case 'date':
                        $cell = $row->appendCell($params['type'], $timesheet->$key);
                        if($i % 2 == 1) {
                            $cell->setStyle('ceAlternateCentered');
                        }
                        break;
                    default:
                        $value = (isset($params['divisor'])) ? $timesheet->$key / $params['divisor'] : $timesheet->$key;
                        $cell = $row->appendCell($params['type'], $value);
                        if($i % 2 == 1) {
                            $cell->setStyle('ceAlternate');
                        }
                        break;
                }
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
        // add footer
        $row = $table->appendRow();
        $row = $table->appendRow();
        $row->appendCell('string');
        $row->appendCell('string');
        $row->appendCell('string');
        $row->appendCell('string', 'Total');
        $cell = $row->appendCell('float', 0);
        #$cell->setFormula('oooc:=SUM([.E2:.E' . $lastCell . '])');   
        $cell->setFormula('oooc:=SUM(E2:E' . $lastCell . ')');   
        $cell->setStyle('ceBold');     
    }
    
    /**
     * add overview table
     *
     * @param integer $lastCell
     */
    protected function _addOverview($lastCell)
    {
        $table = $this->getBody()->appendTable('Overview');
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Not billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.F2:Timesheets.F' . $lastCell . ';0;Timesheets.E2:Timesheets.E' . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.F2:Timesheets.F' . $lastCell . ';1;Timesheets.E2:Timesheets.E' . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Total'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUM(Timesheets.E2:Timesheets.E' . $lastCell . ')');
        $cell->setStyle('ceBold');     
    }
    
    /**
     * get export fields
     * - record fieldname => headline (translated)
     *
     * @return array
     * 
     * @todo add fields array to preferences or move to config file
     */
    protected function _getExportFields()
    {
        $configFilename = dirname(__FILE__) . '/../../config/Timetracker/export.inc.php';
        
        $fields = (file_exists($configFilename)) ? require $configFilename : array();
        
        return $fields;
    }
    
    /**
     * add style/width to column
     *
     * @param string $_styleName
     * @param string $_columnWidth (for example: '2,5cm')
     */
    protected function _addColumnStyle($_styleName, $_columnWidth) 
    {
        $this->addStyle('<style:style style:name="' . $_styleName . '" style:family="table-column" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"><style:table-column-properties style:column-width="' . $_columnWidth . '"/></style:style>');
    }
}
