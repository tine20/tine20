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
 */

/**
 * class Tinebase_EmailUser_Imap_Dbmail
 * 
 * Email User Settings Managing for dbmail attributes
 * 
 * @package Tinebase
 * @subpackage User
 */
class Tinebase_EmailUser_Imap_Dbmail extends Tinebase_EmailUser_Abstract
{
    /**
     * @var Zend_Db_Adapter
     */
    protected $_db = NULL;
    
    /**
     * user table name with prefix
     *
     * @var string
     */
    protected $_tableName = NULL;

    /**
     * client id
     *
     * @var string
     */
    protected $_clientId = NULL;
    
    /**
     * dbmail config
     * 
     * @var array 
     * 
     * @todo add those to imap config?
     */
    protected $_config = array(
        'prefix'            => 'dbmail_',
        'userTable'         => 'users',
        'encryptionType'    => 'md5',
        'mailboxTable'      => 'mailboxes'
    );

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'emailUID'          => 'user_idnr', 
        'emailPassword'     => 'passwd', 
        'emailMailQuota'    => 'maxmail_size',
        'emailMailSize'     => 'curmail_size',
        'emailSieveQuota'   => 'maxsieve_size',
        'emailSieveSize'    => 'cursieve_size',
        'emailUserId'       => 'userid',
        'emailLastLogin'    => 'last_login',
    );
    
    /**
     * dbmail readonly
     * 
     * @var array
     */
    protected $_readOnlyFields = array(
        'emailMailSize',
        'emailSieveQuota',
        'emailSieveSize',
        'emailLastLogin',
    );
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        $this->_config = array_merge($imapConfig['dbmail'], $this->_config);
        $this->_config['domain'] = (isset($imapConfig['domain'])) ? $imapConfig['domain'] : '';
        $this->_tableName = $this->_config['prefix'] . $this->_config['userTable'];
        
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
        $_emailUser->emailUserId    = $this->_generateUserId($_user->accountLoginName);
        $_emailUser->emailUID       = $this->_convertToInt($_user->getId());
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));  
        
        $this->_db->insert($this->_tableName, $recordArray);
        
        $emailUser = $this->getUserById($_user->getId());
        
        // create INBOX for new user
        $this->_createMailbox($emailUser);
        
        return $emailUser;
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
        $_emailUser->emailUserId = $this->_generateUserId($_user->accountLoginName);
        
        $recordArray = $this->_recordToRawData($_emailUser);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));  
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('user_idnr') . ' = ?', $this->_convertToInt($_user->getId())),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
        );
        
        $this->_db->update($this->_tableName, $recordArray, $where);
        
        return $this->getUserById($_user->getId());
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
	    $user = Tinebase_User::getInstance()->getFullUserById($_userId);
	    $emailUser = new Tinebase_Model_EmailUser(array(
            'emailUID'      => $this->_convertToInt($user->getId()),
	        'emailPassword' => $_password   
        ));
	    
        return $this->updateUser($user, $emailUser);
	}
	
    /**
     * delete user by id
     *
     * @param   string         $_userId
     * @return  void
     */
    public function deleteUser($_userId) 
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('user_idnr') . ' = ?', $this->_convertToInt($_userId)),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
        );
        
        $this->_db->delete($this->_tableName, $where);
    }
	
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array $_data
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawdata)
    {
        $data = array();
        foreach ($_rawdata as $key => $value) {
            $keyMapping = array_search($key, $this->_userPropertyNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    default: 
                        $data[$keyMapping] = $value;
                        break;
                }
            }
        }
        
        return new Tinebase_Model_EmailUser($data, true);
    }
    
    /**
     * returns array of raw dbmail data
     *
     * @param  Tinebase_Model_EmailUser $_user
     * @return array
     */
    protected function _recordToRawData(Tinebase_Model_EmailUser $_user)
    {
        $data = array();
        foreach ($_user as $key => $value) {
            $property = array_key_exists($key, $this->_userPropertyNameMapping) ? $this->_userPropertyNameMapping[$key] : false;
            if ($property && ! in_array($key, $this->_readOnlyFields)) {
                switch ($key) {
                    case 'emailPassword':
                        if ($this->_config['encryptionType'] == 'md5') {
                            $data[$property] = md5($value);
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . '  encryptionType not supported!');
                        }
                        break;
                        
                    default:
                        $data[$property] = $value;
                }
            }
        }
        
        $data['client_idnr'] = $this->_clientId;
        $data['encryption_type'] = $this->_config['encryptionType'];
        
        return $data;
    }
    
    /**
     * convert some string to absolute int with crc32 and abs
     * 
     * @param $_string
     * @return integer
     */
    protected function _convertToInt($_string)
    {
        return abs(crc32($_string));
    }
    
    /**
     * create mailbox for user
     * 
     * @param Tinebase_Model_EmailUser $_emailUser
     * @param string $_mailboxName
     * @return void
     */
    protected function _createMailbox(Tinebase_Model_EmailUser $_emailUser, $_mailboxName = 'INBOX')
    {
        $data = array(
            'owner_idnr'    => $_emailUser->emailUID,
            'name'          => $_mailboxName,
            'seen_flag'     => 1,
            'answered_flag' => 1,
            'deleted_flag'  => 1,
            'flagged_flag'  => 1,
            'recent_flag'   => 1,
            'draft_flag'    => 1,
        );
        
        $this->_db->insert($this->_config['prefix'] . $this->_config['mailboxTable'], $data);
    }
    
    /**
     * append domain name to userid if required
     * 
     * @param string $_userId the login name
     * @return string
     */
    protected function _generateUserId($_userId)
    {
        $result = $_userId;
        
        if (isset($this->_config['domain']) && ! empty($this->_config['domain'])) {
             $result .= '@' . $this->_config['domain'];
        }
        
        return $result;
    }
}  
