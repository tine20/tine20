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
     * 
     * @todo add forwards
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
            throw new Tinebase_Exception_NotFound('DBmail config for user ' . $_userId . ' not found!');
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
     * 
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
	    
        $recordArray = $this->_recordToRawData($_emailUser);
        $this->_db->insert($this->_tableName, $recordArray);
        
        // add aliases
        foreach ($_emailUser->emailAliases as $aliasAddress) {
            $alias = clone($_emailUser);
            $alias->emailAddress = $aliasAddress;
            
            $aliasArray = $this->_recordToRawData($alias);
            $this->_db->insert($this->_tableName, $aliasArray);
        }
        // add forwards
        foreach ($_emailUser->emailForwards as $forwardAddress) {
            $forwardArray = array(
                'source'        => $_emailUser->emailAddress,
                'destination'   => $forwardAddress,
            );
            $this->_db->insert($this->_config['prefix'] . $this->_config['forwardTable'], $forwardArray);
        }
        
        $emailUser = $this->getUserById($_user->getId());
        
        return $emailUser;
	}
	
	/**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     * @todo    implement
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
	 * 
	 * @todo    implement
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
        $user = Tinebase_User::getInstance()->getFullUserById($_userId);
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('userid') . ' = ?', $user->accountLoginName),
            $this->_db->quoteInto($this->_db->quoteIdentifier('client_idnr') . ' = ?', $this->_clientId)
        );
        
        $this->_db->delete($this->_tableName, $where);

        // delete forwards
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('source') . ' = ?', $user->accountEmailAddress),
        );
        
        $this->_db->delete($this->_config['prefix'] . $this->_config['forwardTable'], $where);
    }
    
    /**
     * get email forwards
     * 
     * @param $_emailAddress
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
}  
