<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
--
-- Database: `postfix`
--

-- --------------------------------------------------------

--
-- Table structure for table `smtp_users`
--

CREATE TABLE IF NOT EXISTS `smtp_users` (
  `userid` varchar(40) NOT NULL,
  `client_idnr` varchar(40) NOT NULL,
  `username` varchar(80) NOT NULL,
  `passwd` varchar(80) NOT NULL,
  `email` varchar(80) DEFAULT NULL,
  `forward_only` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userid`, `client_idnr`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `smtp_destinations`
--

CREATE TABLE IF NOT EXISTS `smtp_destinations` (
  `userid` VARCHAR( 40 ) NOT NULL ,
  `source` VARCHAR( 80 ) NOT NULL ,
  `destination` VARCHAR( 80 ) NOT NULL ,
  CONSTRAINT `smtp_destinations::userid--smtp_users::userid` FOREIGN KEY (`userid`) 
  REFERENCES `smtp_users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=Innodb DEFAULT CHARSET=utf8;
-- --------------------------------------------------------

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
 * plugin to handle postfix smtp accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_Postfix extends Tinebase_User_Plugin_Abstract
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
     * destination table name with prefix
     *
     * @var string
     */
    protected $_destinationTable = NULL;
    
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
     */
    protected $_config = array(
        'prefix'            => 'smtp_',
        'userTable'         => 'users',
        'destinationTable'  => 'destinations',
        'emailScheme'       => 'ssha256',
        'domain'            => null,
        'alloweddomains'    => array()
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
    
    /**
     * the constructor
     */
    public function __construct(array $_options = array())
    {
        $smtpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Config::SMTP);
        
        // merge _config and postfix smtp
        $this->_config = array_merge($smtpConfig['postfix'], $this->_config);
        
        // set domain from smtp config
        $this->_config['domain'] = !empty($smtpConfig['primarydomain']) ? $smtpConfig['primarydomain'] : null;
        
        // add allowed domains
        if (! empty($smtpConfig['primarydomain'])) { 
            $this->_config['alloweddomains'] = array($smtpConfig['primarydomain']);
            if (! empty($smtpConfig['secondarydomains'])) {
                // merge primary and secondary domains and split secondary domains + trim whitespaces
                $this->_config['alloweddomains'] = array_merge($this->_config['alloweddomains'], preg_split('/\s*,\s*/', $smtpConfig['secondarydomains']));
            } 
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, true));
        
        $this->_clientId = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
        
        // table names
        $this->_userTable  = $this->_config['prefix'] . $this->_config['userTable'];
        $this->_destinationTable = $this->_config['prefix'] . $this->_config['destinationTable'];
        
        // get database connection
        $this->_getDb($this->_config);
    }

    /**
     * delete user by id
     *
     * @param  Tinebase_Model_FullUser  $_user
     */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete Postfix settings for user ' . $_user->accountLoginName);

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_user->getId())
        );
        // append domain if set or domain IS NULL
        if (! empty($this->_clientId)) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.' . 'client_idnr') . ' = ?', $this->_clientId);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_userTable . '.' . 'client_idnr') . ' IS NULL';
        }
        
        $this->_db->delete($this->_userTable, $where);
    }
    
    /**
     * inspect get user by property
     * 
     * @param  Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user)
    {
        if (! $_user instanceof Tinebase_Model_FullUser) {
            return;
        }
        
        $userId = $_user->getId();
        
        $select = $this->_getSelect()
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']) . ' = ?', $userId);
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        // Perferom query - retrieve user from database
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Postfix config for user ' . $userId . ' not found!');
            return;
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));
        
        // convert data to Tinebase_Model_EmailUser       
        $emailUser = $this->_rawDataToRecord($queryResult);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUser->toArray(), TRUE));
        
        // modify/correct user name
        // set emailUsername to Tine accout login name and append domain for login purposes if set
        $emailUser->emailUsername = $this->_appendDomain($_user->accountLoginName);

        $_user->smtpUser  = $emailUser;
        $_user->emailUser = Tinebase_EmailUser::merge(isset($_user->emailUser) ? $_user->emailUser : null, $_user->smtpUser);
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
            $this->_propertyMapping['emailPassword'] => Hash_Password::generate($this->_config['emailScheme'], $_password)
        );
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_userId)
        );
        // append domain if set or domain IS NULL
        if (! empty($this->_clientId)) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' = ?', $this->_clientId);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' IS NULL';
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($values, TRUE));
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, TRUE));
        
        $this->_db->update($this->_userTable, $values, $where);
    }
    
    /**
     * inspect data used to update user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        if (!isset($_newUserProperties->smtpUser)) {
            return;
        }
        
        if ($this->_userExists($_updatedUser) === true) {
            $this->_updateUser($_updatedUser, $_newUserProperties);
        } else {
            $this->_addUser($_updatedUser, $_newUserProperties);
        }
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
            array($this->_propertyMapping['emailAliases'] => 'GROUP_CONCAT( DISTINCT ' . $this->_db->quoteIdentifier('aliases.' . $this->_propertyMapping['emailAliases']) . ')')); // Select
        
        // select destination from alias table
        $select->joinLeft(
            array('forwards' => $this->_destinationTable), // Table
            '(' . $userIDMap .  ' = ' . // ON (left)
            $this->_db->quoteIdentifier('forwards.' . $this->_propertyMapping['emailUserId']) . // ON (right)
            ' AND ' . $userEmailMap . ' = ' . // AND ON (left)
            $this->_db->quoteIdentifier('forwards.' . $this->_propertyMapping['emailAliases']) . ')', // AND ON (right)
            array($this->_propertyMapping['emailForwards'] => 'GROUP_CONCAT( DISTINCT ' . $this->_db->quoteIdentifier('forwards.' . $this->_propertyMapping['emailForwards']) . ')')); // Select
            
            
        // append domain if set or domain IS NULL
        if (! empty($this->_clientId)) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' = ?', $this->_clientId);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' IS NULL');
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
        $smtpSettings = $this->_recordToRawData($_addedUser, $_newUserProperties);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating postfix user ' . $smtpSettings[$this->_propertyMapping['emailUsername']]);
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($smtpSettings, TRUE));
                
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            $values = $smtpSettings;
            // generate random password if not set
            if (empty($values[$this->_propertyMapping['emailPassword']])) {
                $values[$this->_propertyMapping['emailPassword']] = Hash_Password::generate($this->_config['emailScheme'], Tinebase_Record_Abstract::generateUID());
            }
            unset($values[$this->_propertyMapping['emailForwards']]);
            unset($values[$this->_propertyMapping['emailAliases']]);
            
            $this->_db->insert($this->_userTable, $values);
            
            // add forwards and aliases
            $this->_setAliases($smtpSettings);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->inspectGetUserByProperty($_addedUser);
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while creating postfix email user: ' . $zdse->getMessage());
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
        $smtpSettings = $this->_recordToRawData($_updatedUser, $_newUserProperties);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating postfix user ' . $smtpSettings[$this->_propertyMapping['emailUsername']]);
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($smtpSettings, TRUE));
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $smtpSettings[$this->_propertyMapping['emailUserId']]),
        );
        // append domain if set or "domain IS NULL"
        if (! empty($this->_clientId)) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' = ?', $this->_clientId);
        } else {
            $where[] = $this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' IS NULL';
        }
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            $values = $smtpSettings;
            unset($values[$this->_propertyMapping['emailForwards']]);
            unset($values[$this->_propertyMapping['emailAliases']]);
            
            $this->_db->update($this->_userTable, $values, $where);
        
            // add forwards and aliases
            $this->_setAliases($smtpSettings);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            $this->inspectGetUserByProperty($_updatedUser);
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while updating postfix email user: ' . $zdse->getMessage());
        }            
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
    protected function _setAliases($_smtpSettings)
    {
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting default alias/forward for ' . print_r($_smtpSettings, true));
        
        // remove all current aliases and forwards for user
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_smtpSettings[$this->_propertyMapping['emailUserId']])
        );
        
        $this->_db->delete($this->_destinationTable, $where);
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting default alias/forward for ' . $_smtpSettings[$this->_propertyMapping['emailUsername']]);
        
        // create default alias/forward
        // check if it should be forward only
        if (!($_smtpSettings[$this->_propertyMapping['emailForwardOnly']])){
            // create email -> username alias
            $aliasArray = array(
                    'userid'        => $_smtpSettings[$this->_propertyMapping['emailUserId']],   // userID
                    'source'        => $_smtpSettings[$this->_propertyMapping['emailAddress']],  // TineEmail
                    'destination'   => $_smtpSettings[$this->_propertyMapping['emailUsername']], // email
            );
            // insert into table
            $this->_db->insert($this->_destinationTable, $aliasArray);
            
            // create username -> username alias if email and username are different
            if ($_smtpSettings[$this->_propertyMapping['emailUsername']] != $_smtpSettings[$this->_propertyMapping['emailAddress']]) {
                $aliasArray = array(
                        'userid'      => $_smtpSettings[$this->_propertyMapping['emailUserId']],   // userID
                        'source'      => $_smtpSettings[$this->_propertyMapping['emailUsername']], // username
                        'destination' => $_smtpSettings[$this->_propertyMapping['emailUsername']], // username
                );
                // insert into table
                $this->_db->insert($this->_destinationTable, $aliasArray);
            }
        }
        
        // Set Aliases
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting aliases for ' . $_smtpSettings[$this->_propertyMapping['emailUsername']] . ': ' . print_r($_smtpSettings[$this->_propertyMapping['emailAliases']], TRUE));

        if (array_key_exists($this->_propertyMapping['emailAliases'], $_smtpSettings) && is_array($_smtpSettings[$this->_propertyMapping['emailAliases']])) {
            foreach ($_smtpSettings[$this->_propertyMapping['emailAliases']] as $aliasAddress) {
                // check if in primary or secondary domains
                if (! empty($aliasAddress) && $this->_checkDomain($aliasAddress)) {
                    // create alias -> email
                    $aliasArray = array(
                        'userid'      => $_smtpSettings[$this->_propertyMapping['emailUserId']],  // userID
                        'source'      => $aliasAddress,                                           // alias
                        'destination' => $_smtpSettings[$this->_propertyMapping['emailAddress']], // email 
                    );
                    // insert into table
                    $this->_db->insert($this->_destinationTable, $aliasArray);
                }
            }
        }
        
        // Set Forwards
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting forwards for ' . $_smtpSettings[$this->_propertyMapping['emailUsername']] . ': ' . print_r($_smtpSettings[$this->_propertyMapping['emailForwards']], TRUE));

        if (array_key_exists($this->_propertyMapping['emailForwards'], $_smtpSettings) && is_array($_smtpSettings[$this->_propertyMapping['emailForwards']])) {
            foreach ($_smtpSettings[$this->_propertyMapping['emailForwards']] as $forwardAddress) {
                if (! empty($forwardAddress)) {
                    // create email -> forward
                    $forwardArray = array(
                        'userid'      => $_smtpSettings[$this->_propertyMapping['emailUserId']],  // userID
                        'source'      => $_smtpSettings[$this->_propertyMapping['emailAddress']], // email
                        'destination' => $forwardAddress                                          // forward
                    );
                    // insert into table
                    $this->_db->insert($this->_destinationTable, $forwardArray);
                }
            }
        }
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
            $keyMapping = array_search($key, $this->_propertyMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'emailPassword':
                        // do nothing
                        break;
                    
                    case 'emailAliases':
                    case 'emailForwards':
                        $data[$keyMapping] = explode(',', $value);
                        // Get rid of TineEmail -> username mapping.
                        $tineEmailAlias = array_search($_rawdata[$this->_propertyMapping['emailUsername']], $data[$keyMapping]);
                        if ($tineEmailAlias !== false) {
                            unset($data[$keyMapping][$tineEmailAlias]);
                            $data[$keyMapping] = array_values($data[$keyMapping]);
                        }
                        // sanitize aliases & forwards
                        if (count($data[$keyMapping]) == 1 && empty($data[$keyMapping][0])) {
                            $data[$keyMapping] = array();
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
        
        return new Tinebase_Model_EmailUser($data, true);
    }
    
    /**
     * returns array of raw Dovecot data
     *
     * @param  Tinebase_Model_EmailUser $_user
     * @param  Tinebase_Model_EmailUser $_newUserProperties
     * @todo   validate domains of aliases too
     * @return array
     */
    protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties)
    {
        $rawData = array();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_newUserProperties->toArray(), true));
        
        foreach ($_newUserProperties->smtpUser as $key => $value) {
            
            $property = array_key_exists($key, $this->_propertyMapping) ? $this->_propertyMapping[$key] : false;
            if ($property) {
                switch ($key) {
                    case 'emailPassword':
                        $rawData[$property] = Hash_Password::generate($this->_config['emailScheme'], $value);
                        break;
                        
                    case 'emailAliases':
                        $rawData[$property] = array();
                        
                        foreach((array)$value as $address) {
                            if ($this->_checkDomain($address) === true) {
                                $rawData[$property][] = $address;
                            }
                        }
                        break;
                        
                    case 'emailForwards':
                        $rawData[$property] = is_array($value) ? $value : array();
                        
                        break;
                        
                    default:
                        $rawData[$property] = $value;
                        break;
                }
            }
            
        }
        
        if (!empty($_user->accountEmailAddress)) {
            $this->_checkDomain($_user->accountEmailAddress, TRUE);
        }        
        $rawData[$this->_propertyMapping['emailAddress']]  = $_user->accountEmailAddress;
        $rawData[$this->_propertyMapping['emailUserId']]   = $_user->getId();
        $rawData[$this->_propertyMapping['emailUsername']] = $this->_appendDomain($_user->accountLoginName);
        
        if (empty($rawData[$this->_propertyMapping['emailAddress']])) {
            $rawData[$this->_propertyMapping['emailAliases']]  = null;
            $rawData[$this->_propertyMapping['emailForwards']] = null;
        }
        
        if (empty($rawData[$this->_propertyMapping['emailForwards']])) {
            $rawData[$this->_propertyMapping['emailForwardOnly']] = 0;
        }
        
                        
        $rawData['client_idnr'] = $this->_clientId;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($rawData, true));
        
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
        $result = true;
        
        if (! empty($this->_config['alloweddomains'])) {

            list($user, $domain) = explode('@', $_email, 2);
            
            if (! in_array($domain, $this->_config['alloweddomains'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Email address ' . $_email . ' not in allowed domains!');
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Allowed domains: ' . print_r($this->_config['alloweddomains'], TRUE));
                
                if ($_throwException) {
                    throw new Tinebase_Exception_UnexpectedValue('Email address not in allowed domains!');
                } else {
                    $result = false;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * check if user exists already in postfix user table
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
    
}  
