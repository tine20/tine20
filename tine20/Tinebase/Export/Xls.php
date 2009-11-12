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
 * @todo        set metadata
 * @todo        allow templates
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
class Tinebase_Export_Xls extends PHPExcel
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
     * current row number
     * 
     * @var integer
     */
    protected $_currentRowIndex = 0;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $this->_config = Tinebase_Config::getInstance()->getConfigAsArray(
            Tinebase_Model_Config::XLSEXPORTCONFIG, 
            $this->_applicationName, 
            $this->_getDefaultConfig()
        );
        
        $this->_currentRowIndex = 1;
    }
    
    /**
     * export records to Xls file
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return void
     */
    protected function _generate(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_SearchInterface $_controller)
    {
        $this->_addHead();
        
        $records = $_controller->search($_filter);
        $this->_addBody($records);
    }
    
    /**
     * add xls head (headline, column styles)
     */
    protected function _addHead()
    {
        $columnId = 0;
        foreach($this->_config['fields'] as $field) {
            $this->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $field['header']);
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
        $locale = Tinebase_Core::get(Tinebase_Core::LOCALE);
        
        // add record rows
        $i = 0;
        foreach ($_records as $record) {
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            
            $columnId = 0;
            foreach ($this->_config['fields'] as $key => $params) {
                switch($params['type']) {
                    case 'datetime':
                        $value = ($record->$key) ? $record->$key->toString(Zend_Locale_Format::getDateFormat($locale), $locale) : '';
                        break;
                    default:
                        $value = $record->$key;
                }
                
                $this->getActiveSheet()->setCellValueByColumnAndRow($columnId++, $this->_currentRowIndex, $value);
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
}
