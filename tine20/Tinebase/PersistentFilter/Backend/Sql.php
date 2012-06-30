<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for persistent filters
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Tinebase_PersistentFilter_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'filter';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_PersistentFilter';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    /**
     * default column(s) for count
     * MOD set $_defaultCountCol to id
     * @var string
     */
    protected $_defaultCountCol = 'id';

    /**
     * returns persistent filter identified by id
     * 
     * @param  string $_id
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public static function getFilterById($_id)
    {
        $obj = new Tinebase_PersistentFilter();
        $persistentFilter = $obj->get($_id);
        
        return $persistentFilter->filters;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        if (is_object($_record->filters)) {
            $_record->filters->removeId();
        }
        $rawData = $_record->toArray();
        $rawData['filters'] = Zend_Json::encode($rawData['filters']);
        
        return $rawData;
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_data
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawData)
    {
        $_rawData['filters'] = Zend_Json::decode($_rawData['filters']);
        return new $this->_modelName($_rawData, true);
    }
    
    /**
     * converts raw data from adapter into a set of records
     *
     * @param  array $_rawDatas of arrays
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecordSet(array $_rawDatas)
    {
        foreach($_rawDatas as $idx => $rawData) {
            $_rawDatas[$idx]['filters'] = Zend_Json::decode($rawData['filters']);
        }
        return new Tinebase_Record_RecordSet($this->_modelName, $_rawDatas, true);
    }
}
