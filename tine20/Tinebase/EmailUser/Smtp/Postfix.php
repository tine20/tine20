<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
`passwd` varchar(100) NOT NULL,
`quota` int(10) DEFAULT '10485760',
`userid` varchar(80) NOT NULL,
`encryption_type` varchar(20) NOT NULL DEFAULT 'md5',
`client_idnr` varchar(40) NOT NULL,
`forward_only` tinyint(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`userid`,`client_idnr`),
UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `smtp_destinations`
--

CREATE TABLE IF NOT EXISTS `smtp_destinations` (
`userid` varchar(80) NOT NULL,
`source` varchar(80) NOT NULL,
`destination` varchar(80) NOT NULL,
KEY `smtp_destinations::userid--smtp_users::userid` (`userid`),
CONSTRAINT `smtp_destinations::userid--smtp_users::userid` FOREIGN KEY (`userid`) REFERENCES `smtp_users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Postfix virtual_mailbox_domains: sql-virtual_mailbox_domains.cf
--

user     = smtpUser
password = smtpPass
hosts    = 127.0.0.1
dbname   = smtp
query    = SELECT DISTINCT 1 FROM smtp_destinations WHERE SUBSTRING_INDEX(source, '@', -1) = '%s';
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
query = SELECT destination FROM smtp_destinations WHERE source='%s'

-- -----------------------------------------------------
 */

/**
 * plugin to handle postfix smtp accounts
 *
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_Postfix extends Tinebase_EmailUser_Sql implements Tinebase_EmailUser_Smtp_Interface
{
    /**
     * destination table name with prefix
     *
     * @var string
     */
    protected $_destinationTable = NULL;
    
    /**
     * subconfig for user email backend (for example: dovecot)
     * 
     * @var string
     */
    protected $_subconfigKey = 'postfix';

    /**
     * postfix config
     * 
     * @var array 
     */
    protected $_config = array(
        'prefix'            => 'smtp_',
        'userTable'         => 'users',
        'destinationTable'  => 'destinations',
        'emailScheme'       => 'ssha256',
        'domain'            => null,
        'alloweddomains'    => array(),
        'adapter'           => Tinebase_Core::PDO_MYSQL
    );
    
    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailPassword'     => 'passwd', 
        'emailUserId'       => 'userid',
        'emailAddress'      => 'email',
        'emailForwardOnly'  => 'forward_only',
        'emailUsername'     => 'username',
        'emailAliases'      => 'source',
        'emailForwards'     => 'destination'
    );
    
    protected $_defaults = array(
        'emailPort'   => 25,
        'emailSecure' => Tinebase_EmailUser_Model_Account::SECURE_TLS,
        'emailAuth'   => 'plain'
    );
    
    /**
     * the constructor
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        // set domain and allowed domains from smtp config
        $this->_config['domain'] = !empty($this->_config['primarydomain']) ? $this->_config['primarydomain'] : null;
        $this->_config['alloweddomains'] = Tinebase_EmailUser::getAllowedDomains($this->_config);

        $this->_clientId = Tinebase_Core::getTinebaseId();
        
        $this->_destinationTable = $this->_config['prefix'] . $this->_config['destinationTable'];

        $this->_supportAliasesDispatchFlag = true;
    }
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param  array|string|Zend_Db_Expr  $_cols        columns to get, * per default
     * @param  boolean                    $_getDeleted  get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        // _userTable.emailUserId=_destinationTable.emailUserId
        $userIDMap    = $this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']);
        $userEmailMap = $this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailAddress']);
        
        $select = $this->_db->select()
            ->from($this->_userTable)
            ->group($this->_userTable . '.userid')
            // Only want 1 user (shouldn't be more than 1 anyway)
            ->limit(1);
            
        // select source from alias table
        $select->joinLeft(
            array('aliases' => $this->_destinationTable), // Table
            '(' . $userIDMap .  ' = ' .  // ON (left)
            $this->_db->quoteIdentifier('aliases.' . $this->_propertyMapping['emailUserId']) . // ON (right)
            ' AND ' . $userEmailMap . ' = ' . // AND ON (left)
            $this->_db->quoteIdentifier('aliases.' . $this->_propertyMapping['emailForwards']) . ')', // AND ON (right)
            array($this->_propertyMapping['emailAliases'] => $this->_dbCommand->getAggregate('aliases.' . $this->_propertyMapping['emailAliases']))); // Select
        
        // select destination from alias table
        $select->joinLeft(
            array('forwards' => $this->_destinationTable), // Table
            '(' . $userIDMap .  ' = ' . // ON (left)
            $this->_db->quoteIdentifier('forwards.' . $this->_propertyMapping['emailUserId']) . // ON (right)
            ' AND ' . $userEmailMap . ' = ' . // AND ON (left)
            $this->_db->quoteIdentifier('forwards.' . $this->_propertyMapping['emailAliases']) . ')', // AND ON (right)
            array($this->_propertyMapping['emailForwards'] => $this->_dbCommand->getAggregate('forwards.' . $this->_propertyMapping['emailForwards']))); // Select

        $this->_appendDomainOrClientIdOrInstanceToSelect($select);

        return $select;
    }

    protected function _appendDomainOrClientIdOrInstanceToSelect($select)
    {
        if (! empty($this->_clientId)) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' = ?', $this->_clientId);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' IS NULL');
        }
    }

    /**
    * interceptor before add
    *
    * @param array $emailUserData
    */
    protected function _beforeAddOrUpdate(&$emailUserData)
    {
        $this->_deleteOldUserDataIfExists($emailUserData);
        unset($emailUserData[$this->_propertyMapping['emailForwards']]);
        unset($emailUserData[$this->_propertyMapping['emailAliases']]);
    }

    /**
     * delete old email user data
     *
     * @param array $emailUserData
     * @throws Tinebase_Exception_Backend_Database
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _deleteOldUserDataIfExists($emailUserData)
    {
        $select = $this->_getSelect();
        $select
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId'])  . ' != ?',   $emailUserData['userid'])
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailAddress']) . ' = ?',   $emailUserData['email']);

        try {
            $stmt = $this->_db->query($select);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $zdse);
            throw new Tinebase_Exception_Backend_Database($zdse->getMessage());
        }
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();

        if ($queryResult) {
            // check if user is still valid
            try {
                Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $queryResult['userid']);
                throw new Tinebase_Exception_SystemGeneric('could not overwrite email data of user ' . $queryResult['userid']);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                        . __LINE__ . ' Removing old email data of userid ' . $queryResult['userid']);
                }
                $this->deleteUserById($queryResult['userid']);
                $this->_removeDestinations($queryResult);
            }
        }
    }
    
    /**
    * interceptor after add
    *
    * @param array $emailUserData
    */
    protected function _afterAddOrUpdate(&$emailUserData)
    {
        $this->_setAliasesAndForwards($emailUserData);
    }
    
    /**
     * set email aliases and forwards
     * 
     * removes all aliases for user
     * creates default email->email alias if not forward only
     * creates aliases
     * creates forwards
     * 
     * @param  array  $_smtpSettings  as returned from _recordToRawData
     * @return void
     */
    protected function _setAliasesAndForwards($_smtpSettings)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Setting default alias/forward for ' . print_r($_smtpSettings, true));
        
        $this->_removeDestinations($_smtpSettings);
        
        // check if it should be forward only
        if (! $_smtpSettings[$this->_propertyMapping['emailForwardOnly']]) {
            $this->_createDefaultDestinations($_smtpSettings);
        }
        
        $this->_createAliasDestinations($_smtpSettings);
        $this->_createForwardDestinations($_smtpSettings);
    }
    
    /**
     * remove all current aliases and forwards for user
     * 
     * @param array $user
     */
    protected function _removeDestinations($user)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?',
                $user[$this->_propertyMapping['emailUserId']])
        );
        
        $this->_db->delete($this->_destinationTable, $where);
    }
    
    /**
     * create default destinations
     * 
     * @param array $_smtpSettings
     */
    protected function _createDefaultDestinations($_smtpSettings)
    {
        // create email -> username alias
        $this->_addDestination(array(
            'userid'        => $_smtpSettings[$this->_propertyMapping['emailUserId']],   // userID
            'source'        => $_smtpSettings[$this->_propertyMapping['emailAddress']],  // TineEmail
            'destination'   => $_smtpSettings[$this->_propertyMapping['emailUsername']], // email
        ));
        
        // create username -> username alias if email and username are different
        if ($_smtpSettings[$this->_propertyMapping['emailUsername']] != $_smtpSettings[$this->_propertyMapping['emailAddress']]) {
            $this->_addDestination(array(
                'userid'      => $_smtpSettings[$this->_propertyMapping['emailUserId']],   // userID
                'source'      => $_smtpSettings[$this->_propertyMapping['emailUsername']], // username
                'destination' => $_smtpSettings[$this->_propertyMapping['emailUsername']], // username
            ));
        }
    }
    
    /**
     * add destination
     * 
     * @param array $destinationData
     */
    protected function _addDestination($destinationData)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Insert into table destinations: ' . print_r($destinationData, true));

        $schema = Tinebase_Db_Table::getTableDescriptionFromCache($this->_destinationTable, $this->_db);
        $destinationData = array_intersect_key($destinationData, $schema);
        if (isset($destinationData['dispatch_address'])) {
            $destinationData['dispatch_address'] = (int)$destinationData['dispatch_address'];
        }

        $this->_db->insert($this->_destinationTable, $destinationData);
    }
    
    /**
     * set aliases
     * 
     * @param array $_smtpSettings
     * @param string $userIdField
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _createAliasDestinations($_smtpSettings, $userIdField = 'userid')
    {
        if (! ((isset($_smtpSettings[$this->_propertyMapping['emailAliases']]) || array_key_exists($this->_propertyMapping['emailAliases'], $_smtpSettings)) && is_array($_smtpSettings[$this->_propertyMapping['emailAliases']]))) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Setting aliases for '
            . $_smtpSettings[$this->_propertyMapping['emailUsername']] . ': ' . print_r($_smtpSettings[$this->_propertyMapping['emailAliases']], TRUE));

        if ($userIdField === 'userid') {
            $userId = $_smtpSettings[$this->_propertyMapping['emailUserId']];
        } else {
            $userId = $_smtpSettings['id'];
        }
            
        foreach ($_smtpSettings[$this->_propertyMapping['emailAliases']] as $aliasAddress) {
            // backward compatibility
            if (! is_array($aliasAddress)) {
                $aliasAddress = [
                    'email' => $aliasAddress,
                    'dispatch_address' => 1,
                ];
            }
            if ($aliasAddress['email'] === $_smtpSettings['email']) {
                throw new Tinebase_Exception_SystemGeneric('It is not allowed to set an alias equal to the main email address');
            }

            // check if in primary or secondary domains
            if (! empty($aliasAddress['email']) && $this->_checkDomain($aliasAddress['email'])) {
                if (! $_smtpSettings[$this->_propertyMapping['emailForwardOnly']]) {
                    // create alias -> email
                    $this->_addDestination(array(
                        $userIdField  => $userId,
                        'source'      => $aliasAddress['email'],
                        'destination' => $_smtpSettings[$this->_propertyMapping['emailAddress']],
                        'dispatch_address' => $aliasAddress['dispatch_address'],
                    ));
                } else if ($this->_hasForwards($_smtpSettings)) {
                    $this->_addForwards($userId, $aliasAddress, $_smtpSettings[$this->_propertyMapping['emailForwards']]);
                }
            }
        }
    }
    
    /**
     * check if forward addresses exist
     * 
     * @param array $_smtpSettings
     * @return boolean
     */
    protected function _hasForwards($_smtpSettings)
    {
        return ((isset($_smtpSettings[$this->_propertyMapping['emailForwards']]) || array_key_exists($this->_propertyMapping['emailForwards'], $_smtpSettings)) && is_array($_smtpSettings[$this->_propertyMapping['emailForwards']]));
    }
    
    /**
     * add forward destinations
     * 
     * @param string $userId
     * @param string $source
     * @param array $forwards
     * @param string $userIdField
     */
    protected function _addForwards($userId, $source, $forwards, $userIdField = 'userid')
    {
        foreach ($forwards as $forwardAddress) {
            if (! empty($forwardAddress)) {
                // create email -> forward
                $this->_addDestination(array(
                    $userIdField  => $userId,
                    'source'      => isset($source['email']) ? $source['email'] : $source,
                    'destination' => $forwardAddress,
                    'dispatch_address' => isset($source['dispatch_address']) ? $source['dispatch_address'] : 1
                ));
            }
        }
    }
    
    /**
     * set forwards
     * 
     * @param array $_smtpSettings
     */
    protected function _createForwardDestinations($_smtpSettings)
    {
        if (! $this->_hasForwards($_smtpSettings)) {
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Setting forwards for ' . $_smtpSettings[$this->_propertyMapping['emailUsername']] . ': ' . print_r($_smtpSettings[$this->_propertyMapping['emailForwards']], TRUE));
        
        $this->_addForwards(
            $_smtpSettings[$this->_propertyMapping['emailUserId']],
            $_smtpSettings[$this->_propertyMapping['emailAddress']],
            $_smtpSettings[$this->_propertyMapping['emailForwards']]
        );
    }
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array $_rawdata
     * @return Tinebase_Record_Interface
     */
    protected function _rawDataToRecord(array &$_rawdata)
    {
        $data = array_merge($this->_defaults, $this->_getConfiguredSystemDefaults());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' raw data: ' . print_r($_rawdata, true));
        
        foreach ($_rawdata as $key => $value) {
            $keyMapping = array_search($key, $this->_propertyMapping);
            if ($keyMapping !== FALSE) {
                switch ($keyMapping) {
                    case 'emailPassword':
                        // do nothing
                        break;
                    
                    case 'emailAliases':
                    case 'emailForwards':
                        $data[$keyMapping] = explode(',', $value);
                        // Get rid of TineEmail -> username mapping.
                        $tineEmailAlias = array_search($_rawdata[$this->_propertyMapping['emailUsername']], $data[$keyMapping]);
                        if ($tineEmailAlias !== false) {
                            if ($keyMapping === 'emailForwards' ||
                                $_rawdata[$this->_propertyMapping['emailAddress']] === $_rawdata[$this->_propertyMapping['emailUsername']]
                            ) {
                                unset($data[$keyMapping][$tineEmailAlias]);
                            }
                            $data[$keyMapping] = array_values($data[$keyMapping]);
                        }
                        // sanitize aliases & forwards
                        if (count($data[$keyMapping]) == 1 && empty($data[$keyMapping][0])) {
                            $data[$keyMapping] = array();
                        }

                        if (! empty($data[$keyMapping]) && $keyMapping === 'emailAliases') {
                            // get dispatch_address
                            $data[$keyMapping] = $this->_getDispatchAddress($_rawdata['userid'], $data[$keyMapping]);
                        }

                        break;
                        
                    case 'emailForwardOnly':
                        $data[$keyMapping] = (bool)$value;
                        break;
                        
                    default: 
                        $data[$keyMapping] = $value;
                        break;
                }
            }
        }
        
        $emailUser = new Tinebase_Model_EmailUser($data, TRUE);

        $this->_getForwardedAliases($emailUser);
        
        return $emailUser;
    }
    
    /**
     * get forwarded aliases
     * - fetch aliases + forwards from destinations table that do belong to 
     *   user where aliases are directly mapped to forward addresses 
     * 
     * @param Tinebase_Model_EmailUser $emailUser
     * @param integer $usersId
     */
    protected function _getForwardedAliases(Tinebase_Model_EmailUser $emailUser, $usersId = null)
    {
        if (! $emailUser->emailForwardOnly) {
            return;
        }

        if ($usersId) {
            $field = 'users_id';
            $value = $usersId;
        } else {
            $field = $this->_propertyMapping['emailUserId'];
            $value = $emailUser->emailUserId;
        }
        
        $select = $this->_db->select()
            ->from($this->_destinationTable)
            ->where($this->_db->quoteIdentifier($this->_destinationTable . '.' . $field) . ' = ?', $value);
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();
        
        $aliases = ($emailUser->emailAliases && is_array($emailUser->emailAliases)) ? $emailUser->emailAliases : array();
        $sources = array_map(function($alias) {
            return $alias['email'];
        }, $aliases);
        foreach ($queryResult as $destination) {
            if ($destination['source'] !== $emailUser->emailAddress
                && in_array($destination['destination'], $emailUser->emailForwards)
                && ! in_array($destination['source'], $sources)
            ) {
                $aliases[] = [
                    'email' => $destination['source'],
                    'dispatch_address' => isset($destination['dispatch_address']) ? $destination['dispatch_address'] : 1
                ];
                $sources[] = $destination['source'];
            }
        }
        $emailUser->emailAliases = $aliases;
    }

    /**
     * get dispatch_address field from destinations
     *
     * @param $userid
     * @param $aliases
     * @return array
     * @throws Zend_Db_Statement_Exception
     *
     * TODO can't we fetch this via the join in _getSelect?
     */
    protected function _getDispatchAddress($userid, $aliases, $userIdProperty = null)
    {
        $userIdProperty = $userIdProperty ? $userIdProperty : $this->_propertyMapping['emailUserId'];

        $select = $this->_db->select()
            ->from($this->_destinationTable)
            ->where($this->_db->quoteIdentifier($this->_destinationTable . '.' . $userIdProperty) . ' = ?', $userid);
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();

        $result = [];
        foreach ($queryResult as $destination) {
            if (in_array($destination['source'], $aliases)
            ) {
                $result[] = [
                    'email' => $destination['source'],
                    'dispatch_address' => isset($destination['dispatch_address']) ? $destination['dispatch_address'] : 1
                ];
            }
        }
        return $result;
    }

    /**
     * returns array of raw email user data
     *
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_FullUser $_newUserProperties
     * @throws Tinebase_Exception_UnexpectedValue
     * @return array
     * 
     * @todo   validate domains of aliases too
     */
    protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties)
    {
        $rawData = array();
        
        if (isset($_newUserProperties->smtpUser)) {
            foreach ($_newUserProperties->smtpUser as $key => $value) {
                $property = (isset($this->_propertyMapping[$key]) || array_key_exists($key, $this->_propertyMapping)) ? $this->_propertyMapping[$key] : false;
                if ($property) {
                    switch ($key) {
                        case 'emailPassword':
                            $rawData[$property] = Hash_Password::generate($this->_config['emailScheme'], $value);
                            break;
                            
                        case 'emailAliases':
                            $rawData[$property] = array();
                            
                            foreach((array)$value as $address) {
                                $email = isset($address['email']) ? $address['email'] : $address;
                                if ($this->_checkDomain($email) === true) {
                                    $rawData[$property][] = $address;
                                }
                            }
                            break;
                            
                        case 'emailForwards':
                            $rawData[$property] = is_array($value) ? $value : array();
                            break;

                        case 'emailForwardOnly':
                            $rawData[$property] = (integer) $value;
                            break;

                        default:
                            $rawData[$property] = $value;
                            break;
                    }
                }
            }
        }
        
        if (!empty($_user->accountEmailAddress)) {
            $this->_checkDomain($_user->accountEmailAddress, TRUE);
        }
        
        $rawData[$this->_propertyMapping['emailAddress']]  = $_user->accountEmailAddress;
        $rawData[$this->_propertyMapping['emailUserId']]   = $_user->getId();
        $rawData[$this->_propertyMapping['emailUsername']] = $this->getEmailUserName($_user);
        
        if (empty($rawData[$this->_propertyMapping['emailAddress']])) {
            $rawData[$this->_propertyMapping['emailAliases']]  = null;
            $rawData[$this->_propertyMapping['emailForwards']] = null;
        }
        
        if (empty($rawData[$this->_propertyMapping['emailForwards']])) {
            $rawData[$this->_propertyMapping['emailForwardOnly']] = 0;
        }
        
        $rawData['client_idnr'] = $this->_clientId;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($rawData, true));
        
        return $rawData;
    }
    
    /**
     * check if email address is in allowed domains
     * 
     * @param string $_email
     * @param boolean $_throwException
     * @return boolean
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _checkDomain($_email, $_throwException = false)
    {
        return Tinebase_EmailUser::checkDomain($_email, $_throwException, $this->_config['alloweddomains']);
    }

    /**
     * check if user exists already in email backend user table
     *
     * @param  Tinebase_Model_FullUser  $_user
     * @return boolean
     */
    public function emailAddressExists(Tinebase_Model_FullUser $_user)
    {
        $select = $this->_db->select()
            ->from($this->_userTable)
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailAddress'])
                . ' = ?', $_user->accountEmailAddress)
            ->limit(1);
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();

        return (bool) $queryResult;
    }

    /**
     * copy email user
     *
     * @param Tinebase_Model_FullUser $_user
     * @param string $newId
     * @throws Tinebase_Exception
     * @throws Zend_Db_Statement_Exception
     * @return array
     */
    public function copyUser(Tinebase_Model_FullUser $_user, $newId)
    {
        $rawUser = parent::copyUser($_user, $newId);
        $this->_createDefaultDestinations($rawUser);
        return $rawUser;
    }
}
