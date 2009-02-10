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
 * @todo        add page layout (orientation landscape to styles
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
    /*
        '<style:style style:name="pm1"
                xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
                xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
            <style:page-layout-properties fo:page-width="11in" fo:page-height="8.5in" style:num-format="1" style:print-orientation="landscape" style:writing-mode="lr-tb"/>
        </style:style>',
    */
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
     * first row of body (records)
     *
     */
    protected $_firstRow = 4;
    
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
        $lastCell = count($timesheets) + $this->_firstRow - 1;
        
        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
                
        // build export table
        $table = $this->getBody()->appendTable('Timesheets');        
        $this->_addHead($table, $_filter, $timeaccounts);
        $this->_addBody($table, $timesheets, $timeaccounts);
        $this->_addFooter($table, $lastCell);
        
        // add overview table
        $this->_addOverviewTable($lastCell);
        
        // create file
        $filename = $this->getDocument();        
        return $filename;
    }
    
    /**
     * add ods head (headline, column styles)
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @param Tinebase_Record_RecordSet $timeaccounts
     */
    protected function _addHead($table, $_filter, $timeaccounts)
    {
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
        

        // add header (replace placeholders)
        $row = $table->appendRow();
        if (isset($this->_config['header'])) {
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_filter->toArray(), true));
            
            $locale = Tinebase_Core::get('locale');
            
            $patterns = array(
                '/\{date\}/', 
                '/\{user\}/',
                '/\{filter\}/'
            );
            
            $filters = array();
            //print_r($_filter->toArray());
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
            $replacements = array(
                Zend_Date::now()->toString(Zend_Locale_Format::getDateFormat($locale), $locale),
                Tinebase_Core::getUser()->accountDisplayName,
                $this->_translate->_('Filter') . ': ' . implode(', ', $filters)
            );
            
            foreach($this->_config['header'] as $headerCell) {
                // replace data
                $value = preg_replace($patterns, $replacements, $headerCell);
                $cell = $row->appendCell('string', $value);                
            }
        }
        
        $row = $table->appendRow();
        
        // add table headline
        $row = $table->appendRow();
        foreach($this->_config['fields'] as $field) {
            $cell = $row->appendCell('string', $field['header']);
            $cell->setStyle('ceHeader');
        }
    }
    
    /**
     * add single export row
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param Tinebase_Record_RecordSet $timesheets
     * @param Tinebase_Record_RecordSet $timeaccounts
     */
    protected function _addBody($table, $timesheets, $timeaccounts)
    {
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
                
                $altStyle = 'ceAlternate';
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
                        $altStyle = 'ceAlternateCentered';
                        break;
                    case 'tags':
                        $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($timesheet);
                        $value = implode(', ', $tags->name);
                        $type = 'string';
                        break;
                    default:
                        if (isset($params['custom']) && $params['custom']) {
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
                        if (isset($params['values']) && isset($params['values'][$value])) {
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
                    $value = (isset($matches[1])) ? $matches[1] : '';
                }
                
                // create cell with type and value and add style
                $cell = $row->appendCell($type, $value);

                if (isset($params['number']) && $params['number']) {
                    $cell->setStyle('numberStyle');
                    $altStyle = 'numberStyleAlternate';
                }
                
                if ($i % 2 == 1) {
                    $cell->setStyle($altStyle);
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
        $numberOfEmptyCells = ord($this->_config['sumColumn']) - 66;
        for ($i=0; $i<$numberOfEmptyCells; $i++) {
            $row->appendCell('string');
        }

        $row->appendCell('string', $this->_translate->_('Total Sum'));
        $cell = $row->appendCell('float', 0);
        // set sum for timesheet duration (for example E2:E10)
        $cell->setFormula('oooc:=SUM(' . $this->_config['sumColumn'] . $this->_firstRow . ':' . $this->_config['sumColumn'] . $lastCell . ')');   
        $cell->setStyle('ceBold');     
    }
    
    /**
     * add overview table
     *
     * @param integer $lastCell
     */
    protected function _addOverviewTable($lastCell)
    {
        $table = $this->getBody()->appendTable('Overview');
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Not billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['billableColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['billableColumn'] . $lastCell . 
            ';0;Timesheets.' . $this->_config['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Billable'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUMIF(Timesheets.' . 
            $this->_config['billableColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['billableColumn'] . $lastCell . 
            ';1;Timesheets.' . $this->_config['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['sumColumn'] . $lastCell . ')');
        #$cell->setStyle('ceBold');     
        
        $row = $table->appendRow();
        $row->appendCell('string', $this->_translate->_('Total'));
        $cell = $row->appendCell('float', 0);
        $cell->setFormula('oooc:=SUM(Timesheets.' . 
            $this->_config['sumColumn'] . $this->_firstRow . ':Timesheets.' . $this->_config['sumColumn'] . $lastCell . ')');
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
                    'header'    => $this->_translate->_('Timeaccount'),
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
                    'divisor'   => 60,
                    'number'    => TRUE,
                ),
                'is_billable_combined' => array(
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
