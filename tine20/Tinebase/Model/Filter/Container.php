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
 * NOTE: This filter already does all ACL checks. This means a controller only 
 *       has to make sure a containerfilter is set and if not add one
 */
class Tinebase_Model_Filter_Container extends Tinebase_Model_Filter_Abstract implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',       // value is expected to represent a single container
        1 => 'in',           // value is expected to be an array of container representitions
        2 => 'specialNode',  // value is one of {all|shared|otherUsers|internal} (depricated please use equals with path instead)
        3 => 'personalNode', // value is expected to be a user id (depricated please use equals with path instead)
        //4 => 'not',        // value is expected to be a single container id
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
     * NOTE: incoming ids will be rewritten to their corresponding paths
     * NOTE: incoming *Node operators will be rewritten to their corresponding pathes
     * 
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        // transform *Node operators
        if (strpos($this->getOperator(), 'Node') !== FALSE) {
            $_value = $this->_node2path($this->getOperator(), $_value);
            $this->setOperator('equals');
        }
        
        $this->_flatten($_value);
            
        $value = array();
        foreach ((array) $_value as $v) {
            $this->_flatten($v);
            
            // transform id to path
            if (strpos($v, '/') === FALSE) {
                $v = Tinebase_Container::getInstance()->getContainerById($v)->getPath();
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
     * @param  bool $_valueToJson resolve value for json api
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson == true) {
            // NOTE: at this point operators should be equals or in and all values should be paths
            $values = array();
            foreach((array) $this->_value as $path) {
                $containerData = array('path' => $path);
                if (($containerId = Tinebase_Model_Container::pathIsContainer($path))) {
                    $containerData = array_merge($containerData, Tinebase_Container::getInstance()->getContainerById($containerId)->toArray());
                } else if (($ownerId = Tinebase_Model_Container::pathIsPersonalNode($path))) {
                    $owner = Tinebase_User::getInstance()->getUserById($ownerId);
                    $containerData['name']  = $owner->accountDisplayName;
                    $containerData['owner'] = $owner->toArray();
                }
                
                $values[] = $containerData;
            }
            $result['value'] = is_array($this->_value) ? $values : $values[0];
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
     * flatten resolved records
     * 
     * @param mixed &$_value
     */
    protected function _flatten(&$_value) {
        if (is_array($_value)) {
            if(array_key_exists('path', $_value)) {
                $_value = $_value['path'];
            } else if(array_key_exists('id', $_value)) {
                $_value = $_value['id'];
            }
        } 
    }
    
    /**
     * resolve container ids
     * 
     * - checks grants and silently removes containers without required grants
     * - sets internal $this->_containerIds
     */
    protected function _resolve()
    {
        if ($this->_isResolved) {
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' already resolved');
            return;
        }
        
        if (! in_array($this->_operator, array('equals', 'in'))) {
            throw new Tinebase_Exception_UnexpectedValue("Operator '{$this->_operator}' not supported.");
        }
        
        $this->_containerIds = array();
        foreach((array)$this->_value as $path) {
            $this->_containerIds = array_merge($this->_containerIds, $this->_resolvePath($path));
        }
        
        $this->_containerIds = array_unique($this->_containerIds);
        
        $this->_isResolved = TRUE;
    }
    
    /**
     * resolves a single path
     * 
     * @param  String $_path
     * @return array of container ids
     */
    protected function _resolvePath($_path)
    {
        $containerIds = array();
        
        if (($containerId = Tinebase_Model_Container::pathIsContainer($_path))) {
            if ($this->_options['ignoreAcl'] == TRUE) {
                $containerIds[] = $containerId;
            } else {
                foreach ($this->_requiredGrants as $grant) {
                    if (Tinebase_Core::getUser()->hasGrant($containerId, $grant)) {
                        $containerIds[] = $containerId;
                        break;
                    }
                }
                $containerIds = array_unique($containerIds);
            }
        } else if (($ownerId = Tinebase_Model_Container::pathIsPersonalNode($_path))) {
            $containerIds = $this->_resolveContainerNode('personal', $ownerId);
        } else {
            $node = $_path == '/' ? 'all' : substr($_path, 1);
            $containerIds = $this->_resolveContainerNode($node);
        }
        
        return $containerIds;
    }
    
    /**
     * wrapper for get container functions
     *
     * @todo implement handling for $this->_options['ignoreAcl'] == TRUE
     * 
     * @param  string $_function
     * @param  string $_ownerId    => needed for $_node == 'personal'
     * @return array of container ids
     */
    protected function _resolveContainerNode($_node, $_ownerId = NULL)
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
                    $result = $currentAccount->getPersonalContainer($appName, $_ownerId, $grant)->getId();
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
     * converts given *Node params to a path
     * 
     * @param  String $_operator
     * @param  String $_value
     * @return String
     */
    protected function _node2path($_operator, $_value)
    {
        switch ($_operator) {
            case 'specialNode':
                return '/' . ($_value != 'all' ?  $_value : '');
            case 'personalNode':
                return "/personal/{$_value}";
            default:
                throw new Tinebase_Exception_UnexpectedValue("operator '$_operator' not supported");
        }
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
        Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . 'HEADS UP DEVELOPERS: old container filter notation in use.  PLEASE UPDATE ');
        
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