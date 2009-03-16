<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class SambaSAM_Ldap
 * 
 * Samba Account Managing
 * 
 * @package Tinebase
 * @subpackage Samba
 */
class SambaSAM_Ldap
{

    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

   /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_SambaSAM_Ldap
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     * don't use the constructor. use the singleton 
     */
    private function __construct(array $_options) 
    {
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

   /**
     * the singleton pattern
     *
     * @param  array $options Options used in connecting, binding, etc.
     * @return Tinebase_SambaSAM_Ldap
     */
    public static function getInstance(array $_options = array()) 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_SambaSAM_Ldap($_options);
        }
        
        return self::$_instance;
    }

    /**
     * adds sam properties for a new user
     * 
     * @param Tinebase_Model_SAMUser $_user
     * @return Tinebase_Model_SAMUser
     */
	public function addUser($_userId, Tinebase_Model_SAMUser $_samUser)
	{

	}
	
	/**
     * updates sam properties for an existing user
     * 
     * @param Tinebase_Model_SAMUser $_user
     * @return Tinebase_Model_SAMUser
     */
	public function updateUser($_userId, Tinebase_Model_SAMUser $_samUser)
	{

	}

	
	/**
     * delete sam user
     *
     * @param int $_userId
     */
	public function deleteUser($_userId)
	{

	}


    /**
     * set the password for given account
     * 
     * @param   int $_userId
     * @param   string $_password
     * @param   bool $_encrypt encrypt password
     * @return  void
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_loginName, $_password, $_encrypt = TRUE)
	{

	}


	/**
     * sets/unsets expiry date 
     *
     * @param   int         $_userId
     * @param   Zend_Date   $_expiryDate
     */
    public function setExpiryDate($_userId, $_expiryDate)
	{

	}

	
	/**
     * adds sam properties to a new group
     *
	 * @param  int                     $_groupId
     * @param  Tinebase_Model_SAMGroup $_samGroup
     * @return Tinebase_Model_SAMGroup
     */
	public function addGroup($_groupId, Tinebase_Model_SAMGroup $_samGroup)
	{

	}


	/**
	 * updates sam properties on an updated group
	 *
	 * @param  int                     $_groupId
     * @param  Tinebase_Model_SAMGroup $_samGroup
	 * @return Tinebase_Model_SAMGroup
	 */
	public function updateGroup($_groupId, Tinebase_Model_SAMGroup $_samGroup)
	{

	}


	/**
	 * deletes sam groups
	 * 
	 * @param  array $_groupIds
	 * @return void
	 */
	public function deleteGroups(array $_groupIds)
	{

	}

	
}  
