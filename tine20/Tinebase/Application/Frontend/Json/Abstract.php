<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id:Abstract.php 5090 2008-10-24 10:30:05Z p.schuele@metaways.de $
 */


/**
 * Abstract class for an Tine 2.0 application with Json interface
 * Each tine application must extend this class to gain an native tine 2.0 user
 * interface.
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Application_Frontend_Json_Abstract extends Tinebase_Application_Frontend_Abstract implements Tinebase_Application_Frontend_Json_Interface
{
    /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        return array();
    }

    /**
     * Search for records matching given arguments
     *
     * @param string $_filter json encoded
     * @param string $_paging json encoded
     * @param Tinebase_Application_Controller_Record_Interface $_controller the record controller
     * @param string $_filterModel the class name of the filter model to use
     * @return array
     */
    protected function _search($_filter, $_paging, Tinebase_Application_Controller_Record_Interface $_controller, $_filterModel)
    {
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r(Zend_Json::decode($filter), true));
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r(Zend_Json::decode($paging), true));
        
        $filter = new $_filterModel(Zend_Json::decode($_filter));
        $pagination = new Tinebase_Model_Pagination(Zend_Json::decode($_paging));
        
        $records = $_controller->search($filter, $pagination);
        
        // set timezone
        $records->setTimezone(Zend_Registry::get('userTimeZone'));
                
        return array(
            'results'       => $records->toArray(),
            'totalcount'    => $_controller->searchCount($filter)
        );
    }
    
    /**
     * creates/updates a record
     *
     * @param  $_recordData
     * @param  $_modelName
     * @return array created/updated record
     */
    protected function _save($_recordData, Tinebase_Application_Controller_Record_Interface $_controller, $_modelName)
    {
        $record = new $_modelName(array(), TRUE);
        $record->setFromJsonInUsersTimezone($_recordData);
        //Zend_Registry::get('logger')->debug(print_r($record->toArray(),true));
        
        $savedRecord = (empty($contact->id)) ? 
            $_controller->create($record): 
            $_controller->update($record);

        return $savedRecord;
    }

    /**
     * returns task prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record, $_resolveContainer = FALSE)
    {
        $_record->setTimezone(Tinebase_Core::get('userTimeZone'));
        $_record->bypassFilters = true;
        $recordArray = $_record->toArray();
        
        if ($_resolveContainer) {
            $recordArray['container_id'] = Tinebase_Container::getInstance()->getContainerById($_task->container_id)->toArray();
            $recordArray['container_id']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $_task->container_id)->toArray();
        }
        return $recordArray;
    }
}
