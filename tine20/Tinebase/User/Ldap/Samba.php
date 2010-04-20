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
class Tinebase_User_Ldap_Samba
{

    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'sid'              => 'sambasid', 
        'primaryGroupSID'  => 'sambaprimarygroupsid', 
        'acctFlags'        => 'sambaacctflags',
        'homeDrive'        => 'sambahomedrive',
        'homePath'         => 'sambahomepath',
        'profilePath'      => 'sambaprofilepath',
        'logonScript'      => 'sambalogonscript',    
        'logonTime'        => 'sambalogontime',
        'logoffTime'       => 'sambalogofftime',
        'kickoffTime'      => 'sambakickofftime',
        'pwdLastSet'       => 'sambapwdlastset',
        'pwdCanChange'     => 'sambapwdcanchange',
        'pwdMustChange'    => 'sambapwdmustchange',
    );
    
    /**
     * group properties mapping
     *
     * @var array
     */
    protected $_groupPropertyNameMapping = array(
        'sid'              => 'sambasid', 
        'groupType'        => 'sambagrouptype',
    );

    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredUserObjectClass = array(
        'sambaSamAccount'
    );
    
    /**
     * objectclasses required for groups
     *
     * @var array
     */
    protected $_requiredGroupObjectClass = array(
        'sambaGroupMapping'
    );
        
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(Tinebase_Ldap $_ldap, $_options = null) 
    {
        if (!isset($_options[Tinebase_User_Ldap::PLUGIN_SAMBA]) || empty($_options[Tinebase_User_Ldap::PLUGIN_SAMBA]['sid'])) {
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
    public function inspectAddUser(Tinebase_Model_FullUser $_user, array &$_ldapData)
    {
        $_ldapData['objectclass'] = array_unique(array_merge($_ldapData['objectclass'], $this->_requiredUserObjectClass));
        
        // defaults
        $_ldapData['sambasid']           = $this->_options[Tinebase_User_Ldap::PLUGIN_SAMBA]['sid'] . '-' . (2 * $_ldapData['uidnumber'] + 1000);
        $_ldapData['sambaacctflags']     = $_user->accountStatus == 'disabled' ? '[UD         ]' : '[U          ]';
        $_ldapData['sambapwdcanchange']  = 1;
        $_ldapData['sambapwdmustchange'] = 2147483647;

        $_ldapData['sambaprimarygroupsid'] = $this->getGroupById($_user->accountPrimaryGroup)->sid;
    }
    
    /**
     * inspect data used to update user
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_user, array &$_ldapData)
    {
        $this->inspectAddUser($_user, $_ldapData);
    }
    
    public function inspectSetBlocked($_accountId, $_blockedUntilDate)
    {
    	// does nothing
    }
    
    public function inspectExpiryDate($_expiryDate, array &$_ldapData)
    {
        if ($_expiryDate instanceof Zend_Date) {
            // seconds since Jan 1, 1970
            $_ldapData['sambakickofftime'] = $_expiryDate->getTimestamp();
        } else {
            $_ldapData['sambakickofftime'] = array();
        }
    }
    
    public function inspectStatus($_status, array &$_ldapData)
    {
        $acctFlags = '[U          ]';
        $acctFlags[2] = $_status == 'disabled' ? 'D' : ' ';
        
        $_ldapData['sambaacctflags'] = $acctFlags;    	
    }
    
    public function inspectSetPassword($_loginName, $_password, $_encrypt, $_mustChange, array &$_ldapData)
    {
        if ($_encrypt !== true) {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' can not transform crypted password into nt/lm samba password. Make sure to reset password for user ' . $_loginName);
        } else {
            $_ldapData['sambantpassword'] = $this->_generateNTPassword($_password);
            $_ldapData['sambalmpassword'] = $this->_generateLMPassword($_password);
            $_ldapData['sambapwdlastset'] = Zend_Date::now()->getTimestamp();
            
            if ($_mustChange !== false) {
                $_ldapData['sambapwdmustchange'] = '1';
                $_ldapData['sambapwdcanchange'] = '1';
            } else {
                $_ldapData['sambapwdmustchange'] = '2147483647';
                $_ldapData['sambapwdcanchange'] = '1';
            }
        }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    /**
     * update user status
     *
     * @param   int         $_userId
     * @param   string      $_status
     */
    public function setStatus($_userId, $_status)
    {
        $metaData = $this->_getUserMetaData($_userId);
        
        $acctFlags = $this->getUserById($_userId)->acctFlags;
        if (empty($currentFlags)) {
            $acctFlags = '[U          ]';
        }
        $acctFlags[2] = $_status == 'disabled' ? 'D' : ' ';
        $ldapData = array('sambaacctflags' => $acctFlags);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
    }
	
}  
