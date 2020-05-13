<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * EmailAccount Controller for Admin application
 *
 * just a wrapper for Tinebase_Controller_Account with additional admin acl
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_ImportExportDefinition extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName       = 'Admin';
        $this->_modelName             = 'Tinebase_Model_ImportExportDefinition';
        $this->_doImportExportDefinitionACLChecks = false;
        $this->_purgeRecords          = false;


        $this->_backend = Tinebase_ImportExportDefinition::getInstance();
        $this->_backend->doContainerACLChecks(false);
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
     * @var Admin_Controller_ImportExportDefinition
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_ImportExportDefinition
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_ImportExportDefinition;
        }

        return self::$_instance;
    }

    protected function _checkRight($_action)
    {
        switch ($_action) {
            case self::ACTION_GET:
                $this->checkRight(Admin_Acl_Rights::VIEW_IMPORTEXPORTDEFINITIONS);
                break;
            case self::ACTION_CREATE:
            case self::ACTION_UPDATE:
            case self::ACTION_DELETE:
                $this->checkRight(Admin_Acl_Rights::MANAGE_IMPORTEXPORTDEFINITIONS);
                break;
            default;
                break;
        }

        parent::_checkRight($_action);
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_ImportExportDefinitionId
     * @param bool         $_getRelatedData
     * @param bool $_getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    public function get($_id, $_ImportExportDefinitionId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE)
    {
        $this->_checkRight('get');

        $ImportExportDefinition = $this->_backend->get($_id);

        return $ImportExportDefinition;
    }

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $this->_checkRight('get');

        return $this->_backend->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $this->_checkRight('get');

        return $this->_backend->searchCount($_filter, $_action);
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        $this->_checkRight('create');

        $ImportExportDefinition = $this->_backend->create($_record);

        return $ImportExportDefinition;
    }

    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   array $_additionalArguments
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record, $_additionalArguments = array())
    {
        $this->_checkRight('update');

        $ImportExportDefinition = $this->_backend->update($_record);

        return $ImportExportDefinition;
    }

    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param   array array of record identifiers
     * @return void
     */
    public function delete($_ids)
    {
        $this->_checkRight('delete');

        $this->_backend->delete($_ids);
    }

}