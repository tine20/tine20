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
 * @todo        add validation of email addresses?
 * @todo        check domains when creating aliases
 */

/**
 * class Tinebase_EmailUser_Smtp_Postfix
 * 
 * Email User Settings Managing for postfix/smtp attributes
 * 
 * @package Tinebase
 * @subpackage User
 * 
 * example postfix db schema:
 * 
--
-- Database: `postfix`
--

-- --------------------------------------------------------

--
-- Table structure for table `smtp_users`
--

CREATE TABLE IF NOT EXISTS `smtp_users` (
  `email` varchar(80) NOT NULL,
  `username` varchar(80) NOT NULL,
  `passwd` varchar(34) DEFAULT NULL,
  `quota` int(10) DEFAULT '10485760',
  `userid` varchar(40) NOT NULL,
  `encryption_type` varchar(20) NOT NULL DEFAULT 'md5',
  `client_idnr` bigint(20) NOT NULL,
  `forward_only` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userid`,`client_idnr`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `smtp_forwardings`
--

CREATE TABLE IF NOT EXISTS `smtp_forwardings` (
  `userid` varchar(80) NOT NULL,
  `forward` varchar(80) NOT NULL,
  KEY `userid` (`userid`, `forward`)
) ENGINE=Innodb DEFAULT CHARSET=utf8;

ALTER TABLE `smtp_forwardings`
  ADD CONSTRAINT `smtp_forwardings::userid--smtp_users::userid` FOREIGN KEY (`userid`) REFERENCES `smtp_users` (`userid`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Table structure for table `smtp_aliases`
--

CREATE TABLE IF NOT EXISTS `smtp_aliases` (
  `userid` varchar(80) NOT NULL,
  `alias` varchar(80) NOT NULL,
  KEY `emailalias` (`userid`, `alias`)
) ENGINE=Innodb DEFAULT CHARSET=utf8;

ALTER TABLE `smtp_aliases`
  ADD CONSTRAINT `smtp_aliases::userid--smtp_users::userid` FOREIGN KEY (`userid`) REFERENCES `smtp_users` (`userid`) ON DELETE CASCADE;

 */
