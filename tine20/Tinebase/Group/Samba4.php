<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Group ldap backend
 * 
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_Samba4 extends Tinebase_Group_Ldap
{
    /**
     * the ldap backend
     *
     * @var Tinebase_Ldap
     */
    protected $_ldap;
    
    /**
     * ldap config options
     *
     * @var array
     */
    protected $_options;
    
    /**
     * list of plugins 
     * 
     * @var array
     */
    protected $_plugins = array();
    
    /**
     * name of the ldap attribute which identifies a group uniquely
     * for example gidNumber, entryUUID, objectGUID
     * @var string
     */
    protected $_groupUUIDAttribute;
    
    /**
     * name of the ldap attribute which identifies a user uniquely
     * for example uidNumber, entryUUID, objectGUID
     * @var string
     */
    protected $_userUUIDAttribute;
    
    /**
     * the basic group ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_groupBaseFilter      = 'objectclass=group';
    
    /**
     * the basic user ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_userBaseFilter      = 'objectclass=user';
    
    /**
     * the basic user search scope
     *
     * @var integer
     */
    protected $_groupSearchScope     = Zend_Ldap::SEARCH_SCOPE_SUB;
    
    /**
     * the basic user search scope
     *
     * @var integer
     */
    protected $_userSearchScope      = Zend_Ldap::SEARCH_SCOPE_SUB;
    
    protected $_isReadOnlyBackend    = false;
    
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) 
    {
        parent::__construct();
        
        if(empty($_options['userUUIDAttribute'])) {
            $_options['userUUIDAttribute'] = 'objectGUID';
        }
        if(empty($_options['groupUUIDAttribute'])) {
            $_options['groupUUIDAttribute'] = 'objectGUID';
        }
        if(empty($_options['baseDn'])) {
            $_options['baseDn'] = $_options['userDn'];
        }
        if(empty($_options['userFilter'])) {
            $_options['userFilter'] = 'objectclass=user';
        }
        if(empty($_options['userSearchScope'])) {
            $_options['userSearchScope'] = Zend_Ldap::SEARCH_SCOPE_SUB;
        }
        if(empty($_options['groupFilter'])) {
            $_options['groupFilter'] = 'objectclass=group';
        }

        if (isset($_options['requiredObjectClass'])) {
            $this->_requiredObjectClass = (array)$_options['requiredObjectClass'];
        }
        if ((isset($_options['readonly']) || array_key_exists('readonly', $_options))) {
            $this->_isReadOnlyBackend = (bool)$_options['readonly'];
        }
        
        $this->_options = $_options;

        $this->_userUUIDAttribute  = strtolower($this->_options['userUUIDAttribute']);
        $this->_groupUUIDAttribute = strtolower($this->_options['groupUUIDAttribute']);
        $this->_baseDn             = $this->_options['baseDn'];
        $this->_userBaseFilter     = $this->_options['userFilter'];
        $this->_userSearchScope    = $this->_options['userSearchScope'];
        $this->_groupBaseFilter    = $this->_options['groupFilter'];
                
        try {
            $this->_ldap = new Tinebase_Ldap($this->_options);
            $this->_ldap->bind();
        } catch (Zend_Ldap_Exception $zle) {
            // @todo move this to Tinebase_Ldap?
            throw new Tinebase_Exception_Backend_Ldap('Could not bind to LDAP: ' . $zle->getMessage());
        }
        
        // get domain sid
        $domainConfig = $this->_ldap->search(
            'objectClass=domain',
            $this->_ldap->getFirstNamingContext(),
            Zend_Ldap::SEARCH_SCOPE_BASE,
            array('objectsid')
        )->getFirst();
        
        $this->_domainSidBinary = $domainConfig['objectsid'][0];
        $this->_domainSidPlain  = $this->_decodeSid($domainConfig['objectsid'][0]);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Domainsid " . $this->_domainSidPlain);
    }
    
    /**
     * get groupmemberships of user from sync backend
     * 
     * @param   Tinebase_Model_User|string  $_userId
     * @return  array  list of group ids
     */
    public function getGroupMembershipsFromSyncBackend($_userId)
    {
        $userId = $_userId instanceof Tinebase_Model_User ? $_userId->getId() : $_userId; 
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_userBaseFilter),
            Zend_Ldap_Filter::equals($this->_userUUIDAttribute, Zend_Ldap::filterEscape($userId))
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' ldap search filter: ' . $filter);
        
        $memberOfs = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array('memberof')
        )->getFirst();
        
        if ($memberOfs === null || !isset($memberOfs['memberof'])) {
            return array();
        }
        
        $filter = new Zend_Ldap_Filter_Or(array());
        foreach ($memberOfs['memberof'] as $memberOf) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' ldap search filter: ' . $memberOf);
            $filter = $filter->addFilter(Zend_Ldap_Filter::equals('distinguishedName', Zend_Ldap::filterEscape($memberOf)));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' ldap search filter: ' . $filter);
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array($this->_groupUUIDAttribute)
        );
        
        $memberships = array();
        
        foreach ($groups as $group) {
            $memberships[] = $this->_decodeGroupId($group[$this->_groupUUIDAttribute][0]);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' group memberships: ' . print_r($memberships, TRUE));
        
        return $memberships;
    }
    
    /**
     * resolve gidnumber to UUID(for example entryUUID) attribute
     * 
     * @param int $_gidNumber the gidnumber
     * @return string 
     */
    public function resolveGIdNumberToUUId($rid)
    {
        $groupSid = $this->_domainSidPlain . '-' .  $rid;
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter),
            Zend_Ldap_Filter::equals('objectsid', $groupSid)
        );
        
        $groupId = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            $this->_groupSearchScope, 
            array($this->_groupUUIDAttribute)
        )->getFirst();
        
        if ($groupId == null) {
            throw new Tinebase_Exception_NotFound('LDAP group with (gidnumber=' . $rid . ') not found');
        }
        
        return $this->_decodeGroupId($groupId[$this->_groupUUIDAttribute][0]);
    }
    
    /**
     * convert binary id to plain text id
     * 
     * @param  string  $groupId
     * @return string
     */
    protected function _decodeGroupId($groupId)
    {
        switch ($this->_groupUUIDAttribute) {
            case 'objectguid':
                return Tinebase_Ldap::decodeGuid($groupId);
                break;
                
            case 'objectsid':
                return Tinebase_Ldap::decodeSid($groupId);
                break;
                
            default:
                return $groupId;
                break;
        }
    }
}
