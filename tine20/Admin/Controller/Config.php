<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public function getBackend()
    {
        return $this->_configBackend;
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
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
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
        if (! $appConfigObject) {
            return $configRecords;
        }
        $appConfigDefinitions = $appConfigObject->getProperties();
        $appDBConfig = $this->_configBackend->search($_filter);

        foreach ($appConfigDefinitions as $name => $definition) {
            if (isset($definition['setByAdminModule']) && $definition['setByAdminModule']) {
                $configFromFile = $appConfigObject->getConfigFileSection($name);
                $configFromDb = $appDBConfig->filter('name', $name)->getFirstRecord();

                if ($configFromDb && !$configFromFile) {
                    $configRecord = $this->_mergeDefinition($configFromDb, $definition);
                    $configRecord->source = Tinebase_Model_Config::SOURCE_DB;

                } else {
                    $definition['id'] = 'virtual-' . $name;
                    $definition['application_id'] = $app->getId();
                    $definition['name'] = $name;
                    $definition['value'] = $configFromFile;
                    $definition['source'] = is_null($configFromFile) ?
                        Tinebase_Model_Config::SOURCE_DEFAULT :
                        Tinebase_Model_Config::SOURCE_FILE;

                    $configRecord = new Tinebase_Model_Config($definition);
                }

                // exclude config's which the admin can't set
                if ($configRecord->source != Tinebase_Model_Config::SOURCE_FILE) {

                    if (isset($definition['type']) && Tinebase_Config_Abstract::TYPE_RECORD === $definition['type']) {
                        $val = Tinebase_Config::resolveRecordValue(Tinebase_Config::uncertainJsonDecode(
                            $configRecord->value), $definition);
                        if ($val instanceof Tinebase_Record_Interface) {
                            $val = $val->toArray(true);
                        }

                        $configRecord->value = $val;
                    }

                    $configRecord->value = json_encode(Tinebase_Config::uncertainJsonDecode($configRecord->value));

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

        if (isset($definition['type']) && Tinebase_Config_Abstract::TYPE_RECORD === $definition['type']) {
            $val = Tinebase_Config::resolveRecordValue(Tinebase_Config::uncertainJsonDecode($configRecord->value),
                $definition);
            if ($val instanceof Tinebase_Record_Interface) {
                $val = $val->toArray(true);
            }

            $configRecord->value = $val;
        }

        $configRecord->value = json_encode(Tinebase_Config::uncertainJsonDecode($configRecord->value));

        return $configRecord;
    }



    /*************** add / update / delete lead *****************/

    protected function _inspectRecord(Tinebase_Model_Config $_config, Tinebase_Model_Application $_app)
    {
        $appConfigObject = Tinebase_Config::getAppConfig($_app->name);
        $definition = $appConfigObject->getDefinition($_config->name);

        $_config->value = json_decode($_config->value, true);

        if (isset($definition['type']) && Tinebase_Config_Abstract::TYPE_RECORD === $definition['type'] &&
                $_config->value) {
            if (is_array($_config->value) && isset($_config->value['id']) && !empty($_config->value['id'])) {
                $_config->value = $_config->value['id'];
            } else {
                $_config->value = null;
            }
        }

        if (is_array($_config->value)) {
            $_config->value = json_encode($_config->value);
        }
    }

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

        $this->_inspectRecord($_record, $app);
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

        $this->_inspectRecord($_record, $app);
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
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_data
     * @param Tinebase_Model_Pagination $_pagination
     * @throws Tinebase_Exception_NotImplemented
     */
    public function updateMultiple($_filter, $_data, $_pagination = null)
    {
        throw new Tinebase_Exception_NotImplemented('Not Implemented');
    }

    /**
     * Returns a set of leads identified by their id's
     *
     * @param $_ids
     * @param bool $_ignoreACL
     * @param Tinebase_Record_Expander $_expander
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet of $this->_modelName
     * @throws Tinebase_Exception_NotImplemented
     * @internal param array $array of record identifiers
     */
    public function getMultiple($_ids, $_ignoreACL = false, Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        throw new Tinebase_Exception_NotImplemented('Not Implemented');
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotImplemented
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        throw new Tinebase_Exception_NotImplemented('Not Implemented');
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action
     * @return int
     * @throws Tinebase_Exception_NotImplemented
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return array
     */
    public function has(array $_ids, $_getDeleted = false)
    {
        return $this->_configBackend->has($_ids, $_getDeleted);
    }

    /**
     * returns the model name
     *
     * @return string
     */
    public function getModel()
    {
        return Tinebase_Model_Config::class;
    }
}
