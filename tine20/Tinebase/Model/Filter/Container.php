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
 * NOTE: this filter accepts multiple formats for incoming container values
 *  - id (always represents a single container)
 *  - path (may also represent a node)
 *  - array containing id or path
 *  
 * NOTE: If no operator / value is given, this filter filters for all containers
 * current user has readGrants for => @todo verfy this NOTE
 * 
 * NOTE: This filter already does all ACL checks. This means a controller only 
 *       has to make sure a containerfilter is set and if not add one
 */
class Tinebase_Model_Filter_Container extends Tinebase_Model_Filter_Abstract implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',       // value is expected to be a single container id
        1 => 'in',           // value is expected to be an array of container ids
        2 => 'specialNode',  // value is one of {all|shared|otherUsers|internal} (depricated please us path instead)
        3 => 'personalNode', // value is expected to be a user id (depricated please us path instead)
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
     * NOTE: incoming pathes representing a single container will be rewritten to their corresponding id's
     * NOTE: incoming *Node operators will be rewritten to their corresponding pathes
     * 
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        // transform *Node operators
        //if (strstr($this->getOperator(), 'Node') !== FALSE)
        
        // cope with resolved records
        if (is_array($_value) && array_key_exists('id', $_value)) {
            $_value = $_value['id'];
        } 
            
        $value = array();
        foreach ((array) $_value as $v) {
            if (is_array($v) && array_key_exists('id', $v)) {
                // cope with resolved records
                $v = $v['id'];
            } else if (strpos($v, '/') !== FALSE) {
                $filter = $this->path2filter($v);
                if ($this->getOperator() !== 'in') {
                    $this->setOperator($filter['operator']);
                } else if ($filter['operator'] !== 'equals') {
                    // we need to resolve nodes already here
                    
                    // we only support single containers here yet!
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
     * NOTE: When returning values for JSON api, single containers represented by 
     *       ids or paths will be resolved with full container data. Moreover, all values
     *       will get the path in its data.
     *        
     * @param  bool $_valueToJson resolve value for json api
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson == true) {
            $cc = Tinebase_Container::getInstance();
            switch ($this->_operator) {
                case 'specialNode':
                    switch($this->_value) {
                        case 'all':
                            $result['value'] = array('path' => '/');
                            break;
                        case 'shared':
                            $result['value'] = array('path' => '/shared');
                            break;
                        case 'otherUsers':
                            $result['value'] = array('path' => '/personal');
                            break;
                        case 'internal':
                            $result['value'] = array('path' => '/internal');
                            break;
                    }
                    break;
                case 'personalNode':
                    $owner = Tinebase_User::getInstance()->getUserById($this->_value);
                    $result['value'] = array(
                        'path' => "/personal/{$this->_value}",
                        'name' => $owner->accountDisplayName,
                        'owner' => $owner->toArray()
                    );
                    break;
                case 'equals':
                	if ($this->_value) {
	                    $container = $cc->getContainerById($this->_value);
	                    $result['value'] = $container->toArray();
	                    $result['value']['path'] = $container->getPath();
                	}
                    break;
                case 'in':
                    $result['value'] = array();
                    foreach ($this->_value as $containerId) {
                    	if ($this->_value) {
	                        $container = $cc->getContainerById($containerId);
	                        $contaienrArray = $container->toArray();
	                        $contaienrArray['path'] = $container->getPath();
	                        
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
     * return ids of containers selected by filter
     * 
     * @return array
     */
    public function getContainerIds()
    {
        $this->_resolve();
        
        return $this->_containerIds;
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
                    $readableContainerIds = $this->_resolveContainerNode('all');
                    $this->_containerIds = array_intersect($this->_containerIds, $readableContainerIds);
                }
                break;
            case 'personalNode':
                $this->_containerIds = $this->_resolveContainerNode('personal');
                break;
            case 'specialNode':
                // sanitize filter value
                if (is_array($this->_value)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Value should not be an array. Using first element.');
                    $this->_value = array_pop($this->_value);
                }
                
                $this->_containerIds = $this->_resolveContainerNode($this->_value);
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
    protected function _resolveContainerNode($_node)
    {
        $currentAccount = Tinebase_Core::getUser();
        $appName = $this->_options['applicationName'];
        
        $ids = array();
        foreach ($this->_requiredGrants as $grant) {
            switch ($_node) {
                case 'all':
                    $result = $currentAccount->getContainerByACL($appName, $grant, TRUE);
                    break;
                case 'personal':
                    $result = $currentAccount->getPersonalContainer($appName, $this->_value, $grant)->getId();
                    break;
                case 'shared':
                    $result = $currentAccount->getSharedContainer($appName, $grant)->getId();
                    break;
                case 'otherUsers':
                    $result = $currentAccount->getOtherUsersContainer($appName, $grant)->getId();
                    break;
                case 'internal':
                    $result = Tinebase_Container::getInstance()->getInternalContainer($currentAccount, $appName)->getId();
                    break;
                default:
                    throw new Tinebase_Exception_UnexpectedValue('specialNode not supported.');
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
        
        throw new Tinebase_Exception_UnexpectedValue('malformatted path: ' . $path);
    }
    
    /**
     * transforms filter data from filter group into new representation if old
     * container filter notation is in use
     *
     * @param  array &$_data
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public static function transformLegacyData(array &$_data, $_containerProperty='container_id')
    {
        Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . 'HEADS UP DEVELOPERS: old container filter notation in use.  PLEASE UPDATE' . print_r(debug_backtrace(), TRUE));
        
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