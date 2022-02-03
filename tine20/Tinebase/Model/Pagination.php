<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Pagination Class
 * @package Tinebase
 *
 * @property string id
 * @property string start
 * @property string limit
 * @property string|array sort
 * @property string|array dir
 * @property string model
 */
class Tinebase_Model_Pagination extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';

    /**
     * holds the columns that should be selected, required if based on the selected columns a join is triggered
     * see \Timetracker_Model_Timesheet -> is_billable_combined
     * see \Timetracker_JsonTest::testSearchTimesheetsWithCombinedIsBillableAndCleared
     * @var array
     */
    protected $_sortColumns = null;

    protected $_externalSortMapping = null;

    protected $_customFields = null;

    protected $_virtualFields = null;

    protected $_recordFields = null;

    protected $_recordsFields = null;

    /**
     * validators
     * 
     * @var array
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty'    => true,
                                        'Int'                           ),
        'start'                => array('allowEmpty'    => true,
                                        'Int',
                                        'default'       => 0            ),
        'limit'                => array('allowEmpty'    => true,  
                                        'Int',
                                        'default'       => 0            ),
        // can be array for multiple sort rows
        'sort'                 => array('allowEmpty'    => true,
                                        'default'       => NULL         ),
        // can be array of sort dirs for multiple sort rows
        'dir'                  => array('presence'      => 'required',
                                        'allowEmpty'    => false,
                                        'default'       => 'ASC'        ),
        'model'                => array('allowEmpty'    => true,
                                        'default'       => NULL         ),
    );

    protected static $context = [];

    /**
     * @return array
     */
    public function getSortColumns()
    {
        if (null === $this->_sortColumns) {
            $this->_sortColumns = [];
            if ($this->_readModelConfig()) {
                foreach ($this->sort as $field) {
                    if (!isset($this->_externalSortMapping[$field]) && (!isset($this->_virtualFields[$field]) ||
                            (!isset($this->_virtualFields[$field]['type']) ||
                                ($this->_virtualFields[$field]['type'] !== 'relation' &&
                                    $this->_virtualFields[$field]['type'] !== 'record'))) &&
                            null === ($this->_customFields->find('name', $field))) {
                        $this->_sortColumns[] = $field;
                    }
                }
            }
        }
        return $this->_sortColumns;
    }

    protected function _readModelConfig()
    {
        if (empty($this->model) || null !== $this->_customFields) {
            return false;
        }

        static $recursion = 0;
        try {
            if (++$recursion > 1) return false;

            list($application,) = explode('_', $this->model, 2);
            $this->_customFields = Tinebase_CustomField::getInstance()
                ->getCustomFieldsForApplication($application, $this->model);
            /** @var Tinebase_Record_Interface $model */
            $model = $this->model;
            $this->_externalSortMapping = $model::getSortExternalMapping();
            if (null !== ($mc = $model::getConfiguration())) {
                if ($model === Tinebase_Model_User::class || $model === Tinebase_Model_FullUser::class) {
                    $this->_identifier = 'id';
                } else {
                    $this->_identifier = $mc->getIdProperty();
                }
                $this->_virtualFields = $mc->getVirtualFields();
                $this->_recordFields = $mc->recordFields;
                $this->_recordsFields = $mc->recordsFields;
            } else {
                $this->_identifier = (new $model([], true))->getIdProperty();
            }
            $this->sort = (array)$this->sort;

            return true;
        } finally {
            --$recursion;
        }
    }

    /**
     * Appends pagination statements to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendPaginationSql($_select, $_getDeleted = false)
    {
        // check model for required joins etc.
        $this->appendModelConfig($_select, $_getDeleted);

        $this->appendLimit($_select);
        $this->appendSort($_select);
    }

    /**
     * Appends limit statement to a given select object
     *
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendModelConfig($_select, $_getDeleted = false)
    {
        if (empty($this->model) || ((empty($this->sort) || empty($this->dir)) && empty($this->limit))) {
            return;
        }

        $this->_readModelConfig();

        /** @var Tinebase_Record_Interface $model */
        $model = $this->model;
        if (null === ($mc = $model::getConfiguration())) {
            if (empty($this->_customFields) && empty($this->_externalSortMapping)) {
                return;
            }
            $virtualFields = [];
            $recordFields = [];
            $recordsFields = [];
        } else {
            $virtualFields = $this->_virtualFields;
            $recordFields = $this->_recordFields;
            $recordsFields = $this->_recordsFields;
        }
        $mapping = $this->_externalSortMapping;
        $customfields = $this->_customFields;

        $joinCount = 0;
        $joined = [];
        foreach ($this->xprops('sort') as &$field) {
            if (isset($mapping[$field])) {
                $mappingDef = $mapping[$field];
                if (isset($mappingDef['fieldCallback'])) {
                    $field = call_user_func($mappingDef['fieldCallback'], $field);
                }
                if (isset($joined[$mappingDef['table']])) {
                    continue;
                }
                $_select->joinLeft([$mappingDef['table'] => SQL_TABLE_PREFIX . $mappingDef['table']],
                    $mappingDef['on'], []);
                $joined[$mappingDef['table']] = true;

            } elseif (isset($virtualFields[$field]) && isset($virtualFields[$field]['type']) &&
                    $virtualFields[$field]['type'] === 'relation' && isset($virtualFields[$field]['config']) &&
                    isset($virtualFields[$field]['config']['appName']) &&
                    isset($virtualFields[$field]['config']['modelName']) &&
                    isset($virtualFields[$field]['config']['type'])) {

                ++$joinCount;
                $db = $_select->getAdapter();
                $relationName = 'relationPagi' . $joinCount;
                /** @var Tinebase_Record_Interface $relatedModel */
                $relatedModel = $virtualFields[$field]['config']['appName'] . '_Model_' .
                    $virtualFields[$field]['config']['modelName'];
                if (null === ($relatedMC = $relatedModel::getConfiguration())) {
                    $e = new Tinebase_Exception_InvalidArgument('related model not a modelconfig model in pagination: '
                        . $relatedModel);
                    Tinebase_Exception::log($e);
                    continue;
                }

                $_select->joinLeft(
                    [$relationName => SQL_TABLE_PREFIX . 'relations'],
                    $db->quoteIdentifier([$relationName, 'own_id']) . ' = ' . $db->quoteIdentifier([
                            $mc->getTableName(), $mc->getIdProperty()
                        ]) . ' AND ' .
                    $db->quoteIdentifier([$relationName, 'own_model']) . ' =  "' . $model . '" AND ' .
                    $db->quoteIdentifier([$relationName, 'related_model']) . ' = "' . $relatedModel . '" AND ' .
                    $db->quoteIdentifier([$relationName, 'type']) . $db->quoteInto(' = ?',
                        $virtualFields[$field]['config']['type']) .
                    ($_getDeleted ? '' : ' AND ' . $db->quoteIdentifier([$relationName, 'is_deleted']) . ' = 0'),
                    []
                );

                $relatedTableName = $relatedMC->getTableName() . '_relPagi' . $joinCount;
                $_select->joinLeft(
                    [$relatedTableName => SQL_TABLE_PREFIX . $relatedMC->getTableName()],
                    $db->quoteIdentifier([$relationName, 'related_id']) . ' = ' .
                        $db->quoteIdentifier([$relatedTableName, $relatedMC->getIdProperty()]),
                    []
                );
                if (is_array($relatedMC->defaultSortInfo) && isset($relatedMC->defaultSortInfo['field'])) {
                    $field = $relatedMC->defaultSortInfo['field'];
                } else {
                    if (is_array($relatedMC->titleProperty)) {
                        $field = $relatedMC->titleProperty[1][0];
                    } else {
                        $field = $relatedMC->titleProperty;
                    }
                }
                $field = $relatedTableName . '.' . $field;

            } elseif (isset($recordFields[$field]) && isset($recordFields[$field]['config']) &&
                    isset($recordFields[$field]['config']['appName']) &&
                    isset($recordFields[$field]['config']['modelName']) &&
                    isset($recordFields[$field]['config']['type']) &&
                    $recordFields[$field]['config']['type'] === 'record') {

                ++$joinCount;
                $db = $_select->getAdapter();
                /** @var Tinebase_Record_Interface $relatedModel */
                $relatedModel = $recordFields[$field]['config']['appName'] . '_Model_' .
                    $recordFields[$field]['config']['modelName'];
                if (null === ($relatedMC = $relatedModel::getConfiguration())) {
                    $e = new Tinebase_Exception_InvalidArgument('related model not a modelconfig model in pagination: '
                        . $relatedModel);
                    Tinebase_Exception::log($e);
                    continue;
                }

                $relatedTableName = $relatedMC->getTableName() . '_recPagi' . $joinCount;
                if ($relatedModel === Tinebase_Model_User::class || $relatedModel === Tinebase_Model_FullUser::class) {
                    $idProp = 'id';
                    $relatedField = 'display_name';
                } else {
                    $idProp = $relatedMC->getIdProperty();
                    if (is_array($relatedMC->defaultSortInfo) && isset($relatedMC->defaultSortInfo['field'])) {
                        $relatedField = $relatedMC->defaultSortInfo['field'];
                    } else {
                        if (is_array($relatedMC->titleProperty)) {
                            $relatedField = $relatedMC->titleProperty[1][0];
                        } else {
                            $relatedField = $relatedMC->titleProperty;
                        }
                    }
                }
                $_select->joinLeft(
                    [$relatedTableName => SQL_TABLE_PREFIX . $relatedMC->getTableName()],
                    $db->quoteIdentifier([$mc->getTableNameForField($field), $field]) . ' = ' .
                    $db->quoteIdentifier([$relatedTableName, $idProp]) .
                    ($relatedMC->modlogActive && ! $_getDeleted ?
                        ' AND ' . $db->quoteIdentifier([$relatedTableName, 'is_deleted']) . ' = 0': ''),
                    []
                );
                $field = $relatedTableName . '.' . $relatedField;
            } elseif (isset($recordsFields[$field]) && isset($recordsFields[$field]['config']) &&
                    isset($recordsFields[$field]['config']['appName']) &&
                    isset($recordsFields[$field]['config']['modelName']) &&
                    isset($recordsFields[$field]['config'][Tinebase_Record_Abstract::SPECIAL_TYPE]) &&
                    Tinebase_Record_Abstract::TYPE_LOCALIZED_STRING === $recordsFields[$field]['config'][Tinebase_Record_Abstract::SPECIAL_TYPE]) {
                /** @var Tinebase_Record_Interface $relatedModel */
                $relatedModel = $recordsFields[$field]['config'][Tinebase_Record_Abstract::RECORD_CLASS_NAME];
                if (null === ($relatedMC = $relatedModel::getConfiguration())) {
                    $e = new Tinebase_Exception_InvalidArgument('related model not a modelconfig model in pagination: '
                        . $relatedModel);
                    Tinebase_Exception::log($e);
                    continue;
                }

                if (is_array($relatedMC->defaultSortInfo) && isset($relatedMC->defaultSortInfo['field'])) {
                    $relatedField = $relatedMC->defaultSortInfo['field'];
                } else {
                    if (is_array($relatedMC->titleProperty)) {
                        $relatedField = $relatedMC->titleProperty[1][0];
                    } else {
                        $relatedField = $relatedMC->titleProperty;
                    }
                }

                $db = $_select->getAdapter();
                $correlationName = null;
                $tableName = SQL_TABLE_PREFIX . $relatedMC->getTableName();
                foreach ($_select->getPart(Zend_Db_Select::FROM) as $cName => $fromConfig) {
                    if ($tableName === $fromConfig['tableName']) {
                        $correlationName = $cName;
                    }
                }
                if (null === $correlationName) {
                    ++$joinCount;
                    $correlationName = $relatedMC->getTableName() . '_recPagi' . $joinCount;
                    $refIdField = $recordsFields[$field]['config'][Tinebase_Record_Abstract::REF_ID_FIELD];
                    $forcedValues = '';
                    $cfg = Tinebase_Config_Abstract::factory($mc->{Tinebase_Record_Abstract::LANGUAGES_AVAILABLE}
                        [Tinebase_Record_Abstract::CONFIG][Tinebase_Record_Abstract::APP_NAME]);
                    $defaultLang = $cfg->{$mc->{Tinebase_Record_Abstract::LANGUAGES_AVAILABLE}[Tinebase_Record_Abstract::NAME]}->default;
                    if (isset(static::$context[Tinebase_Record_Abstract::TYPE_LOCALIZED_STRING][$relatedModel]['language'])) {
                        $defaultLang = static::$context[Tinebase_Record_Abstract::TYPE_LOCALIZED_STRING][$relatedModel]['language'];
                        unset(static::$context[Tinebase_Record_Abstract::TYPE_LOCALIZED_STRING][$relatedModel]['language']);
                    }
                    foreach ($recordsFields[$field]['config'][Tinebase_Record_Abstract::FORCE_VALUES] as $f => $v) {
                        $forcedValues .= ' AND ' . $db->quoteIdentifier([$correlationName, $f])
                            . $db->quoteInto(' = ?', $v);
                    }
                    $_select->joinLeft(
                        [$correlationName => $tableName],
                        $db->quoteIdentifier([$mc->getTableName(), $relatedMC->getIdProperty()]) . ' = ' .
                        $db->quoteIdentifier([$correlationName, $refIdField]) .
                        ($relatedMC->modlogActive && ! $_getDeleted ?
                            ' AND ' . $db->quoteIdentifier([$correlationName, 'is_deleted']) . ' = 0': '')
                        . $forcedValues . ' AND '
                        . $db->quoteIdentifier([$correlationName, Tinebase_Record_PropertyLocalization::FLD_LANGUAGE])
                        . $db->quoteInto(' = ?', $defaultLang),
                        []
                    );
                    if (empty($_select->getPart(Zend_Db_Select::GROUP))) {
                        $_select->group($mc->getTableName() . '.' . $mc->getIdProperty());
                    }
                }
                $field = $correlationName . '.' . $relatedField;

            } elseif (null !== ($cfCfg = $customfields->find('name', $field))) {
                ++$joinCount;
                $db = $_select->getAdapter();

                $relatedTableName = 'customfield_Pagi' . $joinCount;
                $_select->joinLeft(
                    [$relatedTableName => SQL_TABLE_PREFIX . 'customfield'],
                    $db->quoteIdentifier([$mc->getTableName(), $mc->getIdProperty()]) . ' = ' .
                    $db->quoteIdentifier([$relatedTableName, 'record_id']) . ' AND ' .
                    $db->quoteIdentifier([$relatedTableName, 'customfield_id']) .
                    $db->quoteInto(' = ?', $cfCfg->getId()),
                    []
                );
                $field = $relatedTableName . '.value';
            }
        }
    }

    /**
     * Appends limit statement to a given select object
     *
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendLimit($_select)
    {
        // attention, we only check for limit, not for start! same above in model helper functions!
        if (!empty($this->limit)) {
            $this->_ensureRepeatableResults();
            $start = ($this->start >= 0) ? $this->start : 0;
            $_select->limit($this->limit, $start);
        }
    }

    protected function _ensureRepeatableResults()
    {
        // jupp, this sucks all over the place :-/
        if (empty($this->sort)) {
            $this->sort = [];
        } elseif (!is_array($this->sort)) {
            $this->sort = [$this->sort];
        }
        if (!in_array($this->_identifier, $this->sort)) {
            $this->xprops('sort')[] = $this->_identifier;
            if (empty($this->dir)) {
                $this->dir = 'ASC';
            } elseif (!is_array($this->dir)) {
                if ('ASC' !== $this->dir) {
                    $this->dir = array_fill(0, count($this->sort) - 1, $this->dir);
                    $this->xprops('dir')[] = 'ASC';
                }
            } else {
                $this->xprops('dir')[] = 'ASC';
            }

        }
    }

    /**
     * Appends sort statement to a given select object
     * 
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendSort($_select)
    {
        if (!empty($this->sort) && !empty($this->dir)){
            $this->_ensureRepeatableResults();
            $tableName = null;
            foreach ($_select->getPart(Zend_Db_Select::FROM) as $key => $from) {
                if (Zend_Db_Select::FROM === $from['joinType']) {
                    if (is_numeric($key)) {
                        $tableName = $from['tableName'];
                    } else {
                        $tableName = $key;
                    }
                    break;
                }
            }
            $_select->order($this->_getSortCols($tableName, $_select->getPart(Zend_Db_Select::COLUMNS)));
        }        
    }
    
    /**
     * get columns for select order statement
     *
     * @param String $tableName
     * @return array
     */
    protected function _getSortCols($tableName = null, $columns = [])
    {
        $order = array();
        foreach ((array)$this->sort as $index => $sort) {
            if (strpos($sort, '(') === false && strpos($sort, '.') === false) {
                $found = false;
                foreach ($columns as $col) {
                    if ($col[1] === $sort) {
                        if (isset($col[2])) {
                            $sort = $col[2];
                            $found = true;
                        } elseif (!empty($col[0])) {
                            $sort = $col[0] . '.' . $sort;
                            $found = true;
                        }
                        break;
                    } elseif (isset($col[2]) && $col[2] === $sort) {
                        $found = true;
                        break;
                    }
                }
                if (!$found && null !== $tableName) {
                    $sort = $tableName . '.' . $sort;
                }
            }
            $order[] = $sort . ' ' . (is_array($this->dir)
                        ? $this->dir[$index]
                        : $this->dir
                    );
        }
        return $order;
    }

    public static function setContext(array $context)
    {
        static::$context = $context;
    }

    public static function getContext(): array
    {
        return static::$context;
    }
}
