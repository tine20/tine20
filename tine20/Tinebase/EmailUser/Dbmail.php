<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        test it!
 * @todo        add forward / alias
 * @todo        add support for qmail
 * @todo        add factory / different backends?
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail attributes in ldap backend
 * 
 * @package Tinebase
 * @subpackage Ldap
 */
class Tinebase_EmailUser_Dbmail extends Tinebase_EmailUser_Abstract
{
    /**
     * @var Zend_Db_Adapter
     */
    protected $_db = NULL;

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'emailUID'      => 'dbmailUID', 
        'emailGID'      => 'dbmailGID', 
        'emailQuota'    => 'mailQuota',
        //'emailAliases'  => 'alias',
        //'emailForward'  => 'forward',
    );
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        //-- get db adapter
    }
    
    /**
     * get user by id
     *
     * @param   int         $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    public function getUserById($_userId) 
    {
        // @todo remove that later
        return new Tinebase_Model_EmailUser(array(
            'emailUID'      => 'uid',
            'emailGID'      => 'gid',
            'emailQuota'    => 10000,
        ));
        
        /*
        try {
            $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
            $ldapData = $this->_ldap->fetch($this->_options['userDn'], 'uidnumber=' . $userId);
            $user = $this->_ldap2User($ldapData);
        } catch (Exception $e) {
            throw new Exception('User not found');
        }
        
        return $user;
        */
    }

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     * @todo add defaults?
     */
	public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    /*
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_emailUser);
        
        $ldapData['objectclass'] = array_unique(array_merge($metaData['objectClass'], $this->_requiredUserObjectClass));
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        
        return $this->getUserById($_user->getId());
        */
	}
	
	/**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
	public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    // @todo remove that later
	    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_emailUser->toArray(), TRUE));
	    return $this->getUserById($_user->getId());
	    
	    /*
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_emailUser);
        
        // check if user has all required object classes.
        foreach ($this->_requiredUserObjectClass as $className) {
            if (! in_array($className, $metaData['objectClass'])) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn'] . ' had no email objectclass.');

                return $this->addUser($_user, $_emailUser);
            }
        }

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        
        return $this->getUserById($_user->getId());
        */
	}
}  
