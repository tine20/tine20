<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Fronk
 * @version     $Id$
 * 
 * 
 * 
 * example dovecot db schema:
 * 
--
-- Database: `dovecot`
--
-- --------------------------------------------------------

--
-- Table structure for table `dovecot_users`
--

CREATE TABLE IF NOT EXISTS `dovecot_users` (
`userid` VARCHAR( 40 ) NOT NULL ,
`domain` VARCHAR( 80 ) DEFAULT NULL ,
`username` VARCHAR( 80 ) NOT NULL ,
`password` VARCHAR( 100 ) NOT NULL ,
`scheme` VARCHAR( 20 ) NOT NULL DEFAULT 'PLAIN-MD5',
`uid` VARCHAR( 20 ) NOT NULL ,
`gid` VARCHAR( 20 ) NOT NULL ,
`home` VARCHAR( 256 ) NOT NULL ,
`last_login` DATETIME NOT NULL ,
PRIMARY KEY ( `userid`, `domain` ) ,
UNIQUE ( `username` )
) ENGINE = InnoDB DEFAULT CHARSET=utf8;
-- --------------------------------------------------------

--
-- Table structure for table `dovecot_quotas`
--

CREATE TABLE IF NOT EXISTS `dovecot_quotas` (
`username` VARCHAR( 80 ) NOT NULL ,
`mail_quota` BIGINT NOT NULL DEFAULT '536870912',
`mail_size` BIGINT NOT NULL DEFAULT '0',
`sieve_quota` INT NOT NULL DEFAULT '0',
`sieve_size` INT NOT NULL DEFAULT '0',
CONSTRAINT `dovecot_quotas::username--dovecot_users::username` FOREIGN KEY (`username`) 
REFERENCES `dovecot_users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=Innodb DEFAULT CHARSET=utf8;
-- --------------------------------------------------------
* 
* 
* Example Dovecot Config files
* 
* 
--
-- Auth and User Query: dovecot-sql.conf 
-- 
-- Note: Currently Tine Sieve Quota is used as Message Quota
-- Note: Querys should be a single line
--

driver = mysql
connect = host=127.0.0.1 dbname=DovecotDB user=DovecotUser password=DovecotPass
default_pass_scheme = PLAIN-MD5

# passdb with userdb prefetch
password_query = SELECT dovecot_users.username AS user, 
	CONCAT('{', scheme, '}', password) AS password, 
	home AS userdb_home, 
	uid AS userdb_uid, 
	gid AS userdb_gid, 
	CONCAT('*:bytes=', CAST(dovecot_quotas.mail_quota AS CHAR), ':messages=', CAST(dovecot_quotas.sieve_quota AS CHAR)) AS userdb_quota_rule 
	FROM dovecot_users 
	LEFT JOIN dovecot_quotas
	ON (dovecot_users.username=dovecot_quotas.username)
	WHERE dovecot_users.username='%u'

# userdb for deliver
user_query = SELECT home, uid, gid, 
	CONCAT('*:bytes=', CAST(dovecot_quotas.mail_quota AS CHAR), ':messages=', CAST(dovecot_quotas.sieve_quota AS CHAR)) AS userdb_quota_rule 
	FROM dovecot_users 
	LEFT JOIN dovecot_quotas
	ON (dovecot_users.username=dovecot_quotas.username)
	WHERE dovecot_users.username='%u'
-- --------------------------------------------------------

-- 
-- Quotas Config: dovecot-dict-quota.conf
--
-- Note: Currently Tine Sieve Quota is used as Message Quota
--

connect = host=127.0.0.1 dbname=DovecotDB user=DovecotUser password=DovecotPass

map {
  pattern = priv/quota/storage
  table = dovecot_quotas
  username_field = username
  value_field = mail_size
}

map {
  pattern = priv/quota/messages
  table = dovecot_quotas
  username_field = username
  value_field = sieve_size
}
-- ----------------------------------------------------
* 
* 
* Example Postfix Config Files
* 
* 
--
-- Postfix LDA config: master.cf
--
-- Note: Dovecot Tine backend does not support peruser storage, 
-- 		but you can use the dovecot server for multiple 
-- 		sites. So in other words pertine storage

-- All mail is stored as vmail
dovecot   unix  -       n       n       -       -       pipe
    flags=DRhu user=vmail:vmail argv=/usr/lib/dovecot/deliver -d ${recipient}

-- Mail is stored on peruser/persite
dovelda   unix  -       n       n       -       -       pipe
    flags=DRhu user=dovelda:dovelda argv=/usr/bin/sudo /usr/lib/dovecot/deliver -d ${recipient}
-- ------------------------------------------------------

--
-- sudoers entry for peruser/persite config
--

Defaults:dovelda !syslog
dovelda          ALL=NOPASSWD:/usr/lib/dovecot/deliver
-- ----------------------------------------------------

--
-- Postfix virtual_mailbox_domains: sql-virtual_mailbox_domains.cf
--

user     = smtpUser
password = smtpPass
hosts    = 127.0.0.1
dbname   = smtp
query    = SELECT DISTINCT 1 FROM smtp_aliases WHERE SUBSTRING_INDEX(source, '@', -1) = '%s';
-- ----------------------------------------------------

--
-- Postfix sql-virtual_mailbox_maps: sql-virtual_mailbox_maps.cf
--

user     = smtpUser
password = smtpPass
hosts    = 127.0.0.1
dbname   = smtp
query    = SELECT 1 FROM smtp_users WHERE username='%s' AND forward_only=0
-- ----------------------------------------------------

--
-- Postfix sql-virtual_alias_maps: sql-virtual_alias_maps_aliases.cf
--

user     = smtpUser
password = smtpPass
hosts    = 127.0.0.1
dbname   = smtp
query = SELECT destination FROM smtp_aliases WHERE source='%s'

-- -----------------------------------------------------
*/

/**
 * class inebase_EmailUser_Imap_Dovecot
 * 
 * Email User Settings Managing for Dovecot IMAP attributes
 * 
 * @package Tinebase
 * @subpackage Ldap
 */
class Tinebase_EmailUser_Imap_Dovecot extends Tinebase_EmailUser_Abstract
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
     * quotas table name with prefix
     *
     * @var string
     */
    protected $_quotasTable = NULL;
    
    /**
     * email user config
     * 
     * @var array 
     */
    protected $_config = array(
        'prefix'            => 'dovecot_',
        'userTable'         => 'users',
        'quotaTable'        => 'quotas',
        'emailHome'			=> '/var/vmail/%d/%n',
        'emailUID'          => 'vmail', 
        'emailGID'          => 'vmail',
        'emailScheme'    	=> 'PLAIN-MD5',
        'domain'			=> null,
    );
    
    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'emailUserId'       => 'userid',
        'emailUsername'     => 'username',
        'emailPassword'     => 'password',
        'emailUID'          => 'uid', 
        'emailGID'          => 'gid', 
        'emailLastLogin'    => 'last_login',
        
        'emailMailQuota'    => 'mail_quota',
        'emailMailSize'     => 'mail_size',
        'emailSieveQuota'   => 'sieve_quota',
        'emailSieveSize'    => 'sieve_size',

        // makes mapping data to _config easier
        'emailHome'			=> 'home',
        'emailScheme'		=> 'scheme',
    );
    
    /**
     * Dovecot readonly
     * 
     * @var array
     */
    protected $_readOnlyFields = array(
        'emailMailQuota', // hack to fix updates
        'emailMailSize',
        'emailSieveQuota', // hack to fix updates
        'emailSieveSize',
        'emailLastLogin',
    );
    
    /**
     * the constructor
     */
    public function __construct()
    {
        // get dovecot imap config options (host, dbname, username, password, port)
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        
        // merge _config and dovecot imap
        $this->_config = array_merge($imapConfig['dovecot'], $this->_config);
        
        // _quotaTable = dovecot_aliases
        $this->_quotasTable = $this->_config['prefix'] . $this->_config['quotaTable'];
        
        // set domain from imap config
        $this->_config['domain'] = (isset($imapConfig['domain'])) ? $imapConfig['domain'] : '';
        
        // _tablename = "dovecot_users"
        $this->_tableName = $this->_config['prefix'] . $this->_config['userTable'];
        
        // connect to DB
        $this->_db = Zend_Db::factory('Pdo_Mysql', $this->_config);

        // copy over default scheme, home, UID, GID from preconfigured defaults
        $this->_config['emailScheme'] = $this->_config['scheme'];
        $this->_config['emailHome']   = $this->_config['home'];
        $this->_config['emailUID']    = $this->_config['uid'];
        $this->_config['emailGID']    = $this->_config['gid'];
    }
  
	/**
     * get user by id
     *
     * @param   string         $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    public function getUserById($_userId) 
    {
        $userId = $_userId instanceof Tinebase_Model_User ? $_userId->getId() : $_userId;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, true));
        
        /*
         * SELECT dovecot_users.*, dovecot_quotas.mail_quota, dovecot_quotas.mail_size, dovecot_quotas.sieve_quota, dovecot_quotas.sieve_size
         * FROM dovecot_users 
         * LEFT JOIN dovecot_quotas
         * ON (dovecot_users.username=dovecot_quotas.username)
         * WHERE dovecot_users.userid = $_userId
         * LIMIT 1
         */
        $select = $this->_db->select();
        $select->from(array($this->_tableName))
            ->where($this->_db->quoteIdentifier($this->_tableName . '.' . $this->_userPropertyNameMapping['emailUserId']) . ' = ?',   $userId)
            //->group($this->_tableName . '.' . $this->_userPropertyNameMapping['emailUserId'])

            // Left Join Quotas Table
            ->joinLeft(
                array($this->_quotasTable), // table
                '(' . $this->_db->quoteIdentifier($this->_tableName . '.' . $this->_userPropertyNameMapping['emailUsername']) .  ' = ' . // ON (left)
                    $this->_db->quoteIdentifier($this->_quotasTable . '.' . $this->_userPropertyNameMapping['emailUsername']) . ')', // ON (right)
                array( // Select
                    $this->_userPropertyNameMapping['emailMailQuota'] => $this->_quotasTable . '.' . $this->_userPropertyNameMapping['emailMailQuota'], // emailMailQuota
                    $this->_userPropertyNameMapping['emailMailSize'] => $this->_quotasTable . '.' . $this->_userPropertyNameMapping['emailMailSize'], // emailMailSize
                    $this->_userPropertyNameMapping['emailSieveQuota'] => $this->_quotasTable . '.' . $this->_userPropertyNameMapping['emailSieveQuota'], // emailSieveQuota
                    $this->_userPropertyNameMapping['emailSieveSize'] => $this->_quotasTable . '.' . $this->_userPropertyNameMapping['emailSieveSize'] // emailSieveSize
                ) 
            )
        
            // Only want 1 user (shouldn't be more than 1 anyway)
            ->limit(1);
        
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' = ?',   $this->_config['domain']);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' IS NULL');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        // Perferom query - retrieve user from database
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound('Dovecot config for user ' . $_userId . ' not found!');
        }
        
        // Print results of query if log level DEBUG
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));
        
        // convert data to Tinebase_Model_EmailUser       
        $emailUser = $this->_rawDataToRecord($queryResult);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUser, TRUE));
        // modify/correct user name
        // get Tine user
        $user = Tinebase_User::getInstance()->getFullUserById($_userId);
        // set emailUsername to Tine accout login name and append domain for login purposes if set
        $emailUser->emailUsername = $this->_appendDomain($user->accountLoginName);
        
        return $emailUser;
    }
    
    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     */
    protected function _addUser(Tinebase_Model_FullUser $_user, Tinebase_Model_EmailUser $_emailUser)
    {
        // 
        try {
            $this->_tineUserToEmailUser($_user, $_emailUser);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            return $_emailUser;
        }
        
        $recordArray = $this->_recordToRawData($_emailUser);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding new dovecot user ' . $_emailUser->emailUsername);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            $this->_db->insert($this->_tableName, $recordArray);
            
            // Add Quotas
            $this->_db->insert($this->_quotasTable, 
                array(
                    $this->_userPropertyNameMapping['emailUsername']   => $_emailUser->emailUsername,
                    $this->_userPropertyNameMapping['emailMailQuota']  => convertToBytes($_emailUser->emailMailQuota . 'M'), 
                    $this->_userPropertyNameMapping['emailSieveQuota'] => $_emailUser->emailSieveQuota, 
                )
            );
            
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
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
    public function addUser(Tinebase_Model_FullUser $_user, Tinebase_Model_EmailUser $_emailUser)
    {
        return $this->updateUser($_user, $_emailUser);
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
    public function updateUser(Tinebase_Model_FullUser $_user, Tinebase_Model_EmailUser $_emailUser)
    {
        if ($this->_userExists($_user) === true) {
            return $this->_updateUser($_user, $_emailUser);
        } else {
            return $this->_addUser($_user, $_emailUser);
        }
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
    protected function _updateUser(Tinebase_Model_FullUser $_user, Tinebase_Model_EmailUser $_emailUser)
    {
        try {
            $this->_tineUserToEmailUser($_user, $_emailUser);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            return $_emailUser;
        }
        
        $recordArray = $this->_recordToRawData($_emailUser);
        

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating Dovecot user ' . $_emailUser->emailUsername);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordArray, TRUE));
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userPropertyNameMapping['emailUserId']) . ' = ?', $_user->getId())
        );
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' = ?',   $this->_config['domain']);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' IS NULL';
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, TRUE));
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            $this->_db->update($this->_tableName, $recordArray, $where);

            // Update Quotas
            $this->_db->update($this->_quotasTable, 
                array( 
                    $this->_userPropertyNameMapping['emailMailQuota']  => convertToBytes($_emailUser->emailMailQuota . 'M'), 
                    $this->_userPropertyNameMapping['emailSieveQuota'] => $_emailUser->emailSieveQuota, 
                ),
                array(
                    $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userPropertyNameMapping['emailUsername']) . ' = ?', $_emailUser->emailUsername)
                )
            );
            
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
     * check if user exists already in dovecot user table
     * 
     * @param Tinebase_Model_FullUser $_user
     */
    protected function _userExists(Tinebase_Model_FullUser $_user)
    {
        $select = $this->_db->select();
        
        $select->from(array($this->_tableName))
          ->where($this->_db->quoteIdentifier($this->_tableName . '.' . $this->_userPropertyNameMapping['emailUserId']) . ' = ?',   $_user->getId())
        // Only want 1 user (shouldn't be more than 1 anyway)
          ->limit(1);
          
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' = ?',   $this->_config['domain']);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' IS NULL');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        // Perferom query - retrieve user from database
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            return false;
        }
        
        return true;
    }
    
    /**
     * update/set email user password
     * 
     * @param string $_userId
     * @param string $_password
     * @return void
     */
    public function setPassword($_userId, $_password)
    {
        $user = Tinebase_User::getInstance()->getFullUserById($_userId);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Set Dovecot password for user ' . $user->accountLoginName);
        
        $emailUser = $this->getUserById($_userId);
        $emailUser->emailPassword = $_password;
        
        return $this->updateUser($user, $emailUser);
    }
    
    /**
     * delete user by id
     *
     * @param   string         $_userId
     */
    public function deleteUser($_userId)
    {
        $userId = ($_userId instanceof Tinebase_Model_FullUser) ? $_userId->getId() : $_userId;
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete Dovecot settings for user ' . $userId);

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userPropertyNameMapping['emailUserId']) . ' = ?', $userId)
        );
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' = ?',   $this->_config['domain']);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_tableName . '.' . 'domain') . ' IS NULL';
        }
        
        $this->_db->delete($this->_tableName, $where);
    }
    
    /**
     * get new email user
     * 
     * @param Tinebase_Model_FullUser $_user
     * @return Tinebase_Model_EmailUser
     */
    public function getNewUser(Tinebase_Model_FullUser $_user)
    {
        
        $result = new Tinebase_Model_EmailUser(array(
            'emailUserId' 		=> $_user->getId(),
        ));
        
        return $result;
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
                    case 'emailMailQuota':
                    case 'emailMailSize':
                        // convert to megabytes
                        $data[$keyMapping] = round($value / 1024 / 1024);
                        break;
                    /* 
                     * emailHome, emailScheme, emailUID, emailGID are currently broken
                     * home and scheme are understandable, uid and gid not so much
                     * the admin page does not save and return the uid and gid
                     * need to look to see if there are any ways to extend 
                     * the admin page dynamicly
                     * 
                    // set home from preconfigured home if not already set
                    case 'emailHome':
                    	if (!empty($value)){
                    		$this->_config[$keyMapping] = $value;
                    	} else {
                    		$this->_config[$keyMapping] = $this->_config['home'];
                    	}
                    	break;
                    // set scheme from preconfigured scheme if not already set
                    case 'emailScheme':
                    	if (!empty($value)){
                    		$this->_config[$keyMapping] = $value;
                    	} else {
                    		$this->_config[$keyMapping] = $this->_config['scheme'];
                    	}
                    	break;
                    // set uid from preconfigured uid if not already set
                    case 'emailUID':
                    	if (!empty($value)){
                    		$data[$keyMapping] = $value;
                    	} else {
                    		$data[$keyMapping] = $this->_config['uid'];
                    	}
                    	break;
                    // set gid from preconfigured gid if not already set
                    case 'emailGID':
                    	if (!empty($value)){
                    		$data[$keyMapping] = $value;
                    	} else {
                    		$data[$keyMapping] = $this->_config['gid'];
                    	}
                    	break;
                    */
                    default: 
                        $data[$keyMapping] = $value;
                        break;
                }
            }
        }
        
        return new Tinebase_Model_EmailUser($data, true);
    }
     
    /**
     * returns array of raw Dovecot data
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
                    $data[$property] = $this->_generatePassword($value, $this->_config['emailScheme']);
                        break;
                    default:
                        $data[$property] = $value;
                        break;
                }
            }
        }
        
        list($user, $domain) = explode('@', $_user->emailUsername, 2);
        //$data['user']   = $user;
        $data['domain'] = $domain;
        
        $property = $this->_userPropertyNameMapping['emailScheme'];
        $data[$property] = $this->_config['emailScheme'];
        
        // replace home wildcards when storing to db
        // %d = domain
        // %n = user
        // %u == user@domain
        $property = $this->_userPropertyNameMapping['emailHome'];
        $search = array('%n', '%d', '%u');
        $replace = explode('@', $data[$this->_userPropertyNameMapping['emailUsername']]);
        $replace[] = $this->_userPropertyNameMapping['emailUsername'];
        $data[$property] = str_replace($search, $replace, $this->_config['emailHome']);
        
        return $data;
    }
    
    /**
     * convert tine user to email user
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_EmailUser $_emailUser
     * @return Tinebase_Model_EmailUser $_emailUser
     * @throws Tinebase_Exception_UnexpectedValue
     * 
     */
    protected function _tineUserToEmailUser(Tinebase_Model_FullUser $_user, Tinebase_Model_EmailUser $_emailUser)
    {
        // tine20 user ID
        $_emailUser->emailUserId = $_user->getId();
        // set emailUsername to Tine accout login name and append domain for login purposes if set
        $_emailUser->emailUsername = $this->_appendDomain($_user->accountLoginName);
        
        
        // set GID/UID to configured UID/GID if not already set in EmailUser
        if (empty($_emailUser->emailUID)) {
            $_emailUser->emailUID = $this->_config['uid'];
        }
        
        if (empty($_emailUser->emailGID)) {
            $_emailUser->emailGID = $this->_config['gid'];
        }
    }
    
    /**
     * Check if we should append domain name or not
     *
     * @param  string $_userName
     * @return string
     */
    protected function _appendDomain($_userName)
    {
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $_userName .= '@' . $this->_config['domain'];
        }
        
        return $_userName;
    }
    
    /**
     * generate password based on password scheme
     * 
     * @param string $_password
     * @param string $_scheme
     * @return string
     */
    protected function _generatePassword($_password, $_scheme)
    {
        $password = null;
        switch ($_scheme) {
            case 'MD5-CRYPT':
            case 'SSHA256':
            case 'SSHA512':
            	$password = crypt($_password, $this->_salt($_scheme));
            	break;
            
            case 'SHA256':
            case 'SHA512':
            	$password = hash($_scheme, $_password);
            	break;
            
            case 'SHA':
            	$password = sha1($_password);
            	break;
            
            case 'PLAIN':
            	$password = $_password;
            	break;
            
            case 'PLAIN-MD5':
            default:
            	$password = md5($_password);
            	break;
        }

        return $password;
    }
    
    /**
     * generate salt for password scheme
     * 
     * @param $_scheme
     * @return string
     */
    protected function _salt($_scheme)
    {
        $salt = null;
        
        // create a salt that ensures crypt creates an sha2 hash
        $base64_alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            .'abcdefghijklmnopqrstuvwxyz0123456789+/';
        
        for($i=0; $i<16; $i++){
            $salt.=$base64_alphabet[rand(0,63)];
        }
        
        switch ($_scheme)
        {
            case 'SSHA256':
            	$salt = '$5$' . $salt . '$';
            	break;
            	
            case 'SSHA512':
            	$salt = '$6$' . $salt . '$';
            	break;
            	
            case 'MD5-CRYPT':
            default:
            	$salt = crypt($_scheme);
            	break;
        }

        return $salt;
    }
}