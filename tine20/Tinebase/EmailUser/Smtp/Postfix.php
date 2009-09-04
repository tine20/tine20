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
 * @todo        check domain handling
 * @todo        remove verbose debug output
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
-- Table structure for table `forwardings`
--

CREATE TABLE IF NOT EXISTS `forwardings` (
  `source` varchar(80) NOT NULL,
  `destination` text NOT NULL,
  KEY `source` (`source`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `email` varchar(80) NOT NULL,
  `passwd` varchar(34) default NULL,
  `quota` int(10) default '10485760',
  `userid` varchar(100) NOT NULL,
  `encryption_type` varchar(20) NOT NULL default 'md5',
  `client_idnr` bigint(20) NOT NULL,
  `forward_only` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
     * @todo add those to smtp config?
     */
    protected $_config = array(
        'prefix'            => '',
        'userTable'         => 'users',
        'forwardTable'      => 'forwardings',
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
        $this->_config['domain'] = (isset($smtpConfig['domain'])) ? $smtpConfig['domain'] : '';
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
        $user = Tinebase_User::getInstance()->getFullUserById($_userId);
        
        $select = $this->_db->select();
        $select->from($this->_tableName);
        
        $select->where($this->_db->quoteIdentifier('userid') . ' = ?', $user->accountLoginName)
               ->where($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
               ->order('email');

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
                
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound('Postfix config for user ' . $_userId . ' not found!');
        }
        
        // add aliases
        $aliases = array();
        foreach ($queryResult as $row) {
            if ($row['email'] !== $user->accountEmailAddress) {
                $aliases[] = $row['email'];
            } else {
                $result = $this->_rawDataToRecord($row);
            }
        }
        $result->emailAliases = $aliases;
        
        // add forwards
        $result->emailForwards = $this->_getForwards($user->accountEmailAddress);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));        
        
        return $result;
    }

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * @throws Tinebase_Exception_UnexpectedValue
     */
	public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    if (! $_user->accountEmailAddress) {
	        throw new Tinebase_Exception_UnexpectedValue('User has no email address. This is mandatory for adding him or her to postfix table.');
	    }
	    
	    $userId = $_user->accountLoginName;
	    if (isset($this->_config['domain']) && ! empty($this->_config['domain'])) {
            $userId .= '@' . $this->_config['domain'];
        }
	    $_emailUser->emailUserId = $userId;
	    $_emailUser->emailAddress = $_user->accountEmailAddress;
	    
	    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding new postfix user ' . $_emailUser->emailUserId);
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        $this->_db->insert($this->_tableName, $recordArray);
        
        // add forwards and aliases
        $this->_setAliases($_emailUser);
        $this->_setForwards($_emailUser);
        
        return $this->getUserById($_user->getId());
	}
	
	/**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * @throws Tinebase_Exception_UnexpectedValue
     */
	public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
        if (! $_user->accountEmailAddress) {
            throw new Tinebase_Exception_UnexpectedValue('User has no email address. This is mandatory for adding him or her to postfix table.');
        }
	    
        $_emailUser->emailUserId = $_user->accountLoginName;
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('email') . ' = ?', $_user->accountEmailAddress),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
        );
        
        $this->_db->update($this->_tableName, $recordArray, $where);
        
        // add forwards and aliases
        $this->_setAliases($_emailUser, TRUE);
        $this->_setForwards($_emailUser, TRUE);
        
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
	    
	    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set postfix password for user ' . $user->accountLoginName);
	    
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
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('userid') . ' = ?', $user->accountLoginName),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
        );
        
        $this->_db->delete($this->_tableName, $where);

        // delete forwards
        $this->_deleteForwards($user->accountEmailAddress);
    }
    
    /**
     * set email aliases
     * 
     * @param Tinebase_Model_EmailUser $_emailUser
     * @param boolean $_deleteFirst
     * @return array
     */
    protected function _setAliases($_emailUser, $_deleteFirst = FALSE)
    {
        if ($_deleteFirst) {
            $this->_deleteAliases($_emailUser->emailAddress, $_emailUser->emailUserId);
        }
        
        foreach ($_emailUser->emailAliases as $aliasAddress) {
            $alias = clone($_emailUser);
            $alias->emailAddress = $aliasAddress;
            
            $aliasArray = $this->_recordToRawData($alias);
            $this->_db->insert($this->_tableName, $aliasArray);
        }
    }
    
    /**
     * delete aliases
     * 
     * @param string $_emailAddress
     * @return void
     */
    protected function _deleteAliases($_emailAddress, $_emailUserId)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('userid') . ' = ?', $_emailUserId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('email') . ' != ?', $_emailAddress),
        );
        
        $this->_db->delete($this->_tableName, $where);
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
            $this->_deleteForwards($_emailUser->emailAddress);
        }
        
        foreach ($_emailUser->emailForwards as $forwardAddress) {
            $forwardArray = array(
                'source'        => $_emailUser->emailAddress,
                'destination'   => $forwardAddress,
            );
            $this->_db->insert($this->_config['prefix'] . $this->_config['forwardTable'], $forwardArray);
        }
    }
    
    /**
     * get email forwards
     * 
     * @param string $_emailAddress
     * @return array
     */
    protected function _getForwards($_emailAddress)
    {
        $select = $this->_db->select();
        $select->from($this->_config['prefix'] . $this->_config['forwardTable'])
                ->order('destination')
                ->where($this->_db->quoteIdentifier('source') . ' = ?', $_emailAddress);

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        $result = array();
        foreach ($queryResult as $forward) {
            $result[] = $forward['destination'];
        }
        
        return $result;
    }
    
    /**
     * delete forwards
     * 
     * @param string $_emailAddress
     * @return void
     */
    protected function _deleteForwards($_emailAddress)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('source') . ' = ?', $_emailAddress),
        );
        
        $this->_db->delete($this->_config['prefix'] . $this->_config['forwardTable'], $where);
    }
}  
