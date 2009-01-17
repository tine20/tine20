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
class Timetracker_Export_Ods extends Tinebase_Export_Ods
{
    /**
     * export timesheets to Ods file
     *
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @return string filename
     * 
     * @todo add specific export values
     * @todo add fields array to preferences
     */
    public function exportTimesheets($_filter) {
        
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($_filter);
        if (count($timesheets) < 1) {
            throw new Timetracker_Exception_NotFound('No Timesheets found.');
        }

        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
        
        // resolve accounts
        $accountIds = $timesheets->account_id;
        $accounts = Tinebase_User::getInstance()->getMultiple(array_unique(array_values($accountIds)));
        
        // build export array
        $fields = $this->_getExportFields();
        
        $document   = new OpenDocument_Document('SpreadSheet');
        $document->setRowStyle('ro0', 'fo:background-color', '#ccffff');
        $document->setRowStyle('altRow', 'fo:background-color', "#ccccff");
        $document->setCellStyle('ceShortDate', OpenDocument_Document::NS_STYLE, 'style:data-style-name', 'nShortDate');
        
        $table      = $document->getBody()->appendTable('Timesheets');
        $columnId = 0;
        foreach($fields as $field) {
            $column = $table->appendColumn();
            $column->setStyle('co' . $columnId);
            if($field['type'] == 'date') {
                $column->setDefaultCellStyle('ceShortDate');
            }
            $document->setColumnStyle('co' . $columnId, 'style:column-width', $field['width']);
            
            $columnId++;
        }
        
        $row = $table->appendRow();
        $row->setStyle('ro0');
        
        foreach($fields as $field) {
            $row->appendCell('string', $field['header']);
        }
        
        $i = 0;
        foreach ($timesheets as $timesheet) {
            $row = $table->appendRow();
            if($i % 2 == 1) {
                $row->setStyle('altRow');
            }
            foreach ($fields as $key => $params) {
                switch($params['type']) {
                    case 'timeaccount':
                        $value = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->$params['field'];
                        $row->appendCell('string', $value);
                        break;
                    case 'account':
                        $value = $accounts[$accounts->getIndexById($timesheet->account_id)]->$params['field'];
                        $row->appendCell('string', $value);
                        break;
                    case 'date':
                        $cell = $row->appendCell($params['type'], $timesheet->$key);
                        break;
                    case 'float':
                        $row->appendCell($params['type'], $timesheet->$key);
                        break;
                    default:
                        $row->appendCell('string', $timesheet->$key);
                }
            }
            $i++;
            
            //$timesheet->timeaccount_id = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->title;
            //$timesheet->account_id = $accounts[$accounts->getIndexById($timesheet->account_id)]->accountDisplayName;
        }
        
        $row = $table->appendRow();
        $row = $table->appendRow();
        $row->appendCell('string');
        $row->appendCell('string');
        $row->appendCell('string');
        $row->appendCell('string');
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUM([.E2:.E3])');        
        
        //$filename = parent::exportRecords($timesheets);
        #$filename = parent::exportArray($document);
        $filename = $document->getDocument();
        
        return $filename;
    }
    
    /**
     * get export fields
     * - record fieldname => headline (translated)
     *
     * @return array
     */
    protected function _getExportFields()
    {
        $translate = Tinebase_Translation::getTranslation('Timetracker');
        
        $fields = array(
            'start_date' => array(
                'header'    => $translate->_('Date'),
                'type'      => 'date', 
                'width'     => '3cm'
            ),
            'description' => array(
                'header'    => $translate->_('Description'),
                'type'      => 'default', 
                'width'     => '10cm'
            ),
            'timeaccount_id' => array(
                'header'    => $translate->_('Site'),
                'type'      => 'timeaccount', 
                'field'     => 'title', 
                'width'     => '7cm'
            ),
            'account_id' => array(
                'header'    => $translate->_('Staff Member'),
                'type'      => 'account', 
                'field'     => 'accountDisplayName', 
                'width'     => '4cm'
            ),
            'duration' => array(
                'header'    => $translate->_('Duration'),
                'type'      => 'float', 
                'width'     => '2cm'
            ),
            'is_billable' => array(
                'header'    => $translate->_('Billable'),
                'type'      => 'float', 
                'width'     => '3cm'
            ),
            'is_cleared' => array(
                'header'    => $translate->_('Cleared'),
                'type'      => 'float', 
                'width'     => '3cm'
            ),
        );
        
        return $fields;
    }
}
