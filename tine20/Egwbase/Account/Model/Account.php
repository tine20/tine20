<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Account.php 499 2008-01-10 20:37:59Z lkneschke $
 */

/**
 * defines the datatype for one application
 */
class Egwbase_Account_Model_Account extends Egwbase_Record_Abstract
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'account_lid'           => 'StringTrim',
        'account_name'          => 'StringTrim',
        'n_fileas'              => 'StringTrim',
        'n_family'              => 'StringTrim',
        'n_given'               => 'StringTrim',
        'n_fn'                  => 'StringTrim',
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'account_id'            => array('Digits', 'presence' => 'required'),
        'account_lid'           => array('presence' => 'required'),
        'account_name'          => array('allowEmpty' => true),
        'account_lastlogin'     => array('allowEmpty' => true),
        'account_lastloginfrom' => array('allowEmpty' => true),
        'account_lastpwd_change' => array('Digits', 'allowEmpty' => true),
        'account_status'        => array('presence' => 'required'),
        'account_expires'       => array('allowEmpty' => true),
        'account_primary_group' => array('presence' => 'required'),
        'n_fileas'              => array('presence' => 'required'),
        'n_family'              => array('presence' => 'required'),
        'n_given'               => array('allowEmpty' => true),
        'n_fn'                  => array('presence' => 'required'),
    
    );

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'account_lastlogin',
        'account_lastpwd_change',
        'account_expires'
    );

   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'account_id';
    
    /**
     * check if current user has a given right for a given application
     *
     * @param string $_application the name of the application
     * @param int $_right the right to check for
     * @return bool
     */
    public function hasRight($_application, $_right)
    {
        $rights = Egwbase_Acl_Rights::getInstance();
        
        $result = $rights->hasRight($_application, $this->account_id, $_right);
        
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
        $rights = Egwbase_Acl_Rights::getInstance();
        
        $result = $rights->getRights($_application, $this->account_id);
        
        return $result;
    }
    
    /**
     * return the group ids current user is member of
     *
     * @return array list of group ids
     */
    public function getGroupMemberships()
    {
        $backend = Egwbase_Account::getBackend();
        
        $result = $backend->getGroupMemberships($this->account_id);
        
        return $result;
    }
    
    /**
     * update the lastlogin time of current user
     *
     * @param string $_ipAddress
     * @return void
     */
    public function setLoginTime($_ipAddress)
    {
        $backend = Egwbase_Account::getBackend();
        
        $result = $backend->setLoginTime($this->account_id, $_ipAddress);
        
        return $result;
    }
    
    /**
     * set the password for current user
     *
     * @param string $_password
     * @return void
     */
    public function setPassword($_password)
    {
        $backend = Egwbase_Account::getBackend();
        
        $result = $backend->setPassword($this->account_id, $_password);
        
        return $result;
    }
    
    /**
     * returns list of applications the current user is able to use
     *
     * this function takes group memberships into account. Applications the accounts is able to use
     * must have the 'run' right set 
     * 
     * @return array list of enabled applications for this account
     */
    public function getApplications()
    {
        $rights = Egwbase_Acl_Rights::getInstance();
        
        $result = $rights->getApplications($this->account_id);
        
        return $result;
    }
    
    /**
     * return all container, which the user has the requested right for
     *
     * used to get a list of all containers accesssible by the current user
     * 
     * @param string $_application the application name
     * @param int $_right the required right
     * @return Egwbase_Record_RecordSet
     */
    public function getContainerByACL($_application, $_right)
    {
        $container = Egwbase_Container::getInstance();
        
        $result = $container->getContainerByACL($this->account_id, $_application, $_right);
        
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
        $container = Egwbase_Container::getInstance();
        
        $result = $container->hasGrant($this->account_id, $_containerId, $_grant);
        
        return $result;
    }
    
    public function getPublicProperties()
    {
        $accountData = array(
            'accountId'             => $this->account_id,
            'accountDisplayName'    => $this->n_fileas,
            'accountLastName'       => $this->n_family,
            'accountFirstName'      => $this->n_given,
            'accountFullName'       => $this->n_given
        );
        
        $result = new Egwbase_Record_PublicAccountProperties($accountData, true);
        
        return $result;
    }
}