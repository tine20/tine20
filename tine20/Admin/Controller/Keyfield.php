<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Keyfield Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Keyfield extends Tinebase_Controller_Record_Abstract
{
    /**
     * tinebase customfield controller/backend
     * 
     * @var Tinebase_Keyfield
     */
    protected $_keyfieldController = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName       = 'Admin';
        $this->_modelName             = 'Tinebase_Config_KeyField';
        $this->_doContainerACLChecks  = FALSE;
                
        $this->_backend = new Tinebase_CustomField_Config();
        
        $this->_keyfieldController = Tinebase_CustomField::getInstance();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_Container
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Container
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Keyfield;
        }
        
        return self::$_instance;
    }
    
    /**************** overriden methods ***************************/
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        return $this->_keyfieldController->addCustomField($_record);
    }
    
    /**
     * get by id
     *
     * @param string $_id
     * @return Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function get($_id)
    {
        return $this->_keyfieldController->getCustomField($_id);
    }
    
    /**
     * Deletes a set of records.
     *  
     * @param   array array of record identifiers
     * @return  array
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception
     */
    public function delete($ids)
    {
        $this->_checkCFUsage($ids);
        foreach ((array) $ids as $id) {
            $this->_keyfieldController->deleteCustomField($id);
        }
        
        return (array) $ids;
    }
    
    /**
     * checks if customfield(s) are still in use (have values)
     * 
     * @param array $ids
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkCFUsage($ids)
    {
        $filter = new Tinebase_Model_CustomField_ValueFilter(array(array(
            'field'     => 'customfield_id',
            'operator'  => 'in',
            'value'     => (array) $ids
        )));

        $result = $this->_keyfieldController->search($filter, NULL, FALSE, TRUE);
        if ($result->count() > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' ' . count($result) . ' records still have custom field values.');
            
            throw new Tinebase_Exception_SystemGeneric('Keyfield is still in use!');
        }
    }
    
    /**
    * inspect update of one record (after update)
    *
    * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
    * @param   Tinebase_Record_Interface $record          the update record
    * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
    * @return  void
    */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        $this->_keyfieldController->clearCacheForConfig($updatedRecord);
    }
}
