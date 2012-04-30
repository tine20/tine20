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
    public function searchHumanResources($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'HumanResources_Model_EmployeeFilter', TRUE);
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