class Tinebase_EmailUser_Smtp_Postfix extends Tinebase_EmailUser_Abstract
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
     * postfix config
     * 
     * @var array 
     * 
     * @todo add those / some of them to smtp config?
     */
    protected $_config = array(
        'prefix'            => 'smtp_',
        'userTable'         => 'users',
        'forwardTable'      => 'forwardings',
        'aliasTable'        => 'aliases',
        'encryptionType'    => 'md5',
    );

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'emailPassword'     => 'passwd', 
        'emailUserId'       => 'userid',
        'emailAddress'      => 'email',
        'emailForwardOnly'  => 'forward_only',
        'emailUsername'     => 'username',
    );
    
    /**
     * postfix readonly
     * 
     * @var array
     * @deprecated ?
     */
    protected $_readOnlyFields = array(
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
        //$this->_config['domain'] = (isset($smtpConfig['domain'])) ? $smtpConfig['domain'] : '';
        $this->_tableName = $this->_config['prefix'] . $this->_config['userTable'];
        
        $this->_db = Zend_Db::factory('Pdo_Mysql', $this->_config);
        
        $this->_clientId = $this->_convertToInt(Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId());
    }
    
    /**
     * get user by id
     *
     * @param   string         $_userId
     * @return  Tinebase_Model_EmailUser user
     * @throws Tinebase_Exception_NotFound
     */
    public function getUserById($_userId) 
    {
        $select = $this->_db->select();
        $select->from($this->_tableName);
        
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.userid') . ' = ?', $_userId)
               ->where($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
               ->group($this->_tableName . '.userid')
               ->joinLeft(
            /* table  */ array('aliases' => $this->_config['prefix'] . $this->_config['aliasTable']), 
            /* on     */ $this->_db->quoteIdentifier('aliases.userid') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.userid'),
            /* select */ array('emailAliases' => 'GROUP_CONCAT( DISTINCT ' . $this->_db->quoteIdentifier('aliases.alias') . ')'))
               ->joinLeft(
            /* table  */ array('forwards' => $this->_config['prefix'] . $this->_config['forwardTable']), 
            /* on     */ $this->_db->quoteIdentifier('forwards.userid') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.userid'),
            /* select */ array('emailForwards' => 'GROUP_CONCAT( DISTINCT ' . $this->_db->quoteIdentifier('forwards.forward') . ')'))
               ->order($this->_tableName . '.email');

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
                
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound('Postfix config for user ' . $_userId . ' not found!');
        }
        
        $result = $this->_rawDataToRecord($queryResult);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result->toArray(), TRUE));
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));        
        
        return $result;
    }

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser|NULL
     */
	public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    try {
	        $this->_tineUserToEmailUser($_user, $_emailUser);
	    } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            return $_emailUser;
	    }
	    
	    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding new postfix user ' . $_emailUser->emailUserId);
	    //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_emailUser->toArray(), TRUE));
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            $this->_db->insert($this->_tableName, $recordArray);
            
            // add forwards and aliases
            $this->_setAliases($_emailUser);
            $this->_setForwards($_emailUser);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $result = $this->getUserById($_user->getId());
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while creating email user: ' . $zdse->getMessage());
            $result = NULL;
        }
        
        return $result;
	}
	
	/**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser|NULL
     */
	public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
        try {
            $this->_tineUserToEmailUser($_user, $_emailUser);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            return $_emailUser;
        }
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('userid') .       ' = ?', $_user->getId()),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') .  ' = ?', $this->_clientId)
        );
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            $this->_db->update($this->_tableName, $recordArray, $where);
        
            // add forwards and aliases
            $this->_setAliases($_emailUser, TRUE);
            $this->_setForwards($_emailUser, TRUE);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            $result = $this->getUserById($_user->getId());
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while updating email user: ' . $zdse->getMessage());
            $result = NULL;
        }            
        
        return $result;
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
	    
	    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Set postfix password for user ' . $user->accountLoginName);
	    
	    $emailUser = $this->getUserById($user->getId());
	    $emailUser->emailPassword = $_password;
	    
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
        $user = Tinebase_User::getInstance()->getFullUserById($_userId);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete postfix settings for user ' . $user->accountLoginName);
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('userid') .       ' = ?', $_userId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') .  ' = ?', $this->_clientId)
        );
        
        $this->_db->delete($this->_tableName, $where);
    }
    
    /**
     * set email aliases
     * 
     * @param Tinebase_Model_EmailUser $_emailUser
     * @param boolean $_deleteFirst
     * @return void
     */
    protected function _setAliases($_emailUser, $_deleteFirst = FALSE)
    {
        if ($_deleteFirst) {
            $this->_deleteAliases($_emailUser->emailUserId);
        }
        
        if (! is_array($_emailUser->emailAliases)) {
            return;
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Setting aliases for ' . $_emailUser->emailAddress . ': ' 
            . print_r($_emailUser->emailAliases, TRUE));
        
        foreach ($_emailUser->emailAliases as $aliasAddress) {
            if (! empty($aliasAddress)) {
                $aliasArray = array(
                    'userid' => $_emailUser->emailUserId,
                    'alias' => $aliasAddress
                );
                $this->_db->insert($this->_config['prefix'] . $this->_config['aliasTable'], $aliasArray);
            }
        }
    }
    
    /**
     * delete aliases
     * 
     * @param string $_userId
     * @return void
     */
    protected function _deleteAliases($_userId)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('userid') . ' = ?', $_userId),
        );
        
        $this->_db->delete($this->_config['prefix'] . $this->_config['aliasTable'], $where);
    }
    
    /**
     * set email forwards
     * 
     * @param Tinebase_Model_EmailUser $_emailUser
     * @param boolean $_deleteFirst
     * @return array
     */
    protected function _setForwards($_emailUser, $_deleteFirst = FALSE)
    {
        if ($_deleteFirst) {
            $this->_deleteForwards($_emailUser->emailUserId);
        }
        
        if (! is_array($_emailUser->emailForwards)) {
            return;
        }
        
        foreach ($_emailUser->emailForwards as $forwardAddress) {
            if (! empty($forwardAddress)) {
                $forwardArray = array(
                    'userid' => $_emailUser->emailUserId,
                    'forward'   => $forwardAddress,
                );
                $this->_db->insert($this->_config['prefix'] . $this->_config['forwardTable'], $forwardArray);
            }
        }
    }
    
    /**
     * delete forwards
     * 
     * @param string $_emailAddress
     * @return void
     */
    protected function _deleteForwards($_userId)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('userid') . ' = ?', $_userId),
        );
        
        $this->_db->delete($this->_config['prefix'] . $this->_config['forwardTable'], $where);
    }
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array $_data
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawdata)
    {
        $result = parent::_rawDataToRecord($_rawdata);
        
        $result->emailAliases = explode(',', $_rawdata['emailAliases']);
        $result->emailForwards = explode(',', $_rawdata['emailForwards']);

        // sanitize aliases & forwards
        if (count($result->emailAliases) == 1 && empty($result->emailAliases[0])) {
            $result->emailAliases = array();
        }
        if (count($result->emailForwards) == 1  && empty($result->emailForwards[0])) {
            $result->emailForwards = array();
        } 
        
        return $result;
    }
    
    
    /**
     * convert tine user to email useru
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_EmailUser $_emailUser
     * @return void
     * @throws Tinebase_Exception_UnexpectedValue
     * 
     * @todo move this to EmailUser_Abstract or Model
     */
    protected function _tineUserToEmailUser(Tinebase_Model_FullUser $_user, Tinebase_Model_EmailUser $_emailUser)
    {
        if (! $_user->accountEmailAddress) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' User has no email address. This is mandatory for adding him or her to postfix table: ' . $_emailUser->emailUserId);
            throw new Tinebase_Exception_UnexpectedValue('User has no email address. This is mandatory for adding him or her to postfix table.');
        }
        
        $_emailUser->emailUserId = $_user->getId();
        $_emailUser->emailAddress = $_user->accountEmailAddress;
        $_emailUser->emailUsername = $_user->accountLoginName;
        
        // get imap domain and add it to username if available
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP, 'Tinebase', array());
        if (array_key_exists('domain', $imapConfig)) {
            $_emailUser->emailUsername .= '@' . $imapConfig['domain'];
        }
    }
}  
