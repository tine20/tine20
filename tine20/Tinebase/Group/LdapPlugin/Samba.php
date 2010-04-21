<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_SambaSAM_Ldap
 * 
 * Samba Account Managing
 * 
 * todo: what about primaryGroupSID?
 *
 * @package Tinebase
 * @subpackage Samba
 */
class Tinebase_Group_LdapPlugin_Samba
{

    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * group properties mapping
     *
     * @var array
     */
    protected $_rowNameMapping = array(
        'sid'              => 'sambasid', 
        'groupType'        => 'sambagrouptype',
    );

    /**
     * objectclasses required for groups
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'sambaGroupMapping'
    );
        
    /**
     * the constructor
     *
     * @param  Tinebase_Ldap  $_ldap    the ldap resource
     * @param  array          $options  options used in connecting, binding, etc.
     */
    public function __construct(Tinebase_Ldap $_ldap, $_options = null) 
    {
        if (!isset($_options[Tinebase_Group_Ldap::PLUGIN_SAMBA]) || empty($_options[Tinebase_Group_Ldap::PLUGIN_SAMBA]['sid'])) {
            throw new Exception('you need to configure the sid of the samba installation');
        }
        
        $this->_ldap    = $_ldap;
        $this->_options = $_options;
    }
    
    /**
     * inspect data used to create user
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    public function inspectAddGroup(Tinebase_Model_Group $_group, array &$_ldapData)
    {
        $this->_group2ldap($_group, $_ldapData);    
    }
    
    /**
     * inspect data used to update user
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    public function inspectUpdateGroup(Tinebase_Model_Group $_group, array &$_ldapData)
    {
        $this->_group2ldap($_group, $_ldapData);
    }
    
    /**
     * convert objects with user data to ldap data array
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    protected function _group2ldap(Tinebase_Model_Group $_group, array &$_ldapData)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ENCRYPT ' . print_r($_ldapData, true));
        if (isset($_ldapData['objectclass'])) {
            $_ldapData['objectclass'] = array_unique(array_merge($_ldapData['objectclass'], $this->_requiredObjectClass));
        }
        if(isset($_ldapData['gidnumber'])) {
            $gidNumber = $_ldapData['gidnumber'];
        } else {
            $gidNumber = $this->_getGidNumber($_group->getId());
        }
        
        $_ldapData['sambasid']       = $this->_options[Tinebase_Group_Ldap::PLUGIN_SAMBA]['sid'] . '-' . (2 * $gidNumber + 1001);
        $_ldapData['sambagrouptype'] = 2;
        $_ldapData['displayname']    = $_group->name;
    }
    
    /**
     * return gidnumber of group
     * 
     * @param string $_gid
     * @return string
     */
    protected function _getGidNumber($_gid)
    {
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['groupUUIDAttribute'], Zend_Ldap::filterEscape($_gid)
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array('gidnumber')
        );
        
        if (count($groups) == 0) {
            throw new Tinebase_Exception_NotFound('Group not found! Filter: ' . $filter->toString());
        }
        
        $group = $groups->getFirst();
        
        if (empty($group['gidnumber'][0])) {
            throw new Tinebase_Exception_NotFound('Group has no gidnumber');
        }
        
        return $group['gidnumber'][0];
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * get group by id
     *
     * @param   int         $_groupId
     * @return  Tinebase_Model_SAMGroup group
     */
    public function getGroupById($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);

        $filter = Zend_Ldap_Filter::equals(
            $this->_options['groupUUIDAttribute'], Zend_Ldap::filterEscape($groupId)
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array()
        );
        
        if(count($groups) == 0) {
            throw new Exception('Group not found');
        }
        
        $group = $this->_ldap2Group($groups->getFirst());
        
        return $group;
    }
    
    /**
     * returns ldap metadata of given group
     *
     * @param  int         $_groupId
     * @return array 
     * 
     * @todo remove obsolete code
     */
    protected function _getGroupMetaData($_groupId)
    {
        $groupId = Tinebase_Model_Group::convertGroupIdToInt($_groupId);
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['groupUUIDAttribute'], Zend_Ldap::filterEscape($groupId)
        );
        
        $result = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('objectclass')
        )->getFirst();
        
        return $result;
        
        /*
        } catch (Tinebase_Exception_NotFound $e) {
            throw new Exception("group with id $groupId not found");
        }
        */
    }
    
    /**
     * Returns a group obj with raw data from ldap
     *
     * @param array $_ldapData
     * @return Tinebase_Model_SAMGroup
     */
    protected function _ldap2Group($_ldapData)
    {
        $groupArray = array();
        
        foreach ($_ldapData as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_groupPropertyNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                   default: 
                        $groupArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }

        $group = new Tinebase_Model_SAMGroup($groupArray);
        
        return $group;
    }
}  
