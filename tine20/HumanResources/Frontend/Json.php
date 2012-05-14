<?php
/**
 * Tine 2.0
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *
 * This class handles all Json requests for the HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var HumanResources_Controller_Employee
     */
    protected $_controller = NULL;
    
    /**
     * user fields (created_by, ...) to resolve in _multipleRecordsToJson and _recordToJson
     *
     * @var array
     */
    protected $_resolveUserFields = array(
        'HumanResources_Model_Employee' => array('created_by', 'last_modified_by')
    );
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_controller = HumanResources_Controller_Employee::getInstance();
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchEmployees($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'HumanResources_Model_EmployeeFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getEmployee($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveEmployee($recordData)
    {    
//         $recordData['id'] = time();
        return $this->_save($recordData, $this->_controller, 'Employee');
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return string
     */
    public function deleteHumanResources($ids)
    {
        return $this->_delete($ids, $this->_controller);
    }    

    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        switch (get_class($_record)) {
            case 'HumanResources_Model_Employee':
                $_record['contact_id'] = Addressbook_Controller_Contact::getInstance()->get($_record['contact_id'])->toArray();
                $recordArray = parent::_recordToJson($_record);
                break;

//             case 'IPAccounting_Model_IPNet':
//                 $recordArray = parent::_recordToJson($_record);
//                 $recordArray['account_grants'] = $this->defaultGrants;
//                 break;
                
//             case 'IPAccounting_Model_IPAggregate':
//                 $_record['netid'] = $_record['netid'] ? $this->_ipnetController->get($_record['netid']) : $_record['netid'];
//                 $recordArray = parent::_recordToJson($_record);
//                 $recordArray['account_grants'] = $this->defaultGrants;
        }

        return $recordArray;
    }

//     /**
//      * returns multiple records prepared for json transport
//      *
//      * NOTE: we can't use parent::_multipleRecordsToJson here because of the different container handling
//      *
//      * @param Tinebase_Record_RecordSet $_records
//      * @return array data
//      */
//     protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter=NULL)
//     {
//         if (count($_records) == 0) {
//             return array();
//         }

//         switch ($_records->getRecordClassName()) {
//             case 'IPAccounting_Model_IPVolume':
//             case 'IPAccounting_Model_IPAggregate':
//                 $ipnetIds = $_records->netid;
//                 $ipnets = $this->_ipnetController->getMultiple(array_unique(array_values($ipnetIds)), true);

//                 foreach ($_records as $record) {
//                     $idx = $ipnets->getIndexById($record->netid);
//                     if ($idx !== FALSE) {
//                         $record->netid = $ipnets[$idx];
//                     } else {
//                         Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not resolve ipnet (id: ' . $record->netid . '). No permission?');
//                     }
//                 }
//                 break;
                
//             case 'IPAccounting_Model_IPNet':
//                 break;
//         }

//         $recordArray = $_records->toArray();

//         foreach($recordArray as &$rec) {
//             $rec['account_grants'] = $this->defaultGrants;
//         }
        
//         return $recordArray;
//     }
    
    
    
    
    
    
//     /**
//      * Returns registry data
//      * 
//      * @return array
//      */
//     public function getRegistryData()
//     {
//         $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer($this->_applicationName)->toArray();
//         $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainerArray['id'])->toArray();
        
//         return array(
//             'defaultContainer' => $defaultContainerArray
//         );
//     }
}
