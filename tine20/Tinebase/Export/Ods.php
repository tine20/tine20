<?php
/**
 * Tinebase Ods generation class
 *
 * @package     Tinebase
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        make fonts customizable
 * @todo        add page layout (orientation landscape to styles
 * @todo        move configs to importexport definitions
 */

/**
 * Timetracker Ods generation class
 * 
 * @package     Tinebase
 * @subpackage	Export
 * 
 */
class Tinebase_Export_Ods extends OpenDocument_Document
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
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array();
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        parent::__construct(OpenDocument_Document::SPREADSHEET, NULL, Tinebase_Core::getTempDir());
        
        $this->_translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $this->_config = $this->_getExportConfig();
    }
    
    /**
     * add ods head (headline, column styles)
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param array $_config
     * 
     * @todo add filters/replacements again?
     * param Timetracker_Model_TimesheetFilter $_filter
     * param Tinebase_Record_RecordSet $timeaccounts
     */
    protected function _addHead($table, $_config/*, $_filter, $timeaccounts*/)
    {
        $columnId = 0;
        foreach($_config['fields'] as $field) {
            $column = $table->appendColumn();
            $column->setStyle('co' . $columnId);
            if($field['type'] == 'date') {
                $column->setDefaultCellStyle('ceShortDate');
            }
            $this->_addColumnStyle('co' . $columnId, $field['width']);
            
            $columnId++;
        }

        $row = $table->appendRow();
        
        // add header (replace placeholders)
        if (isset($_config['header'])) {
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_filter->toArray(), true));
            
            $locale = Tinebase_Core::get('locale');
            
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
                Zend_Date::now()->toString(Zend_Locale_Format::getDateFormat($locale), $locale),
                Tinebase_Core::getUser()->accountDisplayName,
                //$this->_translate->_('Filter') . ': ' . implode(', ', $filters)
            );
            
            foreach($_config['header'] as $headerCell) {
                // replace data
                $value = preg_replace($patterns, $replacements, $headerCell);
                $cell = $row->appendCell('string', $value);                
            }
        }
        
        $row = $table->appendRow();
        
        // add table headline
        $row = $table->appendRow();
        foreach($_config['fields'] as $field) {
            $cell = $row->appendCell('string', $field['header']);
            $cell->setStyle('ceHeader');
        }
    }
    
    /**
     * add body rows
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param Tinebase_Record_RecordSet $records
     * @param array 
     */
    protected function _addBody($table, $_records, $_config)
    {
        if (isset($_config['customFields']) && $_config['customFields']) {
            // we need the sql backend if the export contains custom fields
            // @todo remove that when getMultiple() fetches the custom fields as well
            $recordBackend = new Timetracker_Backend_Timesheet();
        }
        
        $locale = Tinebase_Core::get(Tinebase_Core::LOCALE);
        
        // add timesheet rows
        $i = 0;
        foreach ($_records as $record) {
            
            // check if we need to get the complete timesheet with custom fields
            // @todo remove that when getMultiple() fetches the custom fields as well
            if (isset($_config['customFields']) && $_config['customFields']) {
                $record = $recordBackend->get($record->getId());
                Tinebase_User::getInstance()->resolveUsers($record, 'account_id');
            }
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            $row = $table->appendRow();

            foreach ($_config['fields'] as $key => $params) {
                
                $altStyle = 'ceAlternate';
                $type = $params['type'];
                
                switch($params['type']) {
                    case 'datetime':
                        // @todo add another style for datetime fields?
                        $value = ($record->$key) ? $record->$key->toString(Zend_Locale_Format::getDateFormat($locale), $locale) : '';
                        $altStyle = 'ceAlternateCentered';
                        //$type = 'date';
                        $type = 'string';
                        break;
                    case 'date':
                        $value = $record->$key;
                        $altStyle = 'ceAlternateCentered';
                        break;
                    case 'tags':
                        $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($record);
                        $value = implode(', ', $tags->name);
                        $type = 'string';
                        break;
                    default:
                        if (isset($params['custom']) && $params['custom']) {
                            // add custom fields
                            if (isset($record->customfields[$key])) {
                                $value = $record->customfields[$key];
                            } else {
                                $value = '';
                            }
                            
                        } elseif (isset($params['divisor'])) {
                            // divisor
                            $value = $record->$key / $params['divisor'];

                        } elseif (in_array($params['type'], $this->_specialFields)) {
                            // special fields
                            $value = $this->_getSpecialFieldValue($record, $params, $key);
                            $type = 'string';
                        
                        } else {
                            // all remaining
                            $value = $record->$key;
                        }
                        
                        // set special value from params
                        if (isset($params['values']) && isset($params['values'][$value])) {
                            $value = $params['values'][$value];
                        }
                        
                        // translate strings
                        if (isset($params['translate']) && $params['translate'] && $type === 'string') {
                            $value = $this->_translate->_($value);
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
     * get export config
     *
     * @return array
     */
    protected function _getExportConfig()
    {
        return array();
    }

    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string || null $key [may be used by child methods e.g. {@see Timetracker_Export_Ods::_getSpecialFieldValue)]
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $key = null)
    {
        return '';
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
