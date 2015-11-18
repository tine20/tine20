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
 * Config Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Config implements Tinebase_Controller_SearchInterface, Tinebase_Controller_Record_Interface
{
    /**
     * @var Tinebase_Backend_Sql
     */
    protected $_configBackend;

    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_Config
     */
    private static $_instance = NULL;

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Config
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Config;
        }

        return self::$_instance;
    }

    private function __construct()
    {
        $this->_configBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
    }

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean|array $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        //@TODO support more appfilter combinations when needed
        $appFilter = $_filter->getFilter('application_id');
        $app = Tinebase_Application::getInstance()->getApplicationById($appFilter->getValue());
        $appname = $app->name;

        if (! Tinebase_Core::getUser()->hasRight($appname, 'admin')) {
            throw new Tinebase_Exception_AccessDenied("You do not have admin rights for $appname");
        }

        $configRecords = new Tinebase_Record_RecordSet('Tinebase_Model_Config');

        $appConfigObject = Tinebase_Config::getAppConfig($appname);
        $appConfigDefinitions = $appConfigObject->getProperties();
        $appDBConfig = $this->_configBackend->search($_filter);

        foreach ($appConfigDefinitions as $name => $definition) {
            if (array_key_exists('setByAdminModule', $definition) && $definition['setByAdminModule']) {
                $configFromFile = $appConfigObject->getConfigFileSection($name);
                $configFromDb = $appDBConfig->filter('name', $name)->getFirstRecord();

                if ($configFromDb && !$configFromFile) {
                    $configRecord = $this->_mergeDefinition($configFromDb, $definition);
                    $configRecord->source = Tinebase_Model_Config::SOURCE_DB;

                } else {
                    $definition['id'] = 'virtual-' . $name;
                    $definition['application_id'] = $app->getId();
                    $definition['name'] = $name;
                    $definition['value'] = json_encode($configFromFile);
                    $definition['source'] = is_null($configFromFile) ?
                        Tinebase_Model_Config::SOURCE_DEFAULT :
                        Tinebase_Model_Config::SOURCE_FILE;

                    $configRecord = new Tinebase_Model_Config($definition);
                }

                // exclude config's which the admin can't set
                if ($configRecord->source != Tinebase_Model_Config::SOURCE_FILE) {
                    $configRecords->addRecord($configRecord);
                }
            }
        }

        return $configRecords;
    }

    /**
     * merge definition values into config record
     *
     * @param Tinebase_Model_Config   $configFromDb
     * @param array                   $definition
     * @return Tinebase_Model_Config
     */
    protected function _mergeDefinition($configFromDb, $definition)
    {
        foreach($definition as $key => $value) {
            if ($configFromDb->has($key)) {
                $configFromDb->{$key} = $value;
            }
        }

        return $configFromDb;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function get($_id)
    {
        $configRecord = $this->_configBackend->get($_id);

        $app = Tinebase_Application::getInstance()->getApplicationById($configRecord->application_id);
        if (! Tinebase_Core::getUser()->hasRight($app->name, 'admin')) {
            throw new Tinebase_Exception_AccessDenied("You do not have admin rights for $app->name");
        }

        $appConfigObject = Tinebase_Config::getAppConfig($app->name);
        $definition = $appConfigObject->getDefinition($configRecord->name);

        $this->_mergeDefinition($configRecord, $definition);
        $configRecord->source = Tinebase_Model_Config::SOURCE_DB;

        return $configRecord;
    }



    /*************** add / update / delete lead *****************/

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $app = Tinebase_Application::getInstance()->getApplicationById($_record->application_id);
        if (! Tinebase_Core::getUser()->hasRight($app->name, 'admin')) {
            throw new Tinebase_Exception_AccessDenied("You do not have admin rights for $app->name");
        }

        $createdRecord = $this->_configBackend->create($_record);

        return $this->get($createdRecord->getId());
    }

    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $app = Tinebase_Application::getInstance()->getApplicationById($_record->application_id);
        if (! Tinebase_Core::getUser()->hasRight($app->name, 'admin')) {
            throw new Tinebase_Exception_AccessDenied("You do not have admin rights for $app->name");
        }

        $this->_configBackend->update($_record);

        return $this->get($_record->getId());
    }



    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $this->_configBackend->delete($_ids);
    }


    /**
     * update multiple records
     *
     * @param   Tinebase_Model_Filter_FilterGroup $_filter
     * @param   array $_data
     * @return  integer number of updated records
     */
    public function updateMultiple($_what, $_data)
    {
        throw new Tinebase_Exception_NotImplemented('Not Implemented');
    }

    /**
     * Returns a set of leads identified by their id's
     *
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids)
    {
        throw new Tinebase_Exception_NotImplemented('Not Implemented');
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        throw new Tinebase_Exception_NotImplemented('Not Implemented');
    }
}