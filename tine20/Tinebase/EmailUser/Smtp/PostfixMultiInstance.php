<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * 
--
-- Database: `postfix`
--

-- --------------------------------------------------------

--
-- Table structure for table `smtp_users`
--

CREATE TABLE IF NOT EXISTS `smtp_users` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`userid` varchar(40) NOT NULL,
`client_idnr` varchar(40) DEFAULT NULL,
`username` varchar(80) NOT NULL,
`passwd` varchar(256) NOT NULL,
`email` varchar(80) DEFAULT NULL,
`forward_only` tinyint(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`id`),
UNIQUE KEY `username` (`username`),
UNIQUE KEY `userid-client_idnr` (`userid`,`client_idnr`),
UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `smtp_destinations`
--

CREATE TABLE IF NOT EXISTS `smtp_destinations` (
`users_id` int(11) NOT NULL,
`source` varchar(80) NOT NULL,
`destination` varchar(80) NOT NULL,
KEY `users_id` (`users_id`),
KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Constraints for dumped tables
--

--
-- Constraints for table `smtp_destinations`
--
ALTER TABLE `smtp_destinations`
ADD CONSTRAINT `smtp_destinations_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `smtp_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Table structure for table `smtp_virtual_domains`
--

CREATE TABLE `smtp_virtual_domains` (
`domain` varchar(50) NOT NULL,
`instancename` varchar(40) NOT NULL,
PRIMARY KEY (`domain`)
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
 *
 * @todo extend Tinebase_EmailUser_Smtp_Postfix some more (use _propertyMapping)
 */
class Tinebase_EmailUser_Smtp_PostfixMultiInstance extends Tinebase_EmailUser_Smtp_Postfix implements Tinebase_EmailUser_Smtp_Interface
{
    /**
     * subconfig for user email backend (for example: dovecot)
     * 
     * @var string
     */
    protected $_subconfigKey = 'postfixmultiinstance';

    /**
     * get the basic select object to fetch records from the database
     *  
     * @param  array|string|Zend_Db_Expr  $_cols        columns to get, * per default
     * @param  boolean                    $_getDeleted  get deleted records (if modlog is active)
     * @return Zend_Db_Select
     *
     * TODO remove code duplication with \Tinebase_EmailUser_Smtp_Postfix::_getSelect
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        // _userTable.emailUserId=_destinationTable.emailUserId
        $userIDMap    = $this->_db->quoteIdentifier($this->_userTable . '.id');
        $userEmailMap = $this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailAddress']);

        $select = $this->_db->select()
            ->from($this->_userTable)
            ->group(array($this->_userTable . '.userid', $this->_userTable . '.client_idnr'))
            // Only want 1 user (shouldn't be more than 1 anyway)
            ->limit(1);
            
        // select source from alias table
        $select->joinLeft(
            array('aliases' => $this->_destinationTable), // Table
            '(' . $userIDMap .  ' = ' . $this->_db->quoteIdentifier('aliases.users_id') .
            ' AND ' . $userEmailMap . ' = ' . // AND ON (left)
            $this->_db->quoteIdentifier('aliases.' . $this->_propertyMapping['emailForwards']) . ')', // AND ON (right)
            array($this->_propertyMapping['emailAliases'] => $this->_dbCommand->getAggregate('aliases.' . $this->_propertyMapping['emailAliases']))); // Select
        
        // select destination from alias table
        $select->joinLeft(
            array('forwards' => $this->_destinationTable), // Table
            '(' . $userIDMap .  ' = ' . $this->_db->quoteIdentifier('forwards.users_id') .
            ' AND ' . $userEmailMap . ' = ' . // AND ON (left)
            $this->_db->quoteIdentifier('forwards.' . $this->_propertyMapping['emailAliases']) . ')', // AND ON (right)
            array($this->_propertyMapping['emailForwards'] => $this->_dbCommand->getAggregate('forwards.' . $this->_propertyMapping['emailForwards']))); // Select

        // append domain if set or domain IS NULL
        if (! empty($this->_clientId)) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' = ?', $this->_clientId);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.client_idnr') . ' IS NULL');
        }
        
        return $select;
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
     *
     * TODO remove code duplication with \Tinebase_EmailUser_Smtp_Postfix::_setAliasesAndForwards
     */
    protected function _setAliasesAndForwards($_smtpSettings)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Setting default alias/forward for ' . print_r($_smtpSettings, true));

        if (!isset($_smtpSettings['id'])) {
            $_smtpSettings['id'] = $this->_db->lastInsertId();
            if (!$_smtpSettings['id']) {
                $row = $this->_getSelect()->where('userid = ?', $_smtpSettings['userid'])->query()
                    ->fetch(Zend_Db::FETCH_ASSOC);
                $_smtpSettings['id'] = $row['id'];
            }
        }

        if (empty($_smtpSettings['id'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Could not find userid for smtp user: ' . print_r($_smtpSettings, true));
            return;
        }

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
            $this->_db->quoteInto($this->_db->quoteIdentifier('users_id') . ' = ?', $user['id']),
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
            'users_id'      => $_smtpSettings['id'],
            'source'        => $_smtpSettings[$this->_propertyMapping['emailAddress']],  // TineEmail
            'destination'   => $_smtpSettings[$this->_propertyMapping['emailUsername']], // email
        ));
        
        // create username -> username alias if email and username are different
        if ($_smtpSettings[$this->_propertyMapping['emailUsername']] != $_smtpSettings[$this->_propertyMapping['emailAddress']]) {
            $this->_addDestination(array(
                'users_id'    => $_smtpSettings['id'],
                'source'      => $_smtpSettings[$this->_propertyMapping['emailUsername']], // username
                'destination' => $_smtpSettings[$this->_propertyMapping['emailUsername']], // username
            ));
        }
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
        parent::_createAliasDestinations($_smtpSettings, 'users_id');
    }

    /**
     * check if forward addresses exist
     * 
     * @param array $_smtpSettings
     * @return boolean
     */
    protected function _hasForwards($_smtpSettings)
    {
        return isset($_smtpSettings[$this->_propertyMapping['emailForwards']]) && is_array($_smtpSettings[$this->_propertyMapping['emailForwards']]);
    }
    
    /**
     * add forward destinations
     *
     * @param string $users_id
     * @param string $source
     * @param array $forwards
     * @param string $userIdField
     */
    protected function _addForwards($users_id, $source, $forwards, $userIdField = 'userid')
    {
        parent::_addForwards($users_id, $source, $forwards, 'users_id');
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
            $_smtpSettings['id'],
            $_smtpSettings[$this->_propertyMapping['emailAddress']],
            $_smtpSettings[$this->_propertyMapping['emailForwards']]
        );
    }
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array $_rawdata
     * @return Tinebase_Record_Interface
     *
     * TODO remove code duplication with \Tinebase_EmailUser_Smtp_Postfix::_rawDataToRecord
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
                            $data[$keyMapping] = $this->_getDispatchAddress($_rawdata['id'], $data[$keyMapping], 'users_id');
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

        if (isset($_rawdata['id'])) {
            $this->_getForwardedAliases($emailUser, $_rawdata['id']);
        }
        
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
        parent::_getForwardedAliases($emailUser, $usersId);
    }
    
    /**
     * returns array of raw email user data
     *
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_FullUser $_newUserProperties
     * @throws Tinebase_Exception_UnexpectedValue
     * @return array
     */
    protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties)
    {
        $rawData = parent::_recordToRawData($_user, $_newUserProperties);

        if (isset($rawData['id'])) {
            unset($rawData['id']);
        }
        if (($row = $this->getRawUserById($_user)) && is_array($row) && isset($row['id'])) {
            $rawData['id'] = $row['id'];
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($rawData, true));
        
        return $rawData;
    }
}
