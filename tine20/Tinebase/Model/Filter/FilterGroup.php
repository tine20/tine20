<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @todo        finish implementation of to/from json functions
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Tinebase_Model_Filter_FilterGroup
 * 
 * A filter group represents a number of individual filters and a condition between
 * all of them. Each filter group requires a filter model where the allowed filters
 * and options for them are specified on the one hand, and concrete filter data for
 * this concrete filter on the other hand.
 * 
 * NOTE: To define a filter model only once, it might be usefull to extend this 
 *       class and only overwrite $this->_filterModel
 * NOTE: The first filtergroup is _allways_ a AND condition filtergroup!
 *       This is due to the fact that the first filtergroup operates on the 
 *       'real' select object (@see $this->appendSql)
 * NOTE: The ACL relevant filters _must_ be checked and set by the controllers!
 * 
 * <code> 
 * class myFilterGroup {
 *     protected $_applicationName = 'myapp';
 *     protected $_filterModel = array (
 *         'name'       => array('filter' => 'Tinebase_Model_Filter_Text'),
 *         'container'  => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('modelName' => 'myModel')),
 *         'created_by' => array('filter' => 'Tinebase_Model_Filter_User'),
 *         'some_id'    => array('filter' => 'Tinebase_Model_Filter_ForeignId', 'options' => array('filtergroup' => 'Someapp_Model_SomeFilter', 'controller' => 'Myapp_Controller_Some')),
 *         'custom'     => array('custom' => true),  // will be ignored and you must handle this filter your own!
 *     );
 * }
 * 
 * $filterData = array(
 *     array('field' => 'name','operator' => 'beginswith', 'value' => 'Hugo'),
 *     array('condition' => 'OR', 'filters' => array(
 *         array('field' => 'created_by',  'operator' => 'equals', 'value' => 2),
 *         array('field' => 'modified_by', 'operator' => 'equals', 'value' => 2)
 *     )),
 *     array('field' => 'container_id', 'operator' => 'in', 'value' => array(2,4,6,7)
 *     array('field' => 'foreign_id',  'operator' => 'AND', value => array(
 *         array('field' => 'foreignfieldname',  'operator' => 'contains', 'value' => 'test'),
 *     )
 *     // foreign record (relation) filter (Contact <-> Project) 
 *     array(
 *         'field'      => 'foreignRecord',
 *          'operator'  => 'AND', 
 *          'id'        => 'someid' // can be send by the client and is returned in toArray()
 *          'value' => array(
 *              'linkType'      => 'relation',
 *              'appName'       => 'Projects',
 *              'modelName'     => 'Project',
 *              'filters'       => array(
 *                  array('field' => "relation_type", "operator" => "equals", "value" => "COWORKER"),
 *                  array('field' => "status",        "operator" => "notin",  "value" => array(1,2,3)),
 *              ),
 *          )
 *     ),
 *     // foreign record (id) filter (Contact <-> Event Attender)
 *     array(
 *          'field' => 'foreignRecord', 
 *          'operator' => 'AND', 
 *          'value' => array(
 *              'linkType'      => 'foreignId',
 *              'appName'       => 'Calendar',
 *              'filterName'    => 'ContactFilter', // this filter model needs to exist in Calendar/Model/
 *              'filters'       => array(
 *                  array('field' => "period",            "operator" => "within", "value" => array(
 *                      'from'  => '2009-01-01 00:00:00',
 *                      'until' => '2010-12-31 23:59:59',
 *                  )),
 *                  array('field' => "attender_status",   "operator" => "in",  "value" => array('NEEDS-ACTION', 'ACCEPTED')),
 *                  array('field' => "attender_role",     "operator" => "in",  "value" => array('REQ')),
 *              ),
 *          )
 *      ),
 * );
 * 
 * $filterGroup = new myFilterGroup($filterData);
 * 
 * // it is now possible to use the short form for the filterData like this:
 * $filterData = array(
 *      'created_by'  => 2,
 *      'modified_by' => 2,
 * );
 * // this is equivalent to:
 * $filterData = array(
 *      array('field' => 'created_by',  'operator' => 'equals', 'value' => 2),
 *      array('field' => 'modified_by', 'operator' => 'equals', 'value' => 2),
 * </code>
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_FilterGroup implements Iterator
{
    /*************** config options for inheriting filter groups ***************/

    public const CONDITION = 'condition';
    /**
     * const for OR condition
     */
    public const CONDITION_OR = 'OR';
    
    /**
     * const for AND condition
     */
    public const CONDITION_AND = 'AND';

    public const FILTERS = 'filters';
    public const FOREIGN_RECORD = 'foreignRecord';

    public const IGNORE_ACL = 'ignoreAcl';

    public const LINK_TYPE = 'linkType';
    public const LINK_TYPE_RELATION = 'relation';
    public const LINK_TYPE_FOREIGN_ID = 'foreignId';
    public const LINK_TYPE_DENORMALIZED = 'denormalized';
    
    /**
     * if this is set, the filtergroup will be created using the configurationObject for this model
     *
     * @var string
     */
    protected $_configuredModel = NULL;
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = NULL;
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = NULL;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array();

    /**
     * @var string id
     */
    protected $_id = NULL;
    
    /**
     * @var string label
     */
    protected $_label = NULL;
    
    /**
     * default filter
     * @var string
     */
    protected $_defaultFilter = 'query';
    
    /******************************* properties ********************************/
    
    /**
     * @var array holds filter objects of this filter
     */
    protected $_filterObjects = array();
    
    /**
     * @var array special options
     */
    protected $_options = NULL;
    
    /**
     * @var array holds data of all custom filters
     */
    protected $_customData = array();
    
    /**
     * @var string holds condition between this filters
     */
    protected $_concatenationCondition = NULL;

    /**
     * @var boolean whether to check ACLs
     */
    protected $_ignoreAcl = false;

    /**
     * @var Tinebase_Model_Filter_FilterGroup|null reference to parent group
     */
    protected $_parent = null;

    protected $_isInSetFromUser = false;

    protected $_isImplicit = false;

    public static $beStrict = true;
    
    /******************************** functions ********************************/
    
    /**
     * constructs a new filter group
     *
     * @param  array $_data
     * @param  string $_condition {AND|OR}
     * @param  array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $_data = array(), $_condition = '', $_options = array(),
        Tinebase_Model_Filter_FilterGroup $_parent = null)
    {
        $this->_createFromModelConfiguration();

        if (null !== $_parent) {
            $this->_parent = $_parent;
        }
        
        $this->_setOptions($_options);
        
        $this->_concatenationCondition = $_condition == self::CONDITION_OR ? self::CONDITION_OR : self::CONDITION_AND;

        $this->_ignoreAcl = isset($this->_options[self::IGNORE_ACL]) ? (bool)$this->_options[self::IGNORE_ACL] : false;
        
        $this->setFromArray($_data);
    }
    
    /**
     * create filter from modelconfiguration if a configured model is assigned
     */
    protected function _createFromModelConfiguration()
    {
        if ($this->_configuredModel) {
            /** @var Tinebase_Record_Interface $m */
            $m = $this->_configuredModel;
            $filterConfig = $m::getConfiguration()->getFilterModel();

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . ' Filter config: ' . print_r($filterConfig, TRUE));

            foreach ($filterConfig as $prop => $val) {
                $this->{$prop} = $val;
            }
            // modelname is here the full name if the php model
            $this->_modelName = $this->_applicationName . '_Model_' . $this->_modelName;
        }
    }

    /**
     * clone filters after creating clone of filter group
     */
    public function __clone()
    {
        foreach($this->_filterObjects as $idx => $filter) {
            $this->_filterObjects[$idx] = clone $filter;
            $this->_filterObjects[$idx]->setParent($this);
        }
    }

    public function isImplicit(?bool $val = null): bool
    {
        $oldValue = $this->_isImplicit;
        if (null !== $val) {
            $this->_isImplicit = $val;
        }
        return $oldValue;
    }

    public function andWrapItself()
    {
        $class = static::class;
        $children = new $class([], $this->_concatenationCondition);
        foreach($this->_filterObjects as $idx => $filter) {
            if ($this->_filterObjects[$idx] instanceof Tinebase_Model_Filter_Abstract) {
                $children->addFilter($this->_filterObjects[$idx]);
            } else {
                $children->addFilterGroup($this->_filterObjects[$idx]);
            }
        }
        $this->_filterObjects = [];
        $this->_concatenationCondition = self::CONDITION_AND;
        $this->addFilterGroup($children);
    }

    /**
     * @param bool|null $bool
     * @return bool
     */
    public function doIgnoreAcl($bool = null)
    {
        $oldValue = $this->_ignoreAcl;
        if (null !== $bool) {
            foreach ($this->_filterObjects as $filter) {
                if ($filter instanceof Tinebase_Model_Filter_FilterGroup) {
                    $filter->doIgnoreAcl($bool);
                }
            }
            $this->_ignoreAcl = (bool)$bool;
        }
        return $oldValue;
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $_parent
     */
    public function setParent($_parent)
    {
        $this->_parent = $_parent;
        if ($_parent->isInSetFromUser() && !$this->_isInSetFromUser) {
            $this->_isInSetFromUser = true;
            foreach($this->_filterObjects as $filterObject) {
                $filterObject->setParent($this);
            }
        }
    }

    /**
     * @return null|Tinebase_Model_Filter_FilterGroup
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public function getRootParent()
    {
        if (null !== $this->_parent) {
            return $this->_parent->getRootParent();
        }
        return $this;
    }

    /**
     * sets this filter group from filter data in array representation
     *
     * @param array|null $_data
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function setFromArray(?array $_data)
    {
        if (! $_data) {
            // sanitize data
            $_data = [];
        }

        $this->_filterObjects = [];
        
        foreach ($_data as $key => $filterData) {
            if ($filterData === null) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' Skipping empty filter (key: ' . $key . ')');
                continue;
            }
            if (! is_array($filterData)) {
                $filterData = self::sanitizeFilterData($key, $filterData);
            }
            
            // if a condition is given, we create a new filtergroup from this class
            if (isset($filterData[self::CONDITION])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' 
                    . ' Adding FilterGroup: ' . static::class);

                $selfClass = static::class;
                $filtergroup = new $selfClass([], $filterData[self::CONDITION], $this->_options, $this);
                if (static::class === 'Tinebase_Model_Filter_FilterGroup') {
                    // generic modelconfig filter group, need to set model
                    $filtergroup->setConfiguredModel($this->_configuredModel);
                }
                $filtergroup->setFromArray($filterData[self::FILTERS]);
                if (isset($filterData['id'])) {
                    $filtergroup->setId($filterData['id']);
                }
                if (isset($filterData['label'])) {
                    $filtergroup->setLabel($filterData['label']);
                }
                
                $this->addFilterGroup($filtergroup);
            } else if (isset($filterData[TMFA::FIELD]) && $filterData[TMFA::FIELD] === self::FOREIGN_RECORD) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' 
                    . ' Adding ForeignRecordFilter of type: ' . $filterData[TMFA::VALUE][self::LINK_TYPE]);
                $this->_createForeignRecordFilterFromArray($filterData);
                
            } else {
                $this->_createStandardFilterFromArray($filterData);
            }
        }
    }
    
    /**
     * create foreign record filter (from array)
     * 
     * @param array $_filterData
     */
    protected function _createForeignRecordFilterFromArray($_filterData)
    {
        if (! isset($_filterData[TMFA::VALUE][self::FILTERS])) {
            if (self::$beStrict) {
                throw new Tinebase_Exception_Record_DefinitionFailure('filter syntax problem ' . print_r($_filterData, true));
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__
                . ' Skipping filter (foreign record filter syntax problem) -> '
                . static::class . ' with filter data: ' . print_r($_filterData, TRUE));
            return;
        }

        $filterData = $_filterData;

        $filterData[TMFA::VALUE] = $_filterData[TMFA::VALUE][self::FILTERS];
        $filterData[TMFA::OPTIONS] = array(
            'isGeneric'         => TRUE
        );
        
        switch ($_filterData[TMFA::VALUE][self::LINK_TYPE]) {
            case self::LINK_TYPE_DENORMALIZED:
                $modelName = $this->_getModelNameFromLinkInfo($_filterData[TMFA::VALUE], 'modelName');
                $model = new $this->_modelName();
                $filterData[TMFA::FIELD] = $_filterData[TMFA::VALUE]['property'];
                $filterData[TMFA::OPTIONS][TMFA::FIELD] = $model->getIdProperty();
                $filterData[TMFA::OPTIONS]['model'] = $modelName;
                $filterData[TMFA::OPTIONS]['property'] = $_filterData[TMFA::VALUE]['property'];
                $filter = new Tinebase_Model_Filter_DenormalizedRecord($filterData);
                break;

            case self::LINK_TYPE_RELATION:
                $modelName = $this->_getModelNameFromLinkInfo($_filterData[TMFA::VALUE], 'modelName');
                $model = new $this->_modelName();
                $filterData[TMFA::OPTIONS]['related_model'] = $modelName;
                $filterData[TMFA::OPTIONS]['idProperty'] = $model->getIdProperty();
                if (isset($_filterData[TMFA::OPTIONS]) && isset($_filterData[TMFA::OPTIONS]['own_model'])) {
                    $filterData[TMFA::OPTIONS]['own_model'] = $_filterData[TMFA::OPTIONS]['own_model'];
                }
                $filter = new Tinebase_Model_Filter_Relation($filterData);
                break;

            case self::LINK_TYPE_FOREIGN_ID:
                $filterName = $this->_getModelNameFromLinkInfo($_filterData[TMFA::VALUE], 'filterName');
                if (isset($_filterData[TMFA::VALUE]['modelName'])) {
                    $filterData[TMFA::OPTIONS]['modelName'] = $this->_getModelNameFromLinkInfo($_filterData[TMFA::VALUE], 'modelName');;
                }
                $filter = new $filterName($filterData);
                
                // @todo maybe it will be possible to add a generic/implicite foreign id filter 
                // .... but we have to solve the problem of the foreign id field first
//                if (! (isset($_filterData[TMFA::VALUE]['filterName']) || array_key_exists('filterName', $_filterData[TMFA::VALUE]))) {
//                    $modelName = $this->_getModelNameFromLinkInfo($_filterData[TMFA::VALUE], 'modelName');
//                    $filter = new Tinebase_Model_Filter_ForeignId($_filterData[TMFA::FIELD], $_filterData[TMFA::OPERATOR], $_filterData[TMFA::VALUE], array(
//                        'filtergroup'       => $modelName . 'Filter', 
//                        'controller'        => str_replace('Model', 'Controller', $modelName),
//                    ));
//                }
                break;
            default:
                if (self::$beStrict) {
                    throw new Tinebase_Exception_Record_DefinitionFailure('filter syntax problem ' . print_r($_filterData, true));
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__
                    . ' Skipping filter (foreign record filter syntax problem) -> '
                    . static::class . ' with filter data: ' . print_r($_filterData, TRUE));
                return;
        }
        
        $this->addFilter($filter);
    }
    
    /**
     * get model name from link info and checks input
     * 
     * @param array $_linkInfo
     * @param string $_modelKey modelName|filterName
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getModelNameFromLinkInfo($_linkInfo, $_modelKey)
    {
        if (   ! in_array($_modelKey, array('modelName', 'filterName')) 
            || ! (isset($_linkInfo['appName']) || array_key_exists('appName', $_linkInfo)) 
            || ! (isset($_linkInfo[$_modelKey]) || array_key_exists($_modelKey, $_linkInfo))
        ) {
            throw new Tinebase_Exception_InvalidArgument('Foreign record filter needs appName and modelName or filterName');
        }

        $appName = str_replace('_', '', $_linkInfo['appName']);
        
        if (! Tinebase_Application::getInstance()->isInstalled($appName) || (is_object(Tinebase_Core::getUser()) && ! Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights_Abstract::RUN))) {
            throw new Tinebase_Exception_AccessDenied('No right to access application');
        }
        
        $modelName = $appName . '_Model_' . $_linkInfo[$_modelKey];
        
        if (! class_exists($modelName)) {
            throw new Tinebase_Exception_InvalidArgument('Model does not exist');
        }
        
        return $modelName;
    }
    
    /**
     * create standard filter (from array)
     * 
     * @param array $_filterData
     */
    protected function _createStandardFilterFromArray($_filterData)
    {
        $fieldModel = (isset($_filterData[TMFA::FIELD]) && isset($this->_filterModel[$_filterData[TMFA::FIELD]])) ? $this->_filterModel[$_filterData[TMFA::FIELD]] : '';
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . '[' . static::class . '] Debug: ' . print_r($this->_filterModel, true));
        
        if (empty($fieldModel)) {
            if (isset($_filterData[TMFA::FIELD]) && strpos($_filterData[TMFA::FIELD], '#') === 0) {
                $this->_addCustomFieldFilter($_filterData);
            } else {
                if (self::$beStrict) {
                    throw new Tinebase_Exception_Record_DefinitionFailure('no model found for ' . print_r($_filterData, true));
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . '[' . static::class . '] Skipping filter (no filter model defined) ' . print_r($_filterData, true));
            }
        } elseif ((isset($fieldModel['filter']) || array_key_exists('filter', $fieldModel)) && (isset($_filterData[TMFA::VALUE]) || array_key_exists(TMFA::VALUE, $_filterData))) {
            // create a 'single' filter
            $filter = $this->createFilter($_filterData);
            if ($filter instanceof Tinebase_Model_Filter_Abstract) {
                $this->addFilter($filter, TRUE);
            } else {
                $this->addFilterGroup($filter);
            }
        
        } elseif (isset($fieldModel['custom']) && $fieldModel['custom'] == true) {
            // silently skip data, as they will be evaluated by the concrete filtergroup
            $this->_customData[] = $_filterData;
        
        } else {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Skipping filter (filter syntax problem) -> ' 
                . static::class . ' with filter data: ' . print_r($_filterData, TRUE));
        }
    }

    /**
     * create and add custom field filter with the name #CUSTOMFIELDNAME
     *
     * @param $_filterData
     */
    protected function _addCustomFieldFilter($_filterData)
    {
        $cfName = ltrim($_filterData[TMFA::FIELD], '#');
        $customFieldConfig = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            $this->_applicationName,
            $cfName,
            $this->_modelName
        );
        if (! $customFieldConfig) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' No custom field with name ' . $cfName . ' found for model ' . $this->_modelName);
            return;
        }

        $customFieldFilterData = [
            TMFA::FIELD => 'customfield',
            TMFA::OPERATOR => $_filterData[TMFA::OPERATOR],
            TMFA::VALUE => [
                'cfId' => $customFieldConfig->getId(),
                TMFA::VALUE => $_filterData[TMFA::VALUE],
            ]
        ];

        $filter = $this->createFilter($customFieldFilterData);
        $this->addFilter($filter, TRUE);
    }
    
    /**
     * return sanitized filter data
     * 
     * @param string $_field
     * @param mixed $_value
     * @return array
     */
    public static function sanitizeFilterData($_field, $_value)
    {
        return array(
            TMFA::FIELD     => $_field,
            TMFA::OPERATOR  => 'equals',
            TMFA::VALUE     => $_value,
        );
    }
    
    /**
     * Add a filter to this group
     *
     * @param  Tinebase_Model_Filter_Abstract $_filter
     * @param  boolean $_setFromArray
     * @return Tinebase_Model_Filter_FilterGroup this
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function addFilter(Tinebase_Model_Filter_Abstract $_filter, $_setFromArray = FALSE)
    {
        if (! $_setFromArray && $_filter instanceof Tinebase_Model_Filter_AclFilter) {
            // this is added afterwards and considered as an implicit acl filter
            $_filter->setIsImplicit(TRUE);
        }

        $_filter->setParent($this);
        
        $this->_filterObjects[] = $_filter;
        
        return $this;
    }
    
    /**
     * Add a filter group to this group
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filtergroup
     * @return Tinebase_Model_Filter_FilterGroup this
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function addFilterGroup($_filtergroup)
    {
        if (! $_filtergroup instanceof Tinebase_Model_Filter_FilterGroup) {
            if ($_filtergroup instanceof Tinebase_Model_Filter_Abstract) {
                return $this->addFilter($_filtergroup);
            }
            throw new Tinebase_Exception_InvalidArgument('Filters must be of instance Tinebase_Model_Filter_FilterGroup');
        }

        $_filtergroup->setParent($this);
        $this->_filterObjects[] = $_filtergroup;
        
        return $this;
    }
    
    /**
     * creates a new filter based on the definition of this filtergroup
     *
     * @param  string|array $_fieldOrData
     * @param  string $_operator
     * @param  mixed  $_value
     * @return Tinebase_Model_Filter_Abstract|Tinebase_Model_Filter_FilterGroup
     * 
     * @todo remove legacy code + obsolete params sometimes
     */
    public function createFilter($_fieldOrData, $_operator = NULL, $_value = NULL)
    {
        if (is_array($_fieldOrData)) {
            $data = $_fieldOrData;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' 
                . ' Using deprecated function syntax. Please pass all filter data in one array (field: ' . $_fieldOrData . ')');
            
            $data = array(
                TMFA::FIELD     => $_fieldOrData,
                TMFA::OPERATOR  => $_operator,
                TMFA::VALUE     => $_value,
            );
        }

        foreach (array(TMFA::FIELD, TMFA::OPERATOR, TMFA::VALUE) as $requiredKey) {
            if (! (isset($data[$requiredKey]) || array_key_exists($requiredKey, $data))) {
                throw new Tinebase_Exception_InvalidArgument('Filter object needs ' . $requiredKey);
            }
        }
        
        if (empty($this->_filterModel[$data[TMFA::FIELD]])) {
            throw new Tinebase_Exception_NotFound('no such field (' . $data[TMFA::FIELD] . ') in this filter model');
        }
        
        $definition = $this->_filterModel[$data[TMFA::FIELD]];
            
        if (isset($definition['custom']) && $definition['custom']) {
            $this->_customData[] = array(
                TMFA::FIELD     => $data[TMFA::FIELD],
                TMFA::OPERATOR  => $data[TMFA::OPERATOR],
                TMFA::VALUE     => $data[TMFA::VALUE]
            );
            $filter = NULL;
        } else {
            $self = $this;
            $data[TMFA::OPTIONS] = array_merge($this->_options, isset($definition[TMFA::OPTIONS]) ? (array)$definition[TMFA::OPTIONS] : array(), array('parentFilter' => $self));
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Creating filter: ' . $definition['filter'] . ' with data: ' . print_r($data, TRUE));

            if (isset($definition['filter'])
                && (
                    $definition['filter'] === Tinebase_Model_Filter_ForeignRecords::class
                    || $definition['filter'] === Tinebase_Model_Filter_ForeignRecord::class
                    || $definition['filter'] === Tinebase_Model_Filter_ForeignId::class
                )
            ) {
                // special handling for foreign record filtering: creates a subfilter with CONDITION_AND
                // NOTE: maybe this should be fixed in the client / this is just sanitizing here
                // equals is also valid (for Tinebase_Model_Filter_ForeignId)
                // TODO this needs to go away!
                if (! in_array($data[TMFA::OPERATOR], [self::CONDITION_OR, self::CONDITION_AND, 'equals', 'in', 'not', 'notin', 'notDefinedBy:AND']) &&
                        strpos($data[TMFA::OPERATOR], 'efinedBy') === false) {
                    // add a sub-query filter
                    $data[TMFA::VALUE] = [
                        [TMFA::FIELD => 'query', TMFA::OPERATOR => $data[TMFA::OPERATOR], TMFA::VALUE => $data[TMFA::VALUE]]
                    ];
                    if (isset($definition[TMFA::OPTIONS]['addFilters'])) {
                        $data[TMFA::VALUE] = array_merge($data[TMFA::VALUE], $definition[TMFA::OPTIONS]['addFilters']);
                    }
                    $data[TMFA::OPERATOR] = self::CONDITION_AND;
                }
            }

            $filter = new $definition['filter']($data);
        }
        
        return $filter;
    }
    
    /**
     * gets aclFilter of this group
     * 
     * @return array
     */
    public function getAclFilters()
    {
        $aclFilters = array();
        
        foreach ($this->_filterObjects as $object) {
            if ($object instanceof Tinebase_Model_Filter_AclFilter) {
                $aclFilters[] = $object;
            }
        }
        
        return $aclFilters;
    }
    
    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        foreach ($this->getAclFilters() as $object) {
            $object->setRequiredGrants($_grants);
        }
    }
    
    /**
     * returns concetationOperator / condition of this filtergroup
     *
     * @return string {AND|OR}
     */
    public function getCondition()
    {
        return $this->_concatenationCondition;
    }

    /**
     * @param string $_condition
     */
    public function setCondition($_condition)
    {
        $this->_concatenationCondition = $_condition === self::CONDITION_OR ? self::CONDITION_OR : ($_condition === self::CONDITION_AND ? self::CONDITION_AND : ($this->_concatenationCondition !== NULL ? $this->_concatenationCondition : self::CONDITION_AND));
    }
    
    /**
     * set id
     *
     * @param string $_id
     */
    public function setId($_id)
    {
        $this->_id = $_id;
    }
    
    /**
     * set label
     *
     * @param string $_label
     */
    public function setLabel($_label)
    {
        $this->_label = $_label;
    }

    /**
     * set configured model
     *
     * @param $configuredModel
     */
    public function setConfiguredModel($configuredModel)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' Re-create filter model from model config (' . $configuredModel . ')');

        $this->_configuredModel = $configuredModel;
        $this->_createFromModelConfiguration();
    }
    
    /**
     * returns id
     *
     * @return string id
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * returns label
     *
     * @return string label
     */
    public function getLabel()
    {
        return $this->_label;
    }
    
    /**
     * returns default filter
     * @return string default filter
     */
    public function getDefaultFilter()
    {
        return $this->_defaultFilter;
    }
    
    /**
     * returns application name of this filtergroup
     *
     * @return string
     */
    public function getApplicationName()
    {
        return $this->_applicationName;
    }
    
    /**
     * returns name of model this filtergroup is for
     *
     * @return string
     */
    public function getModelName()
    {
        if (null === $this->_modelName) {
            $this->_modelName = substr(get_called_class(), 0, -6);
        }
        return $this->_modelName;
    }
    
    /**
     * returns model of this filtergroup
     *
     * @return array
     */
    public function getFilterModel()
    {
        return $this->_filterModel;
    }
    
    /**
     * return filter object(s)
     *
     * @param string $_field
     * @param boolean $_getAll
     * @param boolean $_recursive
     * 
     * @return Tinebase_Model_Filter_Abstract|array|null
     */
    public function getFilter($_field, $_getAll = FALSE, $_recursive = FALSE)
    {
        return $this->_findFilter($_field, $_getAll, $_recursive);
    }
    
    /**
     * returns filter objects
     *
     * @return array
     * 
     * @todo remove after concrete filter backends are separated from concrete filter models
     */
    public function getFilterObjects()
    {
        return $this->_filterObjects;
    }
    
    /**
     * removes a filter
     * 
     * @param string|Tinebase_Model_Filter_Abstract $_field
     * @param boolean $_recursive remove filter in subfilters
     * @return void
     */
    public function removeFilter($_field, $_recursive = FALSE)
    {
        if ($_field instanceof Tinebase_Model_Filter_Abstract) {
            $idx = array_search($_field, $this->_filterObjects, TRUE);
            if ($idx !== FALSE) {
                unset($this->_filterObjects[$idx]);
            }
            if ($_recursive) {
                foreach ($this->_filterObjects as $object) {
                    if ($object instanceof Tinebase_Model_Filter_FilterGroup) {
                        $object->removeFilter($_field, $_recursive);
                    }
                }
            }
        } else {
            $this->_removeFilter($_field, $_recursive);
        }
    }
    
    /**
     * remove id of filter object
     */
    public function removeId()
    {
        $this->_id = NULL;
        foreach ($this->_filterObjects as $filter) {
            $filter->removeId();
        }
    }
    
    /**
     * returns array with the filter settings of this filter group 
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = array();
        /** @var Tinebase_Model_Filter_FilterGroup|Tinebase_Model_Filter_Abstract $filter */
        foreach ($this->_filterObjects as $filter) {
            if ($filter->isImplicit()) {
                continue;
            }
            if ($filter instanceof Tinebase_Model_Filter_FilterGroup && ! $filter instanceof Tinebase_Model_Filter_Query) {
                $result[] = array(
                    self::CONDITION => $filter->getCondition(),
                    self::FILTERS   => $filter->toArray($_valueToJson),
                    'id'        => $filter->getId(),
                    'label'     => $filter->getLabel(),
                );
                
            } else {
                $result[] = $filter->toArray($_valueToJson);
            }
            
        }
        
        // add custom fields
        foreach ($this->_customData as $custom) {
            $result[] = $custom;
        }
        
        return $result;
    }

    /**
     * wrapper for setFromJson which expects datetimes in array to be in
     * users timezone and converts them to UTC
     *
     * @param array $_data 
     */
    public function setFromArrayInUsersTimezone($_data)
    {
        $this->_options['timezone'] = Tinebase_Core::getUserTimezone();
        try {
            $this->_isInSetFromUser = true;
            $this->setFromArray($_data);
        } finally {
            $this->_isInSetFromUser = false;
        }
    }

    public function setFromUser(bool $val): void
    {
        $this->_isInSetFromUser = $val;
    }

    public function isInSetFromUser()
    {
        return $this->_isInSetFromUser;
    }
    
    /**
     * returns TRUE if this group has no filters
     * 
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_filterObjects);
    }
    
    /**
     * returns true if filter for a field is set in this group
     *
     * @param string $_field
     * @param boolean $_recursive
     * @return bool
     */
    public function isFilterSet($_field, $_recursive = false)
    {
        foreach ($this->_filterObjects as $object) {
            if ($object instanceof Tinebase_Model_Filter_Abstract) {
                if ($object->getField() == $_field) {
                    return true;
                }
            } elseif ($_recursive && $object instanceof Tinebase_Model_Filter_FilterGroup) {
                if ($object->isFilterSet($_field, $_recursive)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * gets additional columns required for from() of search Zend_Db_Select 
     * 
     * @return array
     */
    public function getRequiredColumnsForSelect()
    {
        $result = array();
        
        foreach ($this->getFilterObjects() as $filter) {
            if ($filter instanceof Tinebase_Model_Filter_Abstract) {
                $field = $filter->getField();
                if (   is_string($field) 
                    && (isset($this->_filterModel[$field]) || array_key_exists($field, $this->_filterModel)) 
                    && (isset($this->_filterModel[$field][TMFA::OPTIONS]) || array_key_exists(TMFA::OPTIONS, $this->_filterModel[$field]))
                    && (isset($this->_filterModel[$field][TMFA::OPTIONS]['requiredCols']) || array_key_exists('requiredCols', $this->_filterModel[$field][TMFA::OPTIONS]))
                ) {
                    $result = array_merge($result, $this->_filterModel[$field][TMFA::OPTIONS]['requiredCols']);
                }
            } else if ($filter instanceof Tinebase_Model_Filter_FilterGroup) {
                $result = array_merge($result, $filter->getRequiredColumnsForSelect());
            }
        }
        
        foreach ($this->_customData as $custom) {
            // check custom filter for requirements
            if ((isset($this->_filterModel[$custom[TMFA::FIELD]]['requiredCols']) || array_key_exists('requiredCols', $this->_filterModel[$custom[TMFA::FIELD]]))) {
                $result = array_merge($result, $this->_filterModel[$custom[TMFA::FIELD]]['requiredCols']);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, TRUE));
        
        return $result;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    /************************ protected functions *****************************/

    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        $this->_options = $_options;
    }

    public function hash()
    {
        $data = [];
        foreach ($this->_filterObjects as $object) {
            if ($object instanceof Tinebase_Model_Filter_FilterGroup) {
                $data[] = $object->hash();
            } else if ($object instanceof Tinebase_Model_Filter_Abstract) {
                $data[] = $object->getField() . '_' . $object->getOperator() . '_' . $object->getValue();
            }
        }

        sort($data);
        return md5($this->getModelName() . '_' . $this->getCondition() . '_' . join('_', $data));
    }

    /**
     * return filter object(s)
     *
     * @param string $_field
     * @param boolean $_getAll
     * @param boolean $_recursive
     * 
     * @return Tinebase_Model_Filter_Abstract|array
     */
    protected function _findFilter($_field, $_getAll = FALSE, $_recursive= FALSE)
    {
        $result = ($_getAll) ? [] : null;
        
        foreach ($this->_filterObjects as $object) {
            if ($_field === 'query' && $object instanceof Tinebase_Model_Filter_Query) {
                if ($_getAll) {
                    $result[] = $object;
                } else {
                    return $object;
                }
            } else if ($_recursive && $object instanceof Tinebase_Model_Filter_FilterGroup) {
                $filter = $object->getFilter($_field, $_getAll, $_recursive);
                
                if ($filter) {
                    if ($_getAll) {
                        $result = array_merge($result, $filter);
                    } else {
                        return $filter;
                    }
                }
            } else if ($object instanceof Tinebase_Model_Filter_Abstract) {
                if ($object->getField() == $_field) {
                    if ($_getAll) {
                        $result[] = $object;
                    } else {
                        return $object;
                    }                    
                }
            }
        }
        
        foreach ($this->_customData as $customFilter) {
            if ($customFilter[TMFA::FIELD] == $_field) {
                if ($_getAll) {
                    $result[] = $customFilter;
                } else {
                    return $customFilter;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * remove filter object
     *
     * @param string $_field
     * @param boolean $_recursive
     */
    protected function _removeFilter($_field, $_recursive = FALSE)
    {
        foreach ($this->_filterObjects as $key => $object) {
            if ($object instanceof Tinebase_Model_Filter_Abstract) {
                if ($object->getField() == $_field) {
                    unset($this->_filterObjects[$key]);
                }
            } else if ($object instanceof Tinebase_Model_Filter_FilterGroup && $_recursive) {
                $object->removeFilter($_field, true);
            }
        }

        foreach ($this->_customData as $key => $customFilter) {
            if ($customFilter[TMFA::FIELD] == $_field) {
                unset($this->_customData[$key]);
            }
        }
    }

    /**
     * returns filter for given model
     *
     * @param string $_modelOrFilterName model or model filter class name
     * @param array  $_data
     * @param string $_condition
     * @param array  $_options
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public static function getFilterForModel($_modelOrFilterName, array $_data = array(), $_condition = '', $_options = array())
    {
        if (preg_match('/Filter$/', $_modelOrFilterName)) {
            $modelName = preg_replace('/Filter$/', '', $_modelOrFilterName);
            $modelFilterClass = $_modelOrFilterName;
        } else {
            $modelName = $_modelOrFilterName;
            $modelFilterClass = $modelName . 'Filter';
        }

        if (! class_exists($modelFilterClass)) {
            // TODO check if model class exists?
            //if (class_exists($modelName))

            // use generic filter model
            $filter = new Tinebase_Model_Filter_FilterGroup(array(), $_condition, $_options);
            $filter->setConfiguredModel($modelName);
            $filter->setFromArray($_data);
        } else {
            $filter = new $modelFilterClass($_data, $_condition, $_options);
        }

        return $filter;
    }

    public function filterWalk(callable $func) {
        foreach ($this->_filterObjects as $filter) {
            $func($filter);
        }
    }
    
    ###### iterator interface ###########
    public function rewind(): void {
        reset($this->_filterObjects);
    }

    #[\ReturnTypeWillChange]
    public function current() {
        return current($this->_filterObjects);
    }

    #[\ReturnTypeWillChange]
    public function key() {
        return key($this->_filterObjects);
    }

    public function next(): void {
        next($this->_filterObjects);
    }

    public function valid(): bool {
        return $this->current() !== false;
    }
}
