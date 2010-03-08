<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase_Model_Filter_Container
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters by containers
 * 
 * If no operator / value is given, this filter filters for all containers
 * current user has readGrants for
 * 
 * NOTE: This filter already does all ACL checks. This means a controller only 
 *       has to make sure a containerfilter is set and if not add one
 * 
 * @todo implement setRequiredGrants
 */
class Tinebase_Model_Filter_Container extends Tinebase_Model_Filter_Abstract implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',       // value is expected to be a single container id
        1 => 'in',           // value is expected to be an array of container ids
        2 => 'specialNode',  // value is one of {all|shared|otherUsers|internal}
        3 => 'personalNode', // value is expected to be a user id 
        //5 => 'not',        // value is expected to be a single container id
    );
    
    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_Grants::GRANT_READ
    );
    
    /**
     * is resolved
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * resolved containerIds
     *
     * @var array
     */
    protected $_containerIds = array();
    
    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     */
    protected function _setOptions(array $_options)
    {
        if (! isset($_options['applicationName'])) {
            throw new Tinebase_Exception_InvalidArgument('container filter needs the applicationName options');
        }
        
        $_options['ignoreAcl'] = isset($_options['ignoreAcl']) ? $_options['ignoreAcl'] : false;
        
        $this->_options = $_options;
    }
    
    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
    }

    /**
     * set operator
     *
     * @param string $_operator
     */
    public function setOperator($_operator)
    {
        parent::setOperator($_operator);
        $this->_isResolved = FALSE;
    }
    
    /**
     * set value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        $value = array();
        foreach ((array) $_value as $v) {
            if (strpos($v, '/') !== FALSE) {
                $filter = $this->path2filter($v);
                if ($this->getOperator() !== 'in') {
                    $this->setOperator($filter['operator']);
                } else if ($filter['operator'] !== 'equals') {
                    throw new Tinebase_Exception_UnexpectedValue('filter constallation not supported');
                }
                $v = $filter['value'];
            }
            $value[] = $v;
        }
        
        parent::setValue(is_array($_value) ? $value : $value[0]);
        $this->_isResolved = FALSE;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_resolve();
        
        $_select->where($this->_getQuotedFieldName($_backend) .  ' IN (?)', empty($this->_containerIds) ? " " : $this->_containerIds);
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson == true) {
            $cc = Tinebase_Container::getInstance();
            switch ($this->_operator) {
                case 'equals':
                	if ($this->_value) {
	                    $container = $cc->getContainerById($this->_value);
	                    $result['value'] = $container->toArray();
	                    $result['value']['path'] = $cc->getPath($container);
                	}
                    break;
                case 'in':
                    $result['value'] = array();
                    foreach ($this->_value as $containerId) {
                    	if ($this->_value) {
	                        $container = $cc->getContainerById($containerId);
	                        $contaienrArray = $container->toArray();
	                        $contaienrArray['path'] = $cc->getPath($container);
	                        
	                        $result['value'][] = $contaienrArray;
                    	}
                    }
                    break;
                default:
                    // nothing to do
                    break;
            }
        }
        
        return $result;
    } 
    
    /**
     * resolve container ids
     *
     */
    protected function _resolve()
    {
        if ($this->_isResolved) {
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' already resolved');
            return;
        }

        switch ($this->_operator) {
            case 'equals':
            case 'in':
                $this->_containerIds = (array)$this->_value;
                if ($this->_options['ignoreAcl'] !== true) {
                    $readableContainerIds = $this->_getContainer('getContainerByACL');
                    $this->_containerIds = array_intersect($this->_containerIds, $readableContainerIds);
                }
                break;
            case 'personalNode':
                $this->_containerIds = $this->_getContainer('getPersonalContainer');
                break;
            case 'specialNode':
                switch ($this->_value) {
                    case 'all':
                        $this->_containerIds = $this->_getContainer('getContainerByACL');
                        break;
                    case 'shared':
                        $this->_containerIds = $this->_getContainer('getSharedContainer');
                        break;
                    case 'otherUsers':
                        $this->_containerIds = $this->_getContainer('getOtherUsersContainer');
                        break;
                    case 'internal':
                        $this->_containerIds = $this->_getContainer('getInternalContainer');
                        break;
                    default:
                        throw new Tinebase_Exception_UnexpectedValue('specialNode not supported.');
                        break;
                }
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('Operator not supported.');
                break;
        }
        
        $this->_isResolved = TRUE;
    }
    
    /**
     * wrapper for get container functions
     *
     * @param string $_function
     * @return array of container ids
     */
    protected function _getContainer($_function)
    {
        $currentAccount = Tinebase_Core::getUser();
        $appName = $this->_options['applicationName'];
        
        $ids = array();
        foreach ($this->_requiredGrants as $grant) {
            switch ($_function) {
                case 'getContainerByACL':
                    $result = $currentAccount->getContainerByACL($appName, $grant, TRUE);
                    break;
                case 'getPersonalContainer':
                    $result = $currentAccount->getPersonalContainer($appName, $this->_value, $grant)->getId();
                    break;
                case 'getSharedContainer':
                    $result = $currentAccount->getSharedContainer($appName, $grant)->getId();
                    break;
                case 'getOtherUsersContainer':
                    $result = $currentAccount->getOtherUsersContainer($appName, $grant)->getId();
                    break;
                case 'getInternalContainer':
                    $result = Tinebase_Container::getInstance()->getInternalContainer($currentAccount, $appName)->getId();
                    break;
            }
            
            $ids = array_merge($ids, (array)$result);
        }
                
        return array_unique($ids);
    }
    
    /**
     * transforms path into filter
     * 
     * @param  string $path
     * @return array
     */
    public static function path2filter($path)
    {
        if ($path == '/') {
            return array('operator' => 'specialNode', 'value' => 'all');
        }
        
        $parts = explode('/', $path);
        $partsCount = count($parts);
        
        switch($parts[1]) {
            case 'personal':
                switch ($partsCount) {
                    case 2: return array('operator' => 'specialNode', 'value' => 'otherUsers');
                    case 3: return array('operator' => 'personalNode', 'value' => $parts[2]);
                    case 4: return array('operator' => 'equals', 'value' => $parts[3]);
                }
            case 'shared':
                switch ($partsCount) {
                    case 2: return array('operator' => 'specialNode', 'value' => 'shared');
                    case 3: return array('operator' => 'equals', 'value' => $parts[2]);
                }
            case 'internal':
                switch ($partsCount) {
                    case 2: return array('operator' => 'specialNode', 'value' => $parts[1]);
                }
        }
        
        throw new Tinebase_Exception_UnexpectedValue('malformatted path');
    }
    
    /**
     * transforms filter data from filter group into new representation if old
     * container filter notation is in use
     *
     * @param  array &$_data
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public static function _transformLegacyData(array &$_data, $_containerProperty='container_id')
    {
        $legacyData = array();
        foreach ($_data as $key => $filterData) {
            if (array_key_exists('field', $filterData) && in_array($filterData['field'], array('containerType', 'container', 'owner'))) {
                $legacyData[$filterData['field']] = $filterData['value'];
                unset($_data[$key]);
            }
        }
        
        if (! empty($legacyData)) {
            
            if (! $legacyData['containerType']) {
                $legacyData['containerType'] = 'all';
            }
            
            switch($legacyData['containerType']) {
                case 'personal':
                    $operator = 'personalNode';
                    
                    if (! $legacyData['owner']) {
                        throw new Tinebase_Exception_UnexpectedValue('You need to set an owner when containerType is "Personal".');
                    }
                    $value = $legacyData['owner'];
                    break;
                case 'shared':
                case 'otherUsers':
                case 'internal':
                case 'all':
                    $operator = 'specialNode';
                    $value = $legacyData['containerType'];
                    break;    
                case 'singleContainer':
                    $operator = 'equals';
                    $value = $legacyData['container'];
                    break;
                default:
                    throw new Tinebase_Exception_UnexpectedValue('ContainerType not supported.');
                    break;
            }
            
            $_data[] = array(
                'field'    => $_containerProperty,
                'operator' => $operator,
                'value'    => $value
            );
        }
    }
}