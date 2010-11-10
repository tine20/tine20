<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
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
`domain` VARCHAR( 80 ) DEFAULT '',
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
 * plugin to handle dovecot imap accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Imap_Dovecot extends Tinebase_User_Plugin_Abstract
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
    protected $_userTable = NULL;
    
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
    protected $_propertyMapping = array(
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
        'emailMailSize',
        'emailSieveSize',
        'emailLastLogin',
    );
    
    /**
     * the constructor
     */
    public function __construct(array $_options = array())
    {
        // get dovecot imap config options (host, dbname, username, password, port)
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        
        // merge _config and dovecot imap
        $this->_config = array_merge($imapConfig['dovecot'], $this->_config);
        
        // set domain from imap config
        $this->_config['domain'] = !empty($imapConfig['domain']) ? $imapConfig['domain'] : null;
        
        // _tablename = "dovecot_users"
        $this->_userTable = $this->_config['prefix'] . $this->_config['userTable'];
        
        // _quotaTable = dovecot_aliases
        $this->_quotasTable = $this->_config['prefix'] . $this->_config['quotaTable'];
        
        // connect to DB
        $this->_getDB($this->_config);

        // copy over default scheme, home, UID, GID from preconfigured defaults
        $this->_config['emailScheme'] = $this->_config['scheme'];
        $this->_config['emailHome']   = $this->_config['home'];
        $this->_config['emailUID']    = $this->_config['uid'];
        $this->_config['emailGID']    = $this->_config['gid'];
    }
    
    /**
     * delete user by id
     *
     * @param  string  $_userId
     */
    public function inspectDeleteUser($_userId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete Dovecot settings for user ' . $_userId);

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_userId)
        );
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' = ?',   $this->_config['domain']);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' IS NULL';
        }
        
        $this->_db->delete($this->_userTable, $where);
    }
    
    /**
     * inspect get user by property
     * 
     * @param Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user)
    {
        if (! $_user instanceof Tinebase_Model_FullUser) {
            return;
        }
        
        $userId = $_user->getId();
        
        $select = $this->_getSelect()
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']) . ' = ?',   $userId);
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        // Perferom query - retrieve user from database
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'Dovecot config for user ' . $_userId . ' not found!');
            return;
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));
        
        // convert data to Tinebase_Model_EmailUser       
        $emailUser = $this->_rawDataToRecord($queryResult);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUser->toArray(), TRUE));
        
        // modify/correct user name
        // set emailUsername to Tine accout login name and append domain for login purposes if set
        $emailUser->emailUsername = $this->_appendDomain($_user->accountLoginName);

        $_user->imapUser  = $emailUser;
        $_user->emailUser = Tinebase_EmailUser::merge($_user->imapUser, isset($_user->emailUser) ? $_user->emailUser : null);
    }
    
    /**
     * update/set email user password
     * 
     * @param  string  $_userId
     * @param  string  $_password
     */
    public function inspectSetPassword($_userId, $_password)
    {
        $values = array(
            $this->_propertyMapping['emailScheme']   => $this->_config['emailScheme'],
            $this->_propertyMapping['emailPassword'] => $this->_generatePassword($_password, $this->_config['emailScheme'])
        );
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_userId)
        );
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.domain') . ' = ?',   $this->_config['domain']);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_userTable . '.domain') . ' IS NULL';
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($values, TRUE));
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, TRUE));
        
        $this->_db->update($this->_userTable, $values, $where);
    }
    
    /**
     * get new email user
     * 
     * @param  Tinebase_Model_FullUser   $_user
     * @return Tinebase_Model_EmailUser
     */
    public function getNewUser(Tinebase_Model_FullUser $_user)
    {
        
        $result = new Tinebase_Model_EmailUser(array(
            'emailUserId' 		=> $_user->getId(),
        ));
        
        return $result;
    }
    
    /*********  protected functions  *********/
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param  array|string|Zend_Db_Expr  $_cols        columns to get, * per default
     * @param  boolean                    $_getDeleted  get deleted records (if modlog is active)
     * @return Zend_Db_Select
     * 
     * SELECT dovecot_users.*, dovecot_quotas.mail_quota, dovecot_quotas.mail_size, dovecot_quotas.sieve_quota, dovecot_quotas.sieve_size
     * FROM dovecot_users 
     * LEFT JOIN dovecot_quotas
     * ON (dovecot_users.username=dovecot_quotas.username)
     * WHERE dovecot_users.userid = $_userId
     * LIMIT 1
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {        
        $select = $this->_db->select()
        
            ->from(array($this->_userTable))

            // Left Join Quotas Table
            ->joinLeft(
                array($this->_quotasTable), // table
                '(' . $this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUsername']) .  ' = ' . // ON (left)
                    $this->_db->quoteIdentifier($this->_quotasTable . '.' . $this->_propertyMapping['emailUsername']) . ')', // ON (right)
                array( // Select
                    $this->_propertyMapping['emailMailQuota'] => $this->_quotasTable . '.' . $this->_propertyMapping['emailMailQuota'], // emailMailQuota
                    $this->_propertyMapping['emailMailSize'] => $this->_quotasTable . '.' . $this->_propertyMapping['emailMailSize'], // emailMailSize
                    $this->_propertyMapping['emailSieveQuota'] => $this->_quotasTable . '.' . $this->_propertyMapping['emailSieveQuota'], // emailSieveQuota
                    $this->_propertyMapping['emailSieveSize'] => $this->_quotasTable . '.' . $this->_propertyMapping['emailSieveSize'] // emailSieveSize
                ) 
            )            
            
            // Only want 1 user (shouldn't be more than 1 anyway)
            ->limit(1);
        
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' = ?',   $this->_config['domain']);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' IS NULL');
        }
        
        return $select;
    }
    
    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser  $_addedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _addUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        $imapSettings = $this->_recordToRawData($_addedUser, $_newUserProperties);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding new dovecot user ' . $imapSettings[$this->_propertyMapping['emailUsername']]);
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($imapSettings, TRUE));

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            $values = $imapSettings;
            unset($values[$this->_propertyMapping['emailMailQuota']]);
            unset($values[$this->_propertyMapping['emailSieveQuota']]);
            
            $this->_db->insert($this->_userTable, $values);
            
            // Add Quotas
            $this->_db->insert($this->_quotasTable, 
                array(
                    $this->_propertyMapping['emailUsername']   => $imapSettings[$this->_propertyMapping['emailUsername']],
                    $this->_propertyMapping['emailMailQuota']  => convertToBytes($imapSettings[$this->_propertyMapping['emailMailQuota']] . 'M'), 
                    $this->_propertyMapping['emailSieveQuota'] => $imapSettings[$this->_propertyMapping['emailSieveQuota']]
                )
            );
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->inspectGetUserByProperty($_addedUser);
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while creating email user: ' . $zdse->getMessage());
        }
	}
	
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        $imapSettings = $this->_recordToRawData($_updatedUser, $_newUserProperties);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))  Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating Dovecot user ' . $imapSettings[$this->_propertyMapping['emailUsername']]);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($imapSettings, TRUE));
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $imapSettings[$this->_propertyMapping['emailUserId']])
        );
        // append domain if set or domain IS NULL
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.domain') . ' = ?',   $this->_config['domain']);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_userTable . '.domain') . ' IS NULL';
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, TRUE));
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            $values = $imapSettings;
            unset($values[$this->_propertyMapping['emailMailQuota']]);
            unset($values[$this->_propertyMapping['emailSieveQuota']]);
            
            $this->_db->update($this->_userTable, $values, $where);

            // Update Quotas
            $this->_db->update($this->_quotasTable, 
                array( 
                    $this->_propertyMapping['emailMailQuota']  => convertToBytes($imapSettings[$this->_propertyMapping['emailMailQuota']] . 'M'), 
                    $this->_propertyMapping['emailSieveQuota'] => $imapSettings[$this->_propertyMapping['emailSieveQuota']]
                ),
                array(
                    $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUsername']) . ' = ?', $imapSettings[$this->_propertyMapping['emailUsername']])
                )
            );
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            $this->inspectGetUserByProperty($_updatedUser);
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while updating email user: ' . $zdse->getMessage());
        }            
    }
    
    /**
     * check if user exists already in dovecot user table
     * 
     * @param  Tinebase_Model_FullUser  $_user
     */
    protected function _userExists(Tinebase_Model_FullUser $_user)
    {
        $select = $this->_getSelect();
        
        $select
          ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']) . ' = ?',   $_user->getId());
          
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

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
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array                    $_data
     * @return Tinebase_Model_EmailUser
     */
    protected function _rawDataToRecord(array $_rawdata)
    {
        $data = array();
        
        foreach ($_rawdata as $key => $value) {
            $keyMapping = array_search($key, $this->_propertyMapping);
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
     * @param  Tinebase_Model_FullUser  $_user
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     * @return array
     */
    protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties)
    {
        $rawData = array();
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_newUserProperties->imapUser->toArray(), true));
        
        foreach ($_newUserProperties->imapUser as $key => $value) {
            $property = array_key_exists($key, $this->_propertyMapping) ? $this->_propertyMapping[$key] : false;
            if ($property && ! in_array($key, $this->_readOnlyFields)) {
                switch ($key) {
                    case 'emailPassword':
                        $rawData[$property] = $this->_generatePassword($value, $this->_config['emailScheme']);
                        break;
                        
                    case 'emailUserId':
                        $rawData[$property] = $_user->getId();
                        break;
                        
                    case 'emailUID':
                        $rawData[$property] = !empty($this->_config['uid']) ? $this->_config['uid'] : $value;
                        break;
                        
                    case 'emailGID':
                        $rawData[$property] = !empty($this->_config['gid']) ? $this->_config['gid'] : $value;
                        break;
                        
                    case 'emailUsername':
                        $rawData[$property] = $this->_appendDomain($_user->accountLoginName);
                        break;
                        
                    default:
                        $rawData[$property] = $value;
                        break;
                }
            }
        }

        list($user, $domain) = explode('@', $rawData[$this->_propertyMapping['emailUsername']], 2);
        //$rawData['user']   = $user;
        $rawData['domain'] = $domain;
        
        $rawData[$this->_propertyMapping['emailScheme']] = $this->_config['emailScheme'];
        
        // replace home wildcards when storing to db
        // %d = domain
        // %n = user
        // %u == user@domain
        $property = $this->_propertyMapping['emailHome'];
        $search = array('%n', '%d', '%u');
        $replace = explode('@', $rawData[$this->_propertyMapping['emailUsername']]);
        $replace[] = $this->_propertyMapping['emailUsername'];
        $rawData[$property] = str_replace($search, $replace, $this->_config['emailHome']);
        
        return $rawData;
    }
}