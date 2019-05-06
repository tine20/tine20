<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Fronk
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
`userid` varchar(80) NOT NULL,
`domain` varchar(80) NOT NULL DEFAULT '',
`username` varchar(80) NOT NULL,
`loginname` varchar(255) DEFAULT NULL,
`password` varchar(100) NOT NULL,
`quota_bytes` bigint(20) NOT NULL DEFAULT '2000',
`quota_message` int(11) NOT NULL DEFAULT '0',
`quota_sieve_bytes` bigint(20) NOT NULL DEFAULT '0',
`quota_sieve_script` int(11) NOT NULL DEFAULT '0',
`uid` varchar(20) DEFAULT NULL,
`gid` varchar(20) DEFAULT NULL,
`home` varchar(256) DEFAULT NULL,
`last_login` datetime DEFAULT NULL,
`last_login_unix` int(11) DEFAULT NULL,
`instancename` varchar(40) DEFAULT NULL,
PRIMARY KEY (`userid`,`domain`),
UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- --------------------------------------------------------

--
-- Table structure for table `dovecot_usage`
--

CREATE TABLE IF NOT EXISTS `dovecot_usage` (
`username` VARCHAR( 80 ) NOT NULL ,
`storage`  BIGINT NOT NULL DEFAULT '0',
`messages` BIGINT NOT NULL DEFAULT '0',
PRIMARY KEY (`username`),
CONSTRAINT `dovecot_usage::username--dovecot_users::username` FOREIGN KEY (`username`) REFERENCES `dovecot_users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE
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
    CONCAT('*:bytes=', CAST(quota_bytes AS CHAR), 'M') AS userdb_quota_rule   
    FROM dovecot_users 
    WHERE dovecot_users.username='%u'

# userdb for deliver
user_query = SELECT home, uid, gid, 
    CONCAT('*:bytes=', CAST(quota_bytes AS CHAR), 'M') AS userdb_quota_rule   
    FROM dovecot_users 
    WHERE dovecot_users.username='%u'
-- --------------------------------------------------------

-- 
-- Quotas Config: dovecot-dict-sql.conf
--
-- Note: Currently Tine Sieve Quota is used as Message Quota
--

connect = host=127.0.0.1 dbname=DovecotDB user=DovecotUser password=DovecotPass

map {
  pattern = priv/quota/storage
  table = dovecot_usage
  username_field = username
  value_field = storage
}

map {
  pattern = priv/quota/messages
  table = dovecot_usage
  username_field = username
  value_field = messages
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
--         but you can use the dovecot server for multiple 
--         sites. So in other words pertine storage

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
class Tinebase_EmailUser_Imap_Dovecot extends Tinebase_EmailUser_Sql implements Tinebase_EmailUser_Imap_Interface
{
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
        'quotaTable'        => 'usage',
        'emailHome'         => '/var/vmail/%d/%n',
        'emailUID'          => 'vmail', 
        'emailGID'          => 'vmail',
        'emailScheme'       => 'SSHA256',
        'domain'            => null,
        'adapter'           => Tinebase_Core::PDO_MYSQL
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
        'emailMailQuota'    => 'quota_bytes',
        #'emailSieveQuota'   => 'quota_message',
    
        'emailMailSize'     => 'storage',
        'emailSieveSize'    => 'messages',

        // makes mapping data to _config easier
        'emailHome'            => 'home'
    );

    protected $_tableMapping = array(
        'emailUserId'       => 'USERTABLE',
        'emailUsername'     => 'USERTABLE',
        'emailPassword'     => 'USERTABLE',
        'emailUID'          => 'USERTABLE',
        'emailGID'          => 'USERTABLE',
        'emailLastLogin'    => 'USERTABLE',
        'emailMailQuota'    => 'USERTABLE',
        #'emailSieveQuota'   => 'USERTABLE',

        'emailMailSize'     => 'QUOTATABLE',
        'emailSieveSize'    => 'QUOTATABLE',

        // makes mapping data to _config easier
        'emailHome'            => 'USERTABLE'
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
    
    protected $_defaults = array(
        'emailPort'   => 143,
        'emailSecure' => Tinebase_EmailUser_Model_Account::SECURE_TLS
    );
    
    /**
     * subconfig for user email backend (for example: dovecot)
     * 
     * @var string
     */
    protected $_subconfigKey = 'dovecot';
    
    /**
     * the constructor
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        // _quotaTable = dovecot_aliases
        $this->_quotasTable = $this->_config['prefix'] . $this->_config['quotaTable'];

        $this->_replaceValue($this->_tableMapping, array(
            'USERTABLE' => $this->_userTable,
            'QUOTATABLE' => $this->_quotasTable
        ));
        
        // set domain from imap config
        $this->_config['domain'] = !empty($this->_config['domain']) ? $this->_config['domain'] : null;
        
        // copy over default scheme, home, UID, GID from preconfigured defaults
        $this->_config['emailScheme'] = $this->_config['scheme'];
        $this->_config['emailHome']   = $this->_config['home'];
        $this->_config['emailUID']    = $this->_config['uid'];
        $this->_config['emailGID']    = $this->_config['gid'];
    }
    
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
            ->from(array($this->_userTable), $_cols)
            // Left Join Quotas Table
            ->joinLeft(
                array($this->_quotasTable), // table
                '(' . $this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUsername']) .  ' = ' . // ON (left)
                    $this->_db->quoteIdentifier($this->_quotasTable . '.' . $this->_propertyMapping['emailUsername']) . ')', // ON (right)
                '*' === $_cols ? array( // Select
                    $this->_propertyMapping['emailMailSize']  => $this->_quotasTable . '.' . $this->_propertyMapping['emailMailSize'], // emailMailSize
                    $this->_propertyMapping['emailSieveSize'] => $this->_quotasTable . '.' . $this->_propertyMapping['emailSieveSize'] // emailSieveSize
                ) : array()
            )
            // Only want 1 user (shouldn't be more than 1 anyway)
            ->limit(1);

        $this->_appendInstanceNameOrDomainToSelect($select);

        return $select;
    }

    protected function _appendInstanceNameOrDomainToSelect(Zend_Db_Select $select)
    {
        $schema = $this->getSchema();
        // append instancename OR domain if set or domain IS NULL
        if (isset($this->_config['instanceName'])
            && ! empty($this->_config['instanceName'])
            && array_key_exists('instancename', $schema))
        {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . 'instancename') . ' = ?',
                $this->_config['instanceName']);
        } else if (isset($this->_config['domain']) && ! empty($this->_config['domain'])) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' = ?',
                $this->_config['domain']);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . " = ''");
        }
    }
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array $_rawdata
     * @return Tinebase_Model_EmailUser
     */
    protected function _rawDataToRecord(array $_rawdata)
    {
        $data = array_merge($this->_defaults, $this->_getConfiguredSystemDefaults());
        
        foreach ($_rawdata as $key => $value) {
            $keyMapping = array_search($key, $this->_propertyMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'emailPassword':
                    case 'emailAliases':
                    case 'emailForwards':
                    case 'emailForwardOnly':
                    case 'emailAddress':
                        // do nothing
                        break;
                    case 'emailMailQuota':
                    case 'emailSieveQuota':
                        $data[$keyMapping] = $value > 0 ? $value * 1024 * 1024 : null;
                        break;
                    case 'emailMailSize':
                        $data[$keyMapping] = $value > 0 ? $value : 0;
                        break;
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
        
        if (isset($_newUserProperties->imapUser)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                __METHOD__ . '::' . __LINE__ . ' ' . print_r($_newUserProperties->imapUser->toArray(), true));
            
            foreach ($_newUserProperties->imapUser as $key => $value) {
                $property = (isset($this->_propertyMapping[$key]) || array_key_exists($key, $this->_propertyMapping)) ? $this->_propertyMapping[$key] : false;
                if ($property && ! in_array($key, $this->_readOnlyFields)) {
                    switch ($key) {
                        case 'emailUserId':
                        case 'emailUsername':
                            // do nothing
                            break;
                            
                        case 'emailPassword':
                            $rawData[$property] = Hash_Password::generate($this->_config['emailScheme'], $value);
                            break;
                            
                        case 'emailUID':
                            $rawData[$property] = !empty($this->_config['uid']) ? $this->_config['uid'] : $value;
                            break;
                            
                        case 'emailGID':
                            $rawData[$property] = !empty($this->_config['gid']) ? $this->_config['gid'] : $value;
                            break;
                        case 'emailMailQuota':
                            $rawData[$property] = (empty($value) || $value < (1024 * 1024)) ? 0 :
                                (int)($value / (1024 * 1024));
                            break;
                            
                        default:
                            $rawData[$property] = $value;
                            break;
                    }
                }
            }
        }
        
        foreach (array('uid', 'gid') as $key) {
            if (! (isset($rawData[$key]) || array_key_exists($key, $rawData))) {
                $rawData[$key] = $this->_config[$key];
            }
        }
        
        $rawData[$this->_propertyMapping['emailUserId']]   = $_user->getId();

        $emailUsername = $this->_getEmailUserName($_user, $_newUserProperties->accountEmailAddress);

        list($localPart, $usernamedomain) = explode('@', $emailUsername, 2);
        $domain = empty($this->_config['domain']) ? $usernamedomain : $this->_config['domain'];

        if (isset($this->_config['instanceName'])) {
            $rawData['instancename'] = $this->_config['instanceName'];
        }

        $rawData['domain'] = $domain;

        // replace home wildcards when storing to db
        // %d = domain
        // %n = user
        // %u == user@domain
        $search = array('%n', '%d', '%u');
        $replace = array(
            $localPart,
            $domain,
            $emailUsername
        );
        
        $rawData[$this->_propertyMapping['emailHome']] = str_replace($search, $replace, $this->_config['emailHome']);
        $rawData[$this->_propertyMapping['emailUsername']] = $emailUsername;

        // set primary email address as optional login name
        $rawData['loginname'] = $_user->accountEmailAddress;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . ' ' . print_r($rawData, true));

        return $rawData;
    }

    /**
     * @return array
     */
    public function getAllDomains()
    {
        $select = $this->_db->select()->from(array($this->_userTable), 'domain')->distinct();
        $this->_appendInstanceNameOrDomainToSelect($select);

        $result = $select->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0);
        return $result;
    }
}
