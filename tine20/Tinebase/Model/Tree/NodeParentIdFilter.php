<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        remove special node handling
 */

/**
 * Tinebase_Model_Tree_NodeParentIdFilter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 */
class Tinebase_Model_Tree_NodeParentIdFilter extends Tinebase_Model_Filter_Container 
{
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
                    $containerData = array_merge($containerData, Tinebase_Container::getInstance()->getContainerById($containerId, TRUE)->toArray());
                } else if (($ownerId = Tinebase_Model_Container::pathIsPersonalNode($path))) {
                    // transform current user 
                    $ownerId = $ownerId == "/personal/" . Tinebase_Model_User::CURRENTACCOUNT ? "/personal/" . Tinebase_Core::getUser()->getId() : $ownerId;                    
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
            } else if (Tinebase_Core::getUser()->hasGrant($containerId, $this->_requiredGrants)) {
                $containerIds[] = $containerId;
            }
        } else if (($ownerId = Tinebase_Model_Container::pathIsPersonalNode($_path))) {
            $containerIds = $this->_resolveContainerNode('personal', $ownerId);
        } else {
            $node = $_path == '/' ? 'all' : substr($_path, 1);
            $node = $node === 'personal' ? 'otherUsers' : $node;

            $containerIds = $this->_resolveContainerNode($node);
        }
        
        return $containerIds;
    }
    
    /**
     * wrapper for get container functions
     *
     * @param  string $_function
     * @param  string $_ownerId    => needed for $_node == 'personal'
     * @return array of container ids
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _resolveContainerNode($_node, $_ownerId = NULL)
    {
        $currentAccount = Tinebase_Core::getUser();
        $appName = $this->_options['applicationName'];
        
        switch ($_node) {
            case 'all':        return Tinebase_Container::getInstance()->getContainerByACL($currentAccount, $appName, $this->_requiredGrants, TRUE, $this->_options['ignoreAcl']);
            case 'personal':   return Tinebase_Container::getInstance()->getPersonalContainer($currentAccount, $appName, $_ownerId, $this->_requiredGrants, $this->_options['ignoreAcl'])->getId();
            case 'shared':     return Tinebase_Container::getInstance()->getSharedContainer($currentAccount, $appName, $this->_requiredGrants, $this->_options['ignoreAcl'])->getId();
            case 'otherUsers': return Tinebase_Container::getInstance()->getOtherUsersContainer($currentAccount, $appName, $this->_requiredGrants, $this->_options['ignoreAcl'])->getId();
            case 'internal':
                // @todo remove legacy code
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' Trying to fetch obsolete "/internal" node. Please make sure this filter is no longer used because this is deprecated.');
                $adminConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
                return array($adminConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK]);
            default:           throw new Tinebase_Exception_UnexpectedValue('specialNode ' . $_node . ' not supported.');
        }
                
        return $ids;
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
    public static function transformLegacyData(array &$_data, $_containerProperty = 'container_id')
    {
        $legacyData = array();
        foreach ($_data as $key => $filterData) {
            if (! is_array($filterData)) {
                $filterData = Tinebase_Model_Filter_FilterGroup::sanitizeFilterData($key, $filterData);
            }
            
            if (array_key_exists('field', $filterData) && in_array($filterData['field'], array('containerType', 'container', 'owner'))) {
                $legacyData[$filterData['field']] = $filterData['value'];
                unset($_data[$key]);
            }
        }
        
        if (! empty($legacyData)) {
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . 'HEADS UP DEVELOPERS: old container filter notation in use.  PLEASE UPDATE ');
            
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
