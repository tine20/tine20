<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Path
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */


/**
 * class Tinebase_Path_Backend_Sql
 *
 *
 * @package     Tinebase
 * @subpackage  Path
 */
class Tinebase_Path_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'path';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Path';

    protected static $_modelStore = array();
    protected static $_shadowPathMapping = array();
    protected static $_idToShadowPath = array();
    protected static $_newIds = array();
    protected static $_toDelete = array();

    protected static $_registeredCallback = false;

    protected static $_delayDisabled = false;


    /***
     ** get methods
     **
     ***/

    /**
     * Gets one entry (by id)
     *
     * @param integer|Tinebase_Record_Interface $_id
     * @param $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public function get($_id, $_getDeleted = FALSE)
    {
        if (true !== static::$_delayDisabled) {
            if (isset(static::$_modelStore[$_id])) {
                return static::$_modelStore[$_id];
            }
            if (isset(static::$_toDelete[$_id])) {
                throw new Tinebase_Exception_NotFound('path ' . $_id . ' was already deleted');
            }
        }

        return parent::get($_id, $_getDeleted);
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
    public function getAll($_orderBy = NULL, $_orderDirection = 'ASC')
    {
        if (true === static::$_delayDisabled || (count(static::$_shadowPathMapping) == 0 && count(static::$_toDelete) == 0)) {
            return parent::getAll($_orderBy, $_orderDirection);
        }

        throw new Tinebase_Exception_NotImplemented('paths don\'t support getAll for in memory operations');
    }

    /**
     * Gets one entry (by property)
     *
     * @param  mixed  $value
     * @param  string $property
     * @param  bool   $getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public function getByProperty($value, $property = 'name', $getDeleted = FALSE)
    {
        if (true === static::$_delayDisabled || (count(static::$_shadowPathMapping) == 0 && count(static::$_toDelete) == 0)) {
            return parent::getByProperty($value, $property, $getDeleted);
        }

        if (count(static::$_modelStore) > 0) {
            foreach(static::$_modelStore as $record) {
                if (strcmp((string)$value, (string)($record->{$_property})) === 0) {
                    return $record;
                }
            }
        }

        $ret = parent::getByProperty($value, $property, $getDeleted);
        if (!isset(static::$_toDelete[$ret->getId()])) {
            return $ret;
        }

        $result = parent::getMultipleByProperty($value, $property, $getDeleted);
        foreach($result as $ret) {
            if (!isset(static::$_toDelete[$ret->getId()])) {
                return $ret;
            }
        }

        throw new Tinebase_Exception_NotFound('nothing found');
    }

    /**
     * Get multiple entries
     *
     * @param string|array $_id Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_Record_RecordSet
     *
     */
    public function getMultiple($_id, $_containerIds = NULL)
    {
        $parentResult = parent::getMultiple($_id, $_containerIds);

        if (true === static::$_delayDisabled || (count(static::$_shadowPathMapping) == 0 && count(static::$_toDelete) == 0)) {
            return $parentResult;
        }

        // filter out any emtpy values
        $ids = array_filter((array) $_id, function($value) {
            return !empty($value);
        });

        if (empty($ids)) {
            return $parentResult;
        }

        foreach ($ids as $id) {
            // replace objects with their id's
            if ($id instanceof Tinebase_Record_Interface) {
                $id = $id->getId();
            }

            if (isset(static::$_modelStore[$id])) {
                $parentResult->removeById($id);
                $parentResult->addRecord(static::$_modelStore[$id]);
            } elseif(isset(static::$_toDelete[$id])) {
                $parentResult->removeById($id);
            }
        }

        return $parentResult;
    }

    /**
     * gets multiple entries (by property)
     *
     * @param  mixed  $_value
     * @param  string $_property
     * @param  bool   $_getDeleted
     * @param  string $_orderBy        defaults to $_property
     * @param  string $_orderDirection defaults to 'ASC'
     * @return Tinebase_Record_RecordSet
     */
    public function getMultipleByProperty($_value, $_property='name', $_getDeleted = FALSE, $_orderBy = NULL, $_orderDirection = 'ASC')
    {
        $parentResult = parent::getMultipleByProperty($_value, $_property, $_getDeleted, $_orderBy, $_orderDirection);

        if (true === static::$_delayDisabled || (count(static::$_shadowPathMapping) == 0 && count(static::$_toDelete) == 0)) {
            return $parentResult;
        }

        if (count(static::$_toDelete) > 0) {
            foreach (array_keys(static::$_toDelete) as $id) {
                $parentResult->removeById($id);
            }
        }

        if (count(static::$_shadowPathMapping) > 0) {
            foreach((array)$_value as $value) {
                foreach (static::$_modelStore as $record) {
                    if (strcmp((string)$value, (string)($record->{$_property})) === 0) {
                        $parentResult->removeById($record->getId());
                        $parentResult->addRecord($record);
                    }
                }
            }
        }

        return $parentResult;
    }

    /**
     * fetch a single property for all records defined in array of $ids
     *
     * @param array|string $ids
     * @param string $property
     * @return array (key = id, value = property value)
     */
    public function getPropertyByIds($ids, $property)
    {
        if (true === static::$_delayDisabled || (count(static::$_shadowPathMapping) == 0 && count(static::$_toDelete) == 0)) {
            return parent::getPropertyByIds($ids, $property);
        }
        throw new Tinebase_Exception_NotImplemented('paths don\'t support getPropertyByIds for in memory operations');
    }

    /***
     ** create, update, delete methods
     **
     ***/

    /**
     * Creates new entry
     *
     * @param   Tinebase_Model_Path $_record
     * @return  Tinebase_Model_Path
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        if (true === static::$_delayDisabled) {
            return parent::create($_record);
        }

        if (!$_record instanceof $this->_modelName) {
            throw new Tinebase_Exception_InvalidArgument('invalid model type: $_record is instance of "' . get_class($_record) . '". but should be instance of ' . $this->_modelName);
        }

        $this->_registerCallBacks();

        $identifier = $_record->getIdProperty();
        // set uid if id is empty
        if (empty($_record->$identifier)) {
            $_record->setId($_record::generateUID());
        }

        $this->_addToModelStore($_record);

        return $_record;
    }

    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        if (true === static::$_delayDisabled) {
            return parent::update($_record);
        }

        $this->_registerCallBacks();
        $this->_addToModelStore($_record, true);

        return $_record;
    }

    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     */
    public function updateMultiple($_ids, $_data)
    {
        if (true === static::$_delayDisabled) {
            return parent::updateMultiple($_ids, $_data);
        }

        throw new Tinebase_Exception_NotImplemented('paths don\'t support updateMultiple for in memory operations');
    }

    /**
     * Deletes entries
     *
     * @param string|integer|Tinebase_Record_Interface|array $_id
     * @return void
     * @return int The number of affected rows.
     */
    public function delete($_id)
    {
        if (true === static::$_delayDisabled) {
            parent::delete($_id);
            return;
        }

        $idArray = (! is_array($_id)) ? array(Tinebase_Record_Abstract::convertId($_id, $this->_modelName)) : $_id;

        parent::delete($idArray);

        $this->_registerCallBacks();

        foreach($idArray as $id) {
            if (isset(static::$_modelStore[$id])) {
                unset(static::$_shadowPathMapping[static::$_idToShadowPath[$id]]);
                unset(static::$_idToShadowPath[$id]);
                unset(static::$_modelStore[$id]);
            }
            unset(static::$_newIds[$id]);
            static::$_toDelete[$id] = true;
        }
    }

    /**
     * deletes all entries
     */
    public function deleteAll()
    {
        $this->_db->delete($this->_tablePrefix . $this->_tableName);
        $this->_resetDelay();
    }

    /**
     * delete rows by property
     *
     * @param string|array $_value
     * @param string $_property
     * @param string $_operator (equals|in)
     * @return integer The number of affected rows.
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotImplemented
     */
    public function deleteByProperty($_value, $_property, $_operator = 'equals')
    {
        if (true === static::$_delayDisabled) {
            return parent::deleteByProperty($_value, $_property, $_operator);
        }

        parent::deleteByProperty($_value, $_property, $_operator);

        if ($_operator !== 'in') {
            $_value = array($_value);
        }

        foreach ((array)$_value as $value) {
            $this->_deleteInStoreByProp($value, $_property);
        }
    }


    /***
     ** search methods
     **
     ***/

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int|array
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        return $this->search($_filter)->count();
    }

    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string|boolean                 $_cols columns to get, * per default / use self::IDCOL or TRUE to get only ids
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')
    {
        if (true !== static::$_delayDisabled && count(static::$_shadowPathMapping) > 0) {
            if (! $_filter instanceof Tinebase_Model_PathFilter) {
                throw new Tinebase_Exception_NotImplemented('paths only supports Tinebase_Model_PathFilter for in memory operations');
            }

            $filters = $_filter->getFilterObjects();
            if (count($filters) > 1) {
                throw new Tinebase_Exception_NotImplemented('paths don\'t support complex filters for in memory operations');
            }
            reset($filters);
            /**
             * @var $filter Tinebase_Model_Filter_Abstract
             */
            $filter = current($filters);

            $field = $filter->getField();
            if ($field === 'query') {
                $field = 'path';
            }
            $operator = $filter->getOperator();
            $values = $filter->getValue();
            if (!is_array($values)) {
                $values = array($values);
            }
            if ($field === 'path') {
                $searchValues = array();
                foreach($values as $value) {
                    //replace full text meta characters
                    //$value = str_replace(array('+', '-', '<', '>', '~', '*', '(', ')', '"'), ' ', $value);
                    $value = preg_replace('#[^\w\d ]|_#u', ' ', $value);
                    // replace multiple spaces with just one
                    $value = preg_replace('# +#u', ' ', trim($value));
                    $searchValues = array_merge($searchValues, explode(' ', $value));
                }
                $values = $searchValues;
            }
            $values = array_filter($values);
        }

        $parentResult = parent::search($_filter, $_pagination, $_cols);

        if (true === static::$_delayDisabled) {
            return $parentResult;
        }

        if (count(static::$_toDelete) > 0) {
            foreach (array_keys(static::$_toDelete) as $id) {
                $parentResult->removeById($id);
            }
        }

        if (count(static::$_shadowPathMapping) > 0 && count($values) > 0) {

            if ($operator === 'equals') {
                foreach (static::$_modelStore as $id => $record) {
                    $parentResult->removeById($id);
                    foreach($values as $value) {
                        if (mb_stripos($record->{$field}, $value) === 0 && strlen($record->{$field}) === strlen($value)) {
                            $parentResult->addRecord($record);
                            break;
                        }
                    }
                }

            } elseif($operator === 'contains') {

                // we may need to do path magic here, because of full text
                if ($field === 'path') {
                    foreach (static::$_modelStore as $id => $record) {
                        $parentResult->removeById($id);
                        $fvalue = preg_replace('# +#u', ' ', trim(preg_replace('#[^\w\d ]|_#u', ' ', $record->{$field})));
                        $success = true;
                        foreach($values as $value) {
                            if (mb_stripos($fvalue, $value) === false) {
                                $success = false;
                                break;
                            }
                        }
                        if (true === $success) {
                            $parentResult->addRecord($record);
                        }
                    }

                } else {
                    foreach (static::$_modelStore as $id => $record) {
                        $parentResult->removeById($id);
                        foreach($values as $value) {
                            if (mb_stripos($record->{$field}, $value) !== false) {
                                $parentResult->addRecord($record);
                                break;
                            }
                        }
                    }
                }

            } elseif($operator === 'in') {
                foreach (static::$_modelStore as $id => $record) {
                    $parentResult->removeById($id);
                    foreach($values as $value) {
                        if (mb_stripos($record->{$field}, $value) === 0 && strlen($record->{$field}) === strlen($value)) {
                            $parentResult->addRecord($record);
                            break;
                        }
                    }
                }
            }
        }

        return $parentResult;
    }

    /***
     ** hook methods
     **
     ***/

    public function executeDelayed()
    {
        foreach(static::$_modelStore as $id => $record) {
            if (isset(static::$_newIds[$id])) {
                parent::create($record);
            } else {
                parent::update($record);
            }
        }

        if (count(static::$_toDelete) > 0) {
            $this->_db->delete($this->_tablePrefix . $this->_tableName,
                $this->_db->quoteIdentifier('id') . $this->_db->quoteInto(' IN (?)', array_keys(static::$_toDelete)));
        }

        $this->_resetDelay();
    }

    public function rollback()
    {
        $this->_resetDelay();
    }

    /***
     ** protected methods
     **
     ***/

    protected function _resetDelay()
    {
        static::$_registeredCallback = false;
        static::$_modelStore = array();
        static::$_shadowPathMapping = array();
        static::$_newIds = array();
        static::$_toDelete = array();
        static::$_idToShadowPath = array();
    }

    protected function _deleteInStoreByProp($_value, $_property)
    {
        $toDelete = array();
        foreach(static::$_modelStore as $id => $record) {
            if (strcmp((string)$_value, (string)($record->{$_property})) === 0) {
                unset(static::$_shadowPathMapping[static::$_idToShadowPath[$id]]);
                unset(static::$_idToShadowPath[$id]);
                unset(static::$_newIds[$id]);
                static::$_toDelete[$id] = true;
                $toDelete[] = $id;
            }
        }

        foreach($toDelete as $id) {
            unset(static::$_modelStore[$id]);
        }
    }

    protected function _registerCallBacks()
    {
        if (true !== static::$_registeredCallback) {
            Tinebase_TransactionManager::getInstance()->registerOnCommitCallback(array($this, 'executeDelayed'));
            Tinebase_TransactionManager::getInstance()->registerOnRollbackCallback(array($this, 'rollback'));
            static::$_registeredCallback = true;
        }
    }

    protected function _addToModelStore(Tinebase_Model_Path $_record, $_replace = false)
    {
        $id = $_record->getId();
        $shadow_path = $_record->shadow_path;

        if (isset(static::$_shadowPathMapping[$shadow_path])) {
            if (false === $_replace) {
                throw new Tinebase_Exception_UnexpectedValue('shadow path already mapped');
            } elseif(static::$_shadowPathMapping[$shadow_path] != $id) {
                throw new Tinebase_Exception_UnexpectedValue('shadow path already mapped to different id');
            }
        }

        if (isset(static::$_modelStore[$id])) {
            if (false === $_replace) {
                throw new Tinebase_Exception_UnexpectedValue('path id already in modelStore');
            } else {
                unset(static::$_shadowPathMapping[static::$_idToShadowPath[$id]]);
            }
        }

        if (isset(static::$_toDelete[$id])) {
            throw new Tinebase_Exception_UnexpectedValue('id was already deleted');
        }

        static::$_modelStore[$id] = $_record;
        if (false === $_replace) {
            static::$_newIds[$id] = true;
        }
        static::$_shadowPathMapping[$shadow_path] = $id;
        static::$_idToShadowPath[$id] = $shadow_path;
    }
}