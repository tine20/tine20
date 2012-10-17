<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Json Persistent Filter class
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 */
class Tinebase_Frontend_Json_PersistentFilter extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'Tinebase';
    
    /**
     * Search for PersistentFilters matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchPersistentFilter($filter, $paging)
    {
        return parent::_search($filter, $paging, Tinebase_PersistentFilter::getInstance(), 'Tinebase_Model_PersistentFilterFilter');
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getPersistentFilter($id)
    {
        return $this->_get($id, Tinebase_PersistentFilter::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function savePersistentFilter($recordData)
    {
        return $this->_save($recordData, Tinebase_PersistentFilter::getInstance(), 'PersistentFilter');
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return string
     */
    public function deletePersistentFilters($ids)
    {
        return $this->_delete($ids, Tinebase_PersistentFilter::getInstance());
    }
    
    /**
     * returns registry data of PersistentFilter.
     *
     * @return array
     */
    public static function getAllPersistentFilters()
    {
        if (Tinebase_Core::isRegistered(Tinebase_Core::USER)) {
            $obj = new Tinebase_Frontend_Json_PersistentFilter();
            
            // return only filters of activated apps
            $applicationIds = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->getArrayOfIds();
            
            return $obj->searchPersistentFilter(array(
                array('field' => 'account_id',      'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()),
                array('field' => 'application_id',  'operator' => 'in',     'value' => $applicationIds),
            ), NULL);
        }
    }
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $recordSet = new Tinebase_Record_RecordSet('Tinebase_Model_PersistentFilter', array($_record));
        
        return array_value(0, $this->_multipleRecordsToJson($recordSet));
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @param Tinebase_Model_Filter_FilterGroup
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        $result = parent::_multipleRecordsToJson($_records, $_filter);
        
        foreach($result as $idx => $recordArray) {
            $recordIdx = $_records->getIndexById($recordArray['id']);
            try {
                $result[$idx]['filters'] = $_records[$recordIdx]->filters->toArray(TRUE);
            } catch (Exception $e) {
                // skip filter
                unset ($result[$idx]);
            }
        }
        
        return array_values($result);
    }
}
