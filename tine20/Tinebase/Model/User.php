<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        write more tests for functions
 * @todo        add phpdoc
 */

/**
 * defines the datatype for simple user object
 * 
 * this user object contains only public informations
 * its primary usecase are user selection interfaces
 * 
 * @package     Tinebase
 * @subpackage  User
 * @property    string  accountId
 */
class Tinebase_Model_User extends Tinebase_Record_Abstract
{
    /**
     * const to describe current account accountId independent
     * 
     * @var string
     */
    const CURRENTACCOUNT = 'currentAccount';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'accountId'             => 'StringTrim',
        //'accountLoginName'    => 'StringTrim',
        'accountDisplayName'    => 'StringTrim',
        'accountLastName'       => 'StringTrim',
        'accountFirstName'      => 'StringTrim',
        'accountFullName'       => 'StringTrim',
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'accountId'             => array('presence' => 'required'),
        //'accountLoginName'    => array('presence' => 'required'),
        'accountDisplayName'    => array('presence' => 'required'),
        'accountLastName'       => array('presence' => 'required'),
        'accountFirstName'      => array('allowEmpty' => true),
        'accountFullName'       => array('presence' => 'required'),
        'contact_id'            => array('allowEmpty' => true),
    );
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract#setFromArray($_data)
     */
    public function setFromArray(array $_data)
    {
        // always update accountDisplayName and accountFullName
        $_data['accountDisplayName'] = $_data['accountLastName'];
        if (!empty($_data['accountFirstName'])) {
            $_data['accountDisplayName'] .= ', ' . $_data['accountFirstName'];
        }
            
        $_data['accountFullName'] = $_data['accountLastName'];
        if (!empty($_data['accountFirstName'])) {
            $_data['accountFullName'] = $_data['accountFirstName'] . ' ' . $_data['accountLastName'];
        }
        
        parent::setFromArray($_data);
    }
    
    
   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'accountId';
    
    /**
     * check if current user has a given right for a given application
     *
     * @param string $_application the name of the application
     * @param int $_right the right to check for
     * @return bool
     */
    public function hasRight($_application, $_right)
    {
        $roles = Tinebase_Acl_Roles::getInstance();
        
        $result = $roles->hasRight($_application, $this->accountId, $_right);
        
        return $result;
    }
    
    /**
     * returns a bitmask of rights for current user and given application
     *
     * @param string $_application the name of the application
     * @return int bitmask of rights
     */
    public function getRights($_application)
    {
        $roles = Tinebase_Acl_Roles::getInstance();
        
        $result = $roles->getApplicationRights($_application, $this->accountId);
        
        return $result;
    }
    
    /**
     * return the group ids current user is member of
     *
     * @return array list of group ids
     */
    public function getGroupMemberships()
    {
        $backend = Tinebase_Group::getInstance();
        
        $result = $backend->getGroupMemberships($this->accountId);
        
        return $result;
    }
    
    /**
     * update the lastlogin time of current user
     *
     * @param string $_ipAddress
     * @return void
     * @todo write test for that
    */
    public function setLoginTime($_ipAddress)
    {
        $backend = Tinebase_User::getInstance();
        
        $result = $backend->setLoginTime($this->accountId, $_ipAddress);
        
        return $result;
    }
    
    /**
     * set the password for current user
     *
     * @param string $_password
     * @return void
     * @todo write test for that
     */
    public function setPassword($_password)
    {
        $backend = Tinebase_User::getInstance();
        
        $result = $backend->setPassword($this->accountId, $_password);
        
        return $result;
    }
    
    /**
     * returns list of applications the current user is able to use
     *
     * this function takes group memberships into user. Applications the user is able to use
     * must have the 'run' right set 
     * 
     * @param boolean $_anyRight is any right enough to geht app?
     * @return array list of enabled applications for this user
     */
    public function getApplications($_anyRight = FALSE)
    {
        $roles = Tinebase_Acl_Roles::getInstance();
        
        $result = $roles->getApplications($this->accountId, $_anyRight);
        
        return $result;
    }
    
    /**
     * return all container, which the user has the requested right for
     *
     * used to get a list of all containers accesssible by the current user
     * 
     * @param string $_application the application name
     * @param int $_right the required right
     * @param   bool   $_onlyIds return only ids
     * @return Tinebase_Record_RecordSet|array
     * @todo write test for that
     */
    public function getContainerByACL($_application, $_right, $_onlyIds = FALSE)
    {
        $container = Tinebase_Container::getInstance();
        
        $result = $container->getContainerByACL($this->accountId, $_application, $_right, $_onlyIds);
        
        return $result;
    }

    /**
     * return all personal container of the current user
     *
     * used to get a list of all personal containers accesssible by the current user
     * 
     * @param string $_application the application name
     * @return Tinebase_Record_RecordSet
     * @todo write test for that
     */
    public function getPersonalContainer($_application, $_owner, $_grant)
    {
        $container = Tinebase_Container::getInstance();
        
        $result = $container->getPersonalContainer($this, $_application, $_owner, $_grant);
        
        return $result;
    }
    
    public function getSharedContainer($_application, $_grant)
    {
        $container = Tinebase_Container::getInstance();
        
        $result = $container->getSharedContainer($this, $_application, $_grant);
        
        return $result;
    }
    
    public function getOtherUsersContainer($_application, $_grant)
    {
        $container = Tinebase_Container::getInstance();
        
        $result = $container->getOtherUsersContainer($this, $_application, $_grant);
        
        return $result;
    }
    
    /**
     * check if the current user has a given grant
     *
     * @param int $_containerId
     * @param int $_grant
     * @return boolean
     */
    public function hasGrant($_containerId, $_grant)
    {
        $container = Tinebase_Container::getInstance();
        
        $result = $container->hasGrant($this->accountId, $_containerId, $_grant);
        
        return $result;
    }
    
    /**
     * converts a int, string or Tinebase_Model_User to an accountid
     *
     * @param int|string|Tinebase_Model_User $_accountId the accountid to convert
     * @return int
     * @throws Tinebase_Exception_NotFound
     */
    static public function convertUserIdToInt($_accountId)
    {
        if ($_accountId instanceof Tinebase_Model_User) {
            if (empty($_accountId->accountId)) {
                throw new Tinebase_Exception_NotFound('accountId can not be empty');
            }
            $accountId = (string) $_accountId->accountId;
        } else {
            $accountId = (string) $_accountId;
        }
        
        if (empty($accountId)) {
            throw new Tinebase_Exception_NotFound('accountId can not be empty');
        }
        
        return $accountId;
    }
    
    public function sanitizeAccountPrimaryGroup()
    {  
        try {
            Tinebase_Group::getInstance()->getGroupById($this->accountPrimaryGroup);
        } catch (Tinebase_Exception_Record_NotDefined $e) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Could not resolve accountPrimaryGroupgroup (' . $this->accountPrimaryGroup . '): ' . $e->getMessage() . ' => set default user group id as accountPrimaryGroup for account ' . $this->getId());
            $this->accountPrimaryGroup = Tinebase_Group::getInstance()->getDefaultGroup()->getId();
        }
        
        return $this->accountPrimaryGroup;
    }
}
