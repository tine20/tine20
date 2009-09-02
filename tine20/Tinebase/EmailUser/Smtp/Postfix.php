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
 * @todo        add smtp fields to Tinebase_Model_EmailUser
 * @todo        finish implementation
 * @todo        add tests
 */

/**
 * class Tinebase_EmailUser_Smtp_Postfix
 * 
 * Email User Settings Managing for postfix/smtp attributes
 * 
 * @package Tinebase
 * @subpackage User
 */
class Tinebase_EmailUser_Smtp_Postfix extends Tinebase_EmailUser_Abstract
{
    /**
     * @var Zend_Db_Adapter
     */
    protected $_db = NULL;
    
    /**
     * client id
     *
     * @var string
     */
    protected $_clientId = NULL;
    
    /**
     * postfix config
     * 
     * @var array 
     * 
     * @todo add those to smtp config?
     */
    protected $_config = array(
        'prefix'            => 'postfix_',
        'userTable'         => 'users',
        'forwardTable'      => 'forwards',
        'encryptionType'    => 'md5',
    );

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
    /*
        'emailUID'          => 'user_idnr', 
        'emailPassword'     => 'passwd', 
        'emailMailQuota'    => 'maxmail_size',
        'emailMailSize'     => 'curmail_size',
        'emailSieveQuota'   => 'maxsieve_size',
        'emailSieveSize'    => 'cursieve_size',
        'emailUserId'       => 'userid',
        'emailLastLogin'    => 'last_login',
    */
    );
    
    /**
     * postfix readonly
     * 
     * @var array
     */
    protected $_readOnlyFields = array(
    /*
        'emailMailSize',
        'emailSieveQuota',
        'emailSieveSize',
        'emailLastLogin',
    */
    );
    
    /**
     * the constructor
     *
     * @todo get domain from imap config?
     */
    public function __construct()
    {
        $smtpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
        $this->_config = array_merge($smtpConfig['postfix'], $this->_config);
        $this->_config['domain'] = $smtpConfig['domain'];
        
        $this->_db = Zend_Db::factory('Pdo_Mysql', $this->_config);
        
        $this->_clientId = $this->_convertToInt(Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId());
    }
    
    /**
     * get user by id
     *
     * @param   string         $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    public function getUserById($_userId) 
    {
        /*
        $select = $this->_db->select();
        $select->from($this->_tableName);
        
        $select->where($this->_db->quoteIdentifier('user_idnr') . ' = ?', $this->_convertToInt($_userId))
               ->where($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
               ->limit(1);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound('DBmail config for user ' . $_userId . ' not found!');
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));        
        $result = $this->_rawDataToRecord($queryResult);
        
        return $result;
        */
    }

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     */
	public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    /*
	    $userId = $_user->accountLoginName;
	    if (isset($this->_config['domain']) && ! empty($this->_config['domain'])) {
            $userId .= '@' . $this->_config['domain'];
        }
	    $_emailUser->emailUserId = $userId;
	    $_emailUser->emailUID = $this->_convertToInt($_user->getId());
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));  
        
        $this->_db->insert($this->_tableName, $recordArray);
        
        $emailUser = $this->getUserById($_user->getId());
        
        // create INBOX for new user
        $this->_createMailbox($emailUser);
        
        return $emailUser;
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
	    /*
        $_emailUser->emailUserId = $_user->accountLoginName;
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));  
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('user_idnr') . ' = ?', $this->_convertToInt($_user->getId())),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
        );
        
        $this->_db->update($this->_tableName, $recordArray, $where);
        
        return $this->getUserById($_user->getId());
        */
	}
	
	/**
	 * update/set email user password
	 * 
	 * @param string $_userId
	 * @param string $_password
	 * @return Tinebase_Model_EmailUser
	 */
	public function setPassword($_userId, $_password)
	{
	    /*
	    $user = Tinebase_User::getInstance()->getFullUserById($_userId);
	    $emailUser = new Tinebase_Model_EmailUser(array(
            'emailUID'      => $this->_convertToInt($user->getId()),
	        'emailPassword' => $_password   
        ));
	    
        return $this->updateUser($user, $emailUser);
        */
	}
	
    /**
     * delete user by id
     *
     * @param   string         $_userId
     * @return  void
     */
    public function deleteUser($_userId) 
    {
        /*
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('user_idnr') . ' = ?', $this->_convertToInt($_userId)),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
        );
        
        $this->_db->delete($this->_tableName, $where);
        */
    }
}  
