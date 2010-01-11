<?php
/**
 * Timetracker Ods generation class
 *
 * @package     Timetracker
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        allow template files
 */

/**
 * Timetracker Ods generation class
 * 
 * @package     Timetracker
 * @subpackage	Export
 * 
 */
class Timetracker_Export_Ods extends Tinebase_Export_Ods
{
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * resolved records
     *
     * @var array of Tinebase_Record_RecordSet
     */
    protected $_resolvedRecords = array();
    
    /**
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array('timeaccount', 'account_id', 'created_by');
    
    /**
     * generate export
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return string filename
     */
    public function generate(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        switch ($_filter->getModelName()) {
            case 'Timetracker_Model_Timesheet' :
                $this->_openDocumentObject = new OpenDocument_Document(OpenDocument_Document::SPREADSHEET, NULL, Tinebase_Core::getTempDir());
                $result = $this->exportTimesheets($_filter);
                break;
            case 'Timetracker_Model_Timeaccount' :
                $this->_openDocumentObject = new OpenDocument_Document(OpenDocument_Document::SPREADSHEET, NULL, Tinebase_Core::getTempDir());
                $result = $this->exportTimeaccounts($_filter);
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('Invalid filter model.');
        }
        return $result;
    }
    
    /**
     * export timesheets to Ods file
     *
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @return string filename
     */
    public function exportTimesheets(Timetracker_Model_TimesheetFilter $_filter) {
        
        // get timesheets by filter
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($_filter, new Tinebase_Model_Pagination(array(
            'sort'  => 'start_date',
            'dir'   => 'DESC'
        )));
        $lastCell = count($timesheets) + $this->_firstRow - 1;
        
        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $this->_resolvedRecords['timeaccounts'] = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
        
        Tinebase_User::getInstance()->resolveMultipleUsers($timesheets, 'account_id', true);
        
        // build export table
        $table = $this->_openDocumentObject->getBody()->appendTable('Timesheets');        
        $this->_addHead($table, $this->_config['timesheets']/*, $_filter*/);
        $this->_addBody($table, $timesheets, $this->_config['timesheets']);
        $this->_addFooter($table, $lastCell);
        
        // add overview table
        $this->_addOverviewTable($lastCell);
        
        // create file
        $filename = $this->_openDocumentObject->getDocument();        
        return $filename;
    }
    
    /**
     * export timeaccounts to Ods file
     *
     * @param Timetracker_Model_TimeaccountFilter $_filter
     * @return string filename
     */
    public function exportTimeaccounts(Timetracker_Model_TimeaccountFilter $_filter) {
        
        // get $timeaccounts by filter
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->search($_filter);
        $lastCell = count($timeaccounts) + $this->_firstRow - 1;
        
        Tinebase_User::getInstance()->resolveMultipleUsers($timeaccounts, 'created_by', true);
        
        // build export table
        $table = $this->_openDocumentObject->getBody()->appendTable($this->_translate->_('Timesheets'));        
        $this->_addHead($table, $this->_config['timeaccounts']);
        $this->_addBody($table, $timeaccounts, $this->_config['timeaccounts']);
        //$this->_addFooter($table, $lastCell);
        
        // create file
        $filename = $this->_openDocumentObject->getDocument();        
        return $filename;
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
        $numberOfEmptyCells = ord($this->_config['timesheets']['sumColumn']) - 66;
        for ($i=0; $i<$numberOfEmptyCells; $i++) {
            $row->appendCell('string');
        }

        $row->appendCell('string', $this->_translate->_('Total Sum'));
        $cell = $row->appendCell('float', 0);
        // set sum for timesheet duration (for example E2:E10)
        $cell->setFormula('oooc:=SUM(' . $this->_config['timesheets']['sumColumn'] . $this->_firstRow . ':' . $this->_config['timesheets']['sumColumn'] . $lastCell . ')');   
        $cell->setStyle('ceBold');     
    }
    
