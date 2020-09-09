<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Container
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
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Container extends Tinebase_Model_Filter_Abstract implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',       // value is expected to represent a single container
        1 => 'in',           // value is expected to be an array of container representations
        2 => 'specialNode',  // value is one of {all|shared|otherUsers|internal} (deprecated please use equals with path instead)
        3 => 'personalNode', // value is expected to be a user id (deprecated please use equals with path instead)
        4 => 'not',         // value is expected to be a single container id
        5 => 'notin',       // value is expected to be an array of container representations
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
        if (! isset($_options['modelName'])) {
            throw new Tinebase_Exception_InvalidArgument('Container filter needs the modelName option');
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
            $v = trim($v);
            
            // transform id to path
            if (strpos($v, '/') === FALSE) {
                try {
                    $container = Tinebase_Container::getInstance()->getContainerById($v, TRUE);
                    $v = $container ? $container->getPath() : '/';

                } catch (Tinebase_Exception_InvalidArgument $teia) {
                    Tinebase_Exception::log($teia);
                    $v = '/';
                }
            }
            $value[] = $v;
        }
        
        parent::setValue(is_array($_value)
            ? $value
            : (isset($value[0]) ? $value[0] : null)
        );
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

        if ($this->_operator === 'notin' || $this->_operator === 'not') {
            $_select->where($this->_getQuotedFieldName($_backend) . ' NOT IN (?)', empty($this->_containerIds) ? new Zend_Db_Expr('NULL') : $this->_containerIds);
        } else {
            $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($this->_containerIds) ? new Zend_Db_Expr('NULL') : $this->_containerIds);
        }
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
                try {
                    if (($containerId = Tinebase_Model_Container::pathIsContainer($path))) {
                        $containerData = array_merge($containerData, Tinebase_Container::getInstance()->getContainerById($containerId, TRUE)->toArray());
                    } else if (($ownerId = Tinebase_Model_Container::pathIsPersonalNode($path))) {
                        // transform current user
                        $owner = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $ownerId);
                        $containerData['name'] = $owner->accountDisplayName;
                        $containerData['path'] = "/personal/$ownerId";
                        $containerData['owner'] = $owner->toArray();
                    }
                } catch(Tinebase_Exception_NotFound $e) {

                }

                $values[] = $containerData;
            }
            $result['value'] = is_array($this->_value)
                ? $values
                : (isset($values[0]) ? $values[0] : null);
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
            if((isset($_value['path']) || array_key_exists('path', $_value))) {
                $_value = $_value['path'];
            } else if((isset($_value['id']) || array_key_exists('id', $_value))) {
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
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                ' already resolved');
            return;
        }
        
        if (! in_array($this->_operator, array('equals', 'in', 'notin', 'not'))) {
            throw new Tinebase_Exception_UnexpectedValue("Operator '{$this->_operator}' not supported.");
        }
        
        $this->_containerIds = array();
        foreach((array)$this->_value as $path) {
            $this->_containerIds = array_merge($this->_containerIds, $this->_resolvePath($path));
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' resolved ids: ' . print_r($this->_containerIds, true));
        
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
            } else if (! is_object(Tinebase_Core::getUser()) || Tinebase_Core::getUser()->hasGrant($containerId, $this->_requiredGrants)) {
                $containerIds[] = $containerId;
            }
        } else if (($ownerId = Tinebase_Model_Container::pathIsPersonalNode($_path))) {
            $containerIds = $this->_resolveContainerNode('personal', $ownerId);
        } else {
            $node = $_path == '/' ? 'all' : substr($_path, 1);
            $node = $node === 'personal' ? Tinebase_Model_Container::TYPE_OTHERUSERS : $node;

            $containerIds = $this->_resolveContainerNode($node);
        }
        
        return $containerIds;
    }
    
    /**
     * wrapper for get container functions
     *
     * @param  string $_node
     * @param  string $_ownerId    => needed for $_node == 'personal'
     * @return array of container ids
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _resolveContainerNode($_node, $_ownerId = NULL)
    {
        $currentAccount = Tinebase_Core::getUser();
        $modelName = $this->_options['modelName'];
        
        switch ($_node) {
            case 'all':        return Tinebase_Container::getInstance()->getContainerByACL($currentAccount,
                $modelName, $this->_requiredGrants, TRUE, $this->_options['ignoreAcl']);
            case 'personal':   return Tinebase_Container::getInstance()->getPersonalContainer($currentAccount,
                $modelName, $_ownerId, $this->_requiredGrants, $this->_options['ignoreAcl'])->getId();
            case 'shared':     return $this->_getSharedContainer($currentAccount, $modelName);
            case Tinebase_Model_Container::TYPE_OTHERUSERS:
                return Tinebase_Container::getInstance()->getOtherUsersContainer($currentAccount, $modelName,
                    $this->_requiredGrants, $this->_options['ignoreAcl'])->getId();
            case 'internal':
                // @todo remove legacy code
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' Trying to fetch obsolete "/internal" node. Please make sure this filter is no longer used because this is deprecated.');
                $adminConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
                return array($adminConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK]);
            default:           throw new Tinebase_Exception_UnexpectedValue('specialNode ' . $_node . ' not supported.');
        }
    }

    /**
     * fetch shared containers
     *
     * @param $currentAccount
     * @param $appName
     * @return mixed
     */
    protected function _getSharedContainer($currentAccount, $appName)
    {
        return Tinebase_Container::getInstance()->getSharedContainer($currentAccount, $appName, $this->_requiredGrants,
            $this->_options['ignoreAcl'])->getId();
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
}
