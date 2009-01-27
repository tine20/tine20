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
 *       has to make shure a containerfilter is set and if not add one
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
        Tinebase_Model_Container::GRANT_READ
    );
    
    /**
     * is resolved
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
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
        parent::setValue($_value);
        $this->_isResolved = FALSE;
    }
    
    /**
     * appeds sql to given select statement
     *
     * @param  Zend_Db_Select $_select
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select)
    {
        $this->_resolve();
        
        $db = Tinebase_Core::getDb();
        
        $_select->where($db->quoteIdentifier($this->_field) .  ' IN (?)', empty($this->_value) ? " " : $this->_value);
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
                $this->_value = (array)$this->_value;
                if ($this->_options['ignoreAcl'] !== true) {
                    $readableContainerIds = $this->_getContainer('getContainerByACL');
                    $this->_value = array_intersect($this->_value, $readableContainerIds);
                }
                break;
            case 'personalNode':
                $this->_value = $this->_getContainer('getPersonalContainer');
                break;
            case 'specialNode':
                switch ($this->_value) {
                    case 'all':
                        $this->_value = $this->_getContainer('getContainerByACL');
                        break;
                    case 'shared':
                        $this->_value = $this->_getContainer('getSharedContainer');
                        break;
                    case 'otherUsers':
                        $this->_value = $this->_getContainer('getOtherUsersContainer');
                        break;
                    case 'internal':
                        $this->_value = $this->_getContainer('getInternalContainer');
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
     * transforms filter data from filter group into new representation if old
     * container filter notation is in use
     *
     * @param  array &$_data
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public static function _transformLegacyData(array &$_data, $_containerProperty='container_id') {
        $legacyData = array();
        foreach ($_data as $key => $filterData) {
            if (in_array($filterData['field'], array('containerType', 'container', 'owner'))) {
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