    /**
     * add overview table
     *
     * @param integer $lastCell
     */
    protected function _addOverviewTable($lastCell)
    {
        $table = $this->_openDocumentObject->getBody()->appendTable('Overview');
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Not billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['timesheets']['billableColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['timesheets']['billableColumn'] . $lastCell . 
            ';0;Timesheets.' . $this->_config['timesheets']['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['timesheets']['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['timesheets']['billableColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['timesheets']['billableColumn'] . $lastCell . 
            ';1;Timesheets.' . $this->_config['timesheets']['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['timesheets']['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Total'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUM(Timesheets.' . 
            $this->_config['timesheets']['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['timesheets']['sumColumn'] . $lastCell . ')');
        $cell->setStyle('ceBold');     
    }
    
    /**
     * get export config
     *
     * @return array
     */
    protected function _getExportConfig()
    {
        $defaultTsConfig = array(
        'default' => array(
            'header' => array(
                '{date}', 
                '{user}',
            ),
            'customFields' => FALSE,
            'sumColumn' => 'F',
            'billableColumn' => 'G',
            'overviewTable' => TRUE,
            'fields' => array(
                'start_date' => array(
                    'header'    => $this->_translate->_('Date'),
                    'type'      => 'date', 
                    'width'     => '2,5cm'
                ),
                'description' => array(
                    'header'    => $this->_translate->_('Description'),
                    'type'      => 'string', 
                    'width'     => '10cm'
                ),
                'timeaccount_number' => array(
                    'header'    => $this->_translate->_('Timeaccount Number'),
                    'type'      => 'timeaccount', 
                    'field'     => 'number', 
                    'width'     => '5cm',
                ),                
                'timeaccount_id' => array(
                    'header'    => $this->_translate->_('Timeaccount'),
                    'type'      => 'timeaccount', 
                    'field'     => 'title', 
                    'width'     => '7cm',
                    'replace'   => array('pattern' => "/^XYZ /", 'replacement' => '')
                ),
                'account_id' => array(
                    'header'    => $this->_translate->_('Staff Member'),
                    'type'      => 'account_id', 
                    'field'     => 'accountDisplayName', 
                    'width'     => '4cm'
                ),
                'duration' => array(
                    'header'    => $this->_translate->_('Duration'),
                    'type'      => 'float', 
                    'width'     => '2cm',
                    'divisor'   => 60,
                    'number'    => TRUE,
                ),
                'is_billable_combined' => array(
                    'header'    => $this->_translate->_('Billable'),
                    'type'      => 'float', 
                    'width'     => '3cm'
                ),
                'is_cleared_combined' => array(
                    'header'    => $this->_translate->_('Cleared'),
                    'type'      => 'float', 
                    'width'     => '3cm'
                ),
            ),
        ),
        );
        
        // add template config (same as default + template file definition
        $defaultTsConfig['template'] = $defaultTsConfig['default'];
        $defaultTsConfig['template']['template'] = 'timesheet_export_default.ods';
        
        $defaultTaConfig = array(
            'header' => array(
                '{date}', 
                '{user}',
            ),
            'fields' => array(
                'number' => array(
                    'header'    => $this->_translate->_('Number'),
                    'type'      => 'string', 
                    'width'     => '2,5cm'
                ),
                'title' => array(
                    'header'    => $this->_translate->_('Title'),
                    'type'      => 'string', 
                    'width'     => '2,5cm'
                ),
                'description' => array(
                    'header'    => $this->_translate->_('Description'),
                    'type'      => 'string', 
                    'width'     => '10cm'
                ),
                'created_by' => array(
                    'header'    => $this->_translate->_('Created By'),
                    'type'      => 'created_by', 
                    'field'     => 'accountDisplayName', 
                    'width'     => '4cm'
                ),
                'creation_time' => array(
                    'header'    => $this->_translate->_('Creation Date'),
                    'type'      => 'datetime', 
                    'width'     => '2,5cm'
                ),
                'status' => array(
                    'header'    => $this->_translate->_('Status'),
                    'type'      => 'string',
                    'translate' => TRUE, 
                    'width'     => '3cm'
                ),
                'is_billable' => array(
                    'header'    => $this->_translate->_('Billable'),
                    'type'      => 'float', 
                    'width'     => '3cm'
                ),
                'billed_in' => array(
                    'header'    => $this->_translate->_('Cleared In'),
                    'type'      => 'string', 
                    'width'     => '3cm'
                ),
                'is_open' => array(
                    'header'    => $this->_translate->_('Open'),
                    'type'      => 'float', 
                    'width'     => '3cm'
                ),
            )
        );
        
        // get config from db and set defaults
        $exportConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::ODSEXPORTCONFIG, 'Timetracker', array());
        if (! isset($exportConfig['timesheets'])) {
            $exportConfig['timesheets'] = $defaultTsConfig;
        }
        if (! isset($exportConfig['timeaccounts'])) {
            $exportConfig['timeaccounts'] = $defaultTaConfig;
        }
        
        // check in prefs which ts pref to use
        $tsExportConfigPref = Tinebase_Core::getPreference('Timetracker')->getValue(Timetracker_Preference::TSODSEXPORTCONFIG);
        if (array_key_exists($tsExportConfigPref, $exportConfig['timesheets'])) {
            $exportConfig['timesheets'] = $exportConfig['timesheets'][$tsExportConfigPref];
        } else {
            $exportConfig['timesheets'] = $exportConfig['timesheets']['default'];
        }
        
        return $exportConfig;
    }    
    
    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string $key
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $key = null)
    {
    	if (is_null($key)) {
    		throw new Tinebase_Exception_InvalidArgument('Missing required parameter $key');
    	}
    	
        $value = '';
        
        switch($_param['type']) {
            case 'timeaccount':
                $value = $this->_resolvedRecords['timeaccounts'][$this->_resolvedRecords['timeaccounts']->getIndexById($_record->timeaccount_id)]->$_param['field'];
                break;
            case 'account_id':
            case 'created_by':
                $value = $_record->$_param['type']->$_param['field'];
                break;
        }        
        return $value;
    }
}
