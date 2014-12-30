<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Customfield Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Customfield extends Tinebase_Controller_Record_Abstract
{
    /**
     * tinebase customfield controller/backend
     * 
     * @var Tinebase_Customfield
     */
    protected $_customfieldController = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName       = 'Admin';
        $this->_modelName             = 'Tinebase_Model_CustomField_Config';
        $this->_doContainerACLChecks  = FALSE;
                
        $this->_backend = new Tinebase_CustomField_Config();
        
        $this->_customfieldController = Tinebase_CustomField::getInstance();
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
            self::$_instance = new Admin_Controller_Customfield;
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
        return $this->_customfieldController->addCustomField($_record);
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
        return $this->_customfieldController->getCustomField($_id);
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
            $this->_customfieldController->deleteCustomField($id);
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

        $result = $this->_customfieldController->search($filter, NULL, FALSE, TRUE);
        if ($result->count() > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' ' . count($result) . ' records still have custom field values.');
            
            throw new Tinebase_Exception_SystemGeneric('Customfield is still in use!');
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
        $this->_customfieldController->clearCacheForConfig($updatedRecord);
    }
}
