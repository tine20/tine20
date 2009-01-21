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
     * export config array
     *
     * @var array
     */
    protected $_config = array();
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        parent::__construct(OpenDocument_Document::SPREADSHEET);
        
        $this->_translate = Tinebase_Translation::getTranslation('Timetracker');
        $this->_config = $this->_getExportConfig();
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
        
        // build export table
        $table = $this->_addHead();
        $this->_addBody($table, $timesheets);
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
     * @return OpenDocument_SpreadSheet_Table
     */
    protected function _addHead()
    {
        $table = $this->getBody()->appendTable('Timesheets');

        $columnId = 0;
        foreach($this->_config['fields'] as $field) {
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
        foreach($this->_config['fields'] as $field) {
            $cell = $row->appendCell('string', $field['header']);
            $cell->setStyle('ceHeader');
        }
        
        return $table;
    }
    
    /**
     * add single export row
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param Tinebase_Record_RecordSet $timesheets
     */
    protected function _addBody($table, $timesheets)
    {
        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
        
        // resolve accounts
        $accountIds = $timesheets->account_id;
        $accounts = Tinebase_User::getInstance()->getMultiple(array_unique(array_values($accountIds)));

        if ($this->_config['customFields']) {
            // we need the sql backend if the export contains custom fields
            // @todo remove that when getMultiple() fetches the custom fields as well
            $timesheetBackend = new Timetracker_Backend_Timesheet();
        }
        
        // add timesheet rows
        $i = 0;
        foreach ($timesheets as $timesheet) {
            
            // check if we need to get the complete timesheet with custom fields
            // @todo remove that when getMultiple() fetches the custom fields as well
            if ($this->_config['customFields']) {
                $timesheet = $timesheetBackend->get($timesheet->getId());
            }
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($timesheet->toArray(), true));
            
            $row = $table->appendRow();

            foreach ($this->_config['fields'] as $key => $params) {
                
                $style = 'ceAlternate';
                $type = $params['type'];
                
                switch($params['type']) {
                    case 'timeaccount':
                        $value = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->$params['field'];
                        $type = 'string';
                        break;
                    case 'account':
                        $value = $accounts[$accounts->getIndexById($timesheet->account_id)]->$params['field'];
                        $type = 'string';
                        break;
                    case 'date':
                        $value = $timesheet->$key;
                        $style = 'ceAlternateCentered';
                        break;
                    default:
                        if ($params['custom']) {
                            // add custom fields
                            if (isset($timesheet->customfields[$key])) {
                                $value = $timesheet->customfields[$key];
                            } else {
                                $value = '';
                            }
                            
                        } else {
                            // all remaining
                            $value = (isset($params['divisor'])) ? $timesheet->$key / $params['divisor'] : $timesheet->$key;
                        }
                        
                        // set special value from params
                        if (isset($params['values'])) {
                            $value = $params['values'][$value];
                        }
                        
                        break;
                }
                
                // check for replacements
                if (isset($params['replace'])) {
                    $value = preg_replace($params['replace']['pattern'], $params['replace']['replacement'], $value);
                }

                // check for matches
                if (isset($params['match'])) {
                    preg_match($params['match'], $value, $matches);
                    $value = $matches[1];
                }
                
                // create cell with type and value and add style
                $cell = $row->appendCell($type, $value);
                    
                if ($i % 2 == 1) {
                     $cell->setStyle($style);
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
        $row->appendCell('string', $this->_translate->_('Total Sum'));
        $cell = $row->appendCell('float', 0);
        // set sum for timesheet duration (for example E2:E10)
        $cell->setFormula('oooc:=SUM(' . $this->_config['sumColumn'] . '2:' . $this->_config['sumColumn'] . $lastCell . ')');   
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
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['billableColumn'] . '2:Timesheets.' . $this->_config['billableColumn'] . $lastCell . 
            ';0;Timesheets.' . $this->_config['sumColumn'] . '2:Timesheets.' . $this->_config['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['billableColumn'] . '2:Timesheets.' . $this->_config['billableColumn'] . $lastCell . 
            ';1;Timesheets.' . $this->_config['sumColumn'] . '2:Timesheets.' . $this->_config['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Total'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUM(Timesheets.' . 
            $this->_config['sumColumn'] . '2:Timesheets.' . $this->_config['sumColumn'] . $lastCell . ')');
        $cell->setStyle('ceBold');     
    }
    
    /**
     * get export config
     * - filename should be: /config/Timetracker/export.inc.php
     * - perhaps we could get this from user preferences later
     *
     * @return array
     */
    protected function _getExportConfig()
    {
        $config = Tinebase_Core::getConfig();
        
        $exportConfig = (isset($config->timesheetExport)) ? $config->timesheetExport->toArray() : array(
            'customFields' => FALSE,
            'sumColumn' => 'E',
            'billableColumn' => 'F',
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
                'timeaccount_id' => array(
                    'header'    => $this->_translate->_('Site'),
                    'type'      => 'timeaccount', 
                    'field'     => 'title', 
                    'width'     => '7cm',
                    'replace'   => array('pattern' => "/^XYZ /", 'replacement' => '')
                ),
                'account_id' => array(
                    'header'    => $this->_translate->_('Staff Member'),
                    'type'      => 'account', 
                    'field'     => 'accountDisplayName', 
                    'width'     => '4cm'
                ),
                'duration' => array(
                    'header'    => $this->_translate->_('Duration'),
                    'type'      => 'float', 
                    'width'     => '2cm',
                    'divisor'   => 60 
                ),
                'is_billable' => array(
                    'header'    => $this->_translate->_('Billable'),
                    'type'      => 'float', 
                    'width'     => '3cm'
                ),
                'is_cleared' => array(
                    'header'    => $this->_translate->_('Cleared'),
                    'type'      => 'float', 
                    'width'     => '3cm'
                ),
            )
        );
        
        return $exportConfig;
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
