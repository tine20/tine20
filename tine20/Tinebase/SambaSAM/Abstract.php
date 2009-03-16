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
 * abstract class SambaSAM_Abstract
 * 
 * Samba Account Managing
 * 
 * @package Tinebase
 * @subpackage Samba
 */
abstract class SambaSAM_Abstract
{

	/**
     * holds crypt engine
     *
     * @var Crypt_CHAP_MSv1
     */
    protected $_cryptEngine = NULL;
 
    /**
     * adds sam properties for a new user
     * 
     * @param Tinebase_Model_SAMUser $_user
     * @return Tinebase_Model_SAMUser
     */
	abstract public function addUser($_userId, Tinebase_Model_SAMUser $_samUser);
	
	/**
     * updates sam properties for an existing user
     * 
     * @param Tinebase_Model_SAMUser $_user
     * @return Tinebase_Model_SAMUser
     */
	abstract public function updateUser($_userId, Tinebase_Model_SAMUser $_samUser);
	
	/**
     * delete sam user
     *
     * @param int $_userId
     */
	abstract public function deleteUser($_userId);

    /**
     * set the password for given account
     * 
     * @param   int $_userId
     * @param   string $_password
     * @param   bool $_encrypt encrypt password
     * @return  void
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_loginName, $_password, $_encrypt = TRUE);

	/**
     * sets/unsets expiry date 
     *
     * @param   int         $_userId
     * @param   Zend_Date   $_expiryDate
     */
    abstract public function setExpiryDate($_userId, $_expiryDate);

	abstract public function addGroup($_groupId, Tinebase_Model_SAMGroup $_samGroup);

	abstract public function updateGroup($_groupId, Tinebase_Model_SAMGroup $_samGroup);

	abstract public function deleteGroup($_groupId);
	
	/**
     * returns crypt engine for NT/LMPasswords
     *
     * @return Crypt_CHAP_MSv1
     */
    protected function _getCryptEngine()
    {
        if (! $this->_cryptEngine) {
            $this->_cryptEngine = new Crypt_CHAP_MSv1();
        }
        
        return $this->_cryptEngine;
    }
    
    /**
     * generates LM password
     *
     * @param  string $_password uncrypted original password
     * @return string LM password
     */        
    protected function _generateLMPasswords($_password)
    {
        $lmPassword = strtoupper(bin2hex($this->_getCryptEngine()->lmPasswordHash($_password)));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $lmPassword: ' . $lmPassword);
        
        return $lmPassword;
    }
    
    /**
     * generates NT password
     *
     * @param  string $_password uncrypted original password
     * @return string NT password
     */ 
    protected function _generateLNTPasswords($_password)
    {
        $ntPassword = strtoupper(bin2hex($this->_getCryptEngine()->ntPasswordHash($_password)));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ntPassword: ' . $ntPassword);
        
        return $ntPassword;
    }

}  
