<?php
/**
 * Tinebase xls generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Ods.php 10912 2009-10-12 14:40:25Z p.schuele@metaways.de $
 * 
 * @todo        add Tinebase_Export_Abstract with common funcs (_addRelations, add notes, add special value)
 */

// set include path for phpexcel
set_include_path(dirname(dirname(dirname(__FILE__))) . '/library/PHPExcel' . PATH_SEPARATOR . get_include_path() );

/**
 * Tinebase xls generation class
 * 
 * @package     Tinebase
 * @subpackage  Export
 * 
 */
class Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * export config
     * 
     * @var array
     */
    protected $_config = array();
    
    /**
     * translation object
     *
     * @var Zend_Translate
     */
    protected $_translate;

    /**
     * locale object
     *
     * @var Zend_Locale
     */
    protected $_locale;

    /**
     * current row number
     * 
     * @var integer
     */
    protected $_currentRowIndex = 0;
    
    /**
     * the phpexcel object
     * 
     * @var PHPExcel
     */
    protected $_excelObject = NULL;
    
    /**
     * user fields to resolve
     * 
     * @var array
     */
    protected $_userFields = array('created_by', 'last_modified_by');
    
    /**
     * the constructor
     *
     */
    public function __construct($_additionalConfig = array())
    {
        $this->_translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $this->_config = Tinebase_Config::getInstance()->getConfigAsArray(
            Tinebase_Model_Config::XLSEXPORTCONFIG, 
            $this->_applicationName, 
            $this->_getDefaultConfig()
        );
        $this->_config = array_merge($this->_config, $_additionalConfig);
        $this->_locale = Tinebase_Core::get(Tinebase_Core::LOCALE);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, TRUE));
        
        // check if we need to open template file
        if (isset($this->_config['template'])) {
            $templateFilename = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . $this->_applicationName 
                . DIRECTORY_SEPARATOR . 'Export' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $this->_config['template'];
                
            if (file_exists($templateFilename)) {
                
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Using template file ' . $templateFilename);
                $this->_excelObject = PHPExcel_IOFactory::load($templateFilename);
                
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_excelObject->getProperties(), true));
                
                $this->_excelObject->setActiveSheetIndex(1);
                
            } else {
                throw new Tinebase_Exception_NotFound('Template file ' . $templateFilename . ' not found');
            }
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new PHPExcel object.');
            
            $this->_excelObject = new PHPExcel();
        }
        
        // set metadata/properties
        $this->_excelObject->getProperties()
            ->setCreator(Tinebase_Core::getUser()->accountDisplayName)
            ->setLastModifiedBy(Tinebase_Core::getUser()->accountDisplayName)
            ->setTitle('Tine 2.0 ' . $this->_applicationName . ' Export')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Export for ' . $this->_applicationName . ', generated using PHP classes.')
            ->setKeywords("tine20 openxml php")
            ->setCreated(Zend_Date::now()->toString(Zend_Locale_Format::getDateFormat($this->_locale), $this->_locale));
            
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_excelObject->getProperties(), true));
        
        $this->_currentRowIndex = 1;
    }
    
    /**
     * get excel object
     * 
     * @return PHPExcel
     */
    public function getExcelObject()
    {
        return $this->_excelObject;
    }
    
    /**
     * export records to Xls file
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return PHPExcel
     */
    protected function _generate(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_SearchInterface $_controller, $_sortBy = 'id', $_getRelations = FALSE)
    {
        $pagination = new Tinebase_Model_Pagination(array(
            'sort' => $_sortBy,
        ));
        $records = $_controller->search($_filter, $pagination, $_getRelations);
        
        // resolve users
        foreach ($this->_userFields as $field) {
            if (in_array($field, array_keys($this->_config['fields']))) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resolving users for ' . $field);
                Tinebase_User::getInstance()->resolveMultipleUsers($records, $field, TRUE);
            }
        }
        
        // add notes
        if (in_array('notes', array_keys($this->_config['fields']))) {
            Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($records, 'notes', 'Sql', FALSE);
        }

        $this->_addHead();
        $this->_addBody($records);
        
        return $this->getExcelObject();
    }
    
    /**
     * add xls head (headline, column styles)
     */
    protected function _addHead()
    {
        $columnId = 0;
        foreach($this->_config['fields'] as $field) {
            $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $field['header']);
        }
        
        $this->_currentRowIndex++;
    }
    
    /**
     * add body rows
     *
     * @param OpenDocument_SpreadSheet_Table $table
     * @param Tinebase_Record_RecordSet $records
     * @param array
     * 
     * @todo add more different data types
     * @todo add different styles?
     */
    protected function _addBody($_records)
    {
        // add record rows
        $i = 0;
        foreach ($_records as $record) {
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            $columnId = 0;
            foreach ($this->_config['fields'] as $key => $params) {
                
                // string is default type
                $params['type'] = (isset($params['type'])) ? $params['type'] : 'string';
                
                switch($params['type']) {
                    case 'datetime':
                        $value = ($record->$key) ? $record->$key->toString(Zend_Locale_Format::getDateFormat($this->_locale), $this->_locale) : '';
                        break;
                    case 'user':
                        $value = ($record->$key) ? $record->$key->accountDisplayName : '';
                        break;
                    case 'config':
                        $value = Tinebase_Config::getOptionString($record, $key);
                        break;
                    case 'relation':
                        $value = $this->_addRelations($record, $key, $params['field']);
                        break;
                    case 'notes':
                        $value = $this->_addNotes($record);
                        break;
                    case 'special':
                        $value = $this->_addSpecialValue($record, $key);
                        break;
                    default:
                        $value = $record->$key;
                }
                
                $this->_excelObject->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $value);
            }
            
            $i++;
            $this->_currentRowIndex++;
        }
    }
    
    /**
     * get default export config
     * 
     * @return array
     */
    protected function _getDefaultConfig()
    {
        return array();
    }
    
    /**
     * add relation values from related records
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_fieldName
     * @param string $_recordField
     * @return string
     * 
     * @todo    add _getSummary()?
     */
    protected function _addRelations(Tinebase_Record_Abstract $_record, $_fieldName, $_recordField)
    {
        $_record->relations->addIndices(array('type'));
        $matchingRelations = $_record->relations->filter('type', $_fieldName);
        
        $resultArray = array();
        foreach ($matchingRelations as $relation) {
            $resultArray[] = $relation->related_record->{$_recordField};
        }
        
        $result = implode(';', $resultArray);
        
        return $result;
    }

    /**
     * add relation values from related records
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_fieldName
     * @param string $_recordField
     * @return string
     * 
     * @todo use get multiple notes of records
     */
    protected function _addNotes(Tinebase_Record_Abstract $_record)
    {
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_record->notes->toArray(), true));
        
        $resultArray = array();
        foreach ($_record->notes as $note) {
            $date = $note->creation_time->toString(Zend_Locale_Format::getDateFormat($this->_locale), $this->_locale);
            $resultArray[] = $date . ' - ' . $note->note;
        }
        
        $result = implode(';', $resultArray);
        return $result;
    }
    
    /**
     * special field value function (overwrite that)
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_fieldName
     * @return string
     */
    protected function _addSpecialValue(Tinebase_Record_Abstract $_record, $_fieldName)
    {
        return '';
    }
}
