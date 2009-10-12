<?php
/**
 * Crm Ods generation class
 *
 * @package     Crm
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        make it work
 */

/**
 * Crm Ods generation class
 * 
 * @package     Crm
 * @subpackage	Export
 * 
 */
class Crm_Export_Ods extends Tinebase_Export_Ods
{
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Crm';
    
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
    protected $_specialFields = array(/*'created_by'*/);
    
    /**
     * export leads to Ods file
     *
     * @param Crm_Model_LeadFilter $_filter
     * @return string filename
     */
    public function generate(Crm_Model_LeadFilter $_filter) {
        
        // get leads by filter
        $leads = Crm_Controller_Lead::getInstance()->search($_filter);
        $lastCell = count($leads) + $this->_firstRow - 1;
        
        //Tinebase_User::getInstance()->resolveMultipleUsers($leads, 'account_id', true);
        
        // build export table
        $table = $this->getBody()->appendTable('Leads');        
        $this->_addHead($table, $this->_config['leads']);
        $this->_addBody($table, $leads, $this->_config['leads']);
        $this->_addFooter($table, $lastCell);
        
        // add overview table
        //$this->_addOverviewTable($lastCell);
        
        // create file
        $filename = $this->getDocument();        
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
        /*
        $row = $table->appendRow();
        $row = $table->appendRow();
        $numberOfEmptyCells = ord($this->_config['leads']['sumColumn']) - 66;
        for ($i=0; $i<$numberOfEmptyCells; $i++) {
            $row->appendCell('string');
        }

        $row->appendCell('string', $this->_translate->_('Total Sum'));
        $cell = $row->appendCell('float', 0);
        // set sum for timesheet duration (for example E2:E10)
        $cell->setFormula('oooc:=SUM(' . $this->_config['leads']['sumColumn'] . $this->_firstRow . ':' . $this->_config['leads']['sumColumn'] . $lastCell . ')');   
        $cell->setStyle('ceBold');
        */     
    }
    
    /**
     * add overview table
     *
     * @param integer $lastCell
     */
    protected function _addOverviewTable($lastCell)
    {
        /*
        $table = $this->getBody()->appendTable('Overview');
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Not billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['leads']['billableColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['leads']['billableColumn'] . $lastCell . 
            ';0;Timesheets.' . $this->_config['leads']['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['leads']['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['leads']['billableColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['leads']['billableColumn'] . $lastCell . 
            ';1;Timesheets.' . $this->_config['leads']['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['leads']['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Total'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUM(Timesheets.' . 
            $this->_config['leads']['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['leads']['sumColumn'] . $lastCell . ')');
        $cell->setStyle('ceBold');
        */
    }
    
    /**
     * get export config
     *
     * @return array
     * 
     * @todo get this from db
     */
    protected function _getExportConfig()
    {
        /*
        $config = Tinebase_Core::getConfig();
        
        $exportConfig['leads'] = (isset($config->timesheetExport)) ? $config->timesheetExport->toArray() : array(
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
            )
        );
        
        // timeaccounts export config
        $exportConfig['timeaccounts'] = array(
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
        
        return $exportConfig;
        */
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
        /*
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
        */
    }
}
