<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

 /**
 * plugin to handle postfix smtp accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_PostfixCombined extends Tinebase_EmailUser_Sql
{
    /**
     * destination table name with prefix
     *
     * @var string
     */
    protected $_destinationTable = NULL;
    protected $_forwardTable = 'forwards';
    
    /**
     * postfix config
     * 
     * @var array 
     */
    protected $_config = array(
        'prefix'            => '',
        'userTable'         => 'mailboxes',
        'destinationTable'  => 'aliases',
        'domain'            => null,
        'alloweddomains'    => array()
    );

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailUserId'       => 'id',
        'emailAddress'      => 'email',
        'emailForwardOnly'  => 'forward_only',
        'emailUsername'     => 'loginname',
        'emailAliases'      => 'aliases',
        'emailForwards'     => 'forwards'
    );
    
    /**
     * the constructor
     */
    public function __construct(array $_options = array())
    {
        $this->_configKey    = Tinebase_Config::SMTP;

        parent::__construct($_options);
        
        $smtpConfig = Tinebase_Config::getInstance()->get($this->_configKey, new Tinebase_Config_Struct())->toArray();
        
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
        
        $this->_destinationTable = $this->_config['prefix'] . $this->_config['destinationTable'];
    }
    
    /**
     * set database connection shared with Tinebase_EmailUser::DOVECOT_IMAP_COMBINED backend
     * 
     * @param array $_config
     */
    protected function _getDb($_config)
    {
        $dovecotCombined = Tinebase_EmailUser::factory(Tinebase_EmailUser::DOVECOT_COMBINED);
        
        $this->_db = $dovecotCombined->getDb();
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
        $userIDMap = $this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']);
        
        $select = $this->_db->select()
            ->from(array('mailboxes' => $this->_userTable))
            ->group($this->_userTable . '.' . $this->_propertyMapping['emailUserId'])
            // Only want 1 user (shouldn't be more than 1 anyway)
            ->limit(1)
            
            // select aliases from aliases table
            ->joinLeft(
                /* table  */ array('aliases' => $this->_destinationTable),
                /* on     */ $userIDMap . ' = ' . $this->_db->quoteIdentifier('aliases.mailbox_id'),
                /* select */ array($this->_propertyMapping['emailAliases'] => $this->_dbCommand->getAggregate('aliases.alias')))
         
            // select forwards from forwards table
            ->joinLeft(
                /* table  */ array('forwards' => $this->_forwardTable),
                /* on     */ $userIDMap . ' = ' . $this->_db->quoteIdentifier('forwards.mailbox_id'),
                /* select */ array($this->_propertyMapping['emailForwards'] => $this->_dbCommand->getAggregate('forwards.forward')))
            
            // limit query to enabled domains
            ->where($this->_db->quoteIdentifier($this->_userTable . '.domain') .     ' = ?', $this->_config['domain'])
            ->where($this->_db->quoteIdentifier($this->_userTable . '.is_deleted') . ' = ?', '0');
            
        return $select;
    }
    
    /**
    * interceptor before add
    *
    * @param array $emailUserData
    */
    protected function _beforeAddOrUpdate(&$emailUserData)
    {
        // add all configured domains to domains table
        $select = $this->_db->select()
            ->from(array('domains'), array('name'))
            ->where($this->_db->quoteIdentifier('domains.name') . ' IN (?)', $this->_config['alloweddomains']);
        
        $stmt = $this->_db->query($select);
        $domains = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        // did we find all domains in domains table?
        if (count($domains) < count($this->_config['alloweddomains'])) {
            foreach (array_diff($this->_config['alloweddomains'], $domains) as $domain) {
                $this->_db->insert('domains', array(
                    'id'       => Tinebase_Record_Abstract::generateUID(),
                    'name'     => $domain,
                    'backupmx' => 0,
                    'active'   => 1
                ));
            }
        }
        
        $emailUserData['last_modified'] = Tinebase_DateTime::now()->format(Tinebase_Record_Abstract::ISO8601LONG);
        
        unset($emailUserData[$this->_propertyMapping['emailForwards']]);
        unset($emailUserData[$this->_propertyMapping['emailAliases']]);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Setting alias/forward for ' . print_r($_smtpSettings, true));
        
        $this->_removeAliasesAndForwards($_smtpSettings[$this->_propertyMapping['emailUserId']]);
        
        $this->_setAliases($_smtpSettings);
        $this->_setForwards($_smtpSettings);
    }
    
    /**
     * remove all current aliases and forwards for user
     * 
     * @param string $userId
     */
    protected function _removeAliasesAndForwards($userId)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('mailbox_id') . ' = ?', $userId)
        );
        
        $this->_db->delete($this->_destinationTable, $where);
        $this->_db->delete($this->_forwardTable, $where);
    }
    
    /**
     * set aliases
     * 
     * @param array $_smtpSettings
     */
    protected function _setAliases($_smtpSettings)
    {
        if (! ((isset($_smtpSettings[$this->_propertyMapping['emailAliases']]) || array_key_exists($this->_propertyMapping['emailAliases'], $_smtpSettings)) && is_array($_smtpSettings[$this->_propertyMapping['emailAliases']]))) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Setting aliases for '
            . $_smtpSettings[$this->_propertyMapping['emailUsername']] . ': ' . print_r($_smtpSettings[$this->_propertyMapping['emailAliases']], TRUE));
        
        $userId = $_smtpSettings[$this->_propertyMapping['emailUserId']];
        
        foreach ($_smtpSettings[$this->_propertyMapping['emailAliases']] as $aliasAddress) {
            // check if in primary or secondary domains
            if (! empty($aliasAddress) && $this->_checkDomain($aliasAddress)) {
                $this->_db->insert($this->_destinationTable, array(
                    'id'         => Tinebase_Record_Abstract::generateUID(),
                    'mailbox_id' => $userId,
                    'alias'      => $aliasAddress
                ));
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
     * set forwards
     * 
     * @param array $_smtpSettings
     */
    protected function _setForwards($_smtpSettings)
    {
        if (! $this->_hasForwards($_smtpSettings)) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Setting forwards for ' . $_smtpSettings[$this->_propertyMapping['emailUsername']] . ': ' . print_r($_smtpSettings[$this->_propertyMapping['emailForwards']], TRUE));
        
        foreach ($_smtpSettings[$this->_propertyMapping['emailForwards']] as $forwardAddress) {
            if (! empty($forwardAddress)) {
                // create email -> forward
                $this->_db->insert($this->_forwardTable, array(
                    'id'         => Tinebase_Record_Abstract::generateUID(),
                    'mailbox_id' => $_smtpSettings[$this->_propertyMapping['emailUserId']],
                    'forward'    => $forwardAddress
                ));
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
        
        $emailUser = new Tinebase_Model_EmailUser($data, TRUE);
        
        return $emailUser;
    }
    
    /**
     * returns array of raw email user data
     *
     * @param  Tinebase_Model_EmailUser $_user
     * @param  Tinebase_Model_EmailUser $_newUserProperties
     * @throws Tinebase_Exception_UnexpectedValue
     * @return array
     * 
     * @todo   validate domains of aliases too
     */
    protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties)
    {
        $rawData = array();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_newUserProperties->toArray(), true));
        
        if (isset($_newUserProperties->smtpUser)) {
            foreach ($_newUserProperties->smtpUser as $key => $value) {
                $property = (isset($this->_propertyMapping[$key]) || array_key_exists($key, $this->_propertyMapping)) ? $this->_propertyMapping[$key] : false;
                if ($property) {
                    switch ($key) {
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
        }
        
        if (!empty($_user->accountEmailAddress)) {
            $this->_checkDomain($_user->accountEmailAddress, TRUE);
        }
        
        $rawData[$this->_propertyMapping['emailAddress']]  = $_user->accountEmailAddress;
        $rawData[$this->_propertyMapping['emailUserId']]   = $_user->getId();
        $rawData[$this->_propertyMapping['emailUsername']] = $_user->accountLoginName;
        
        if (empty($rawData[$this->_propertyMapping['emailAddress']])) {
            $rawData[$this->_propertyMapping['emailAliases']]  = null;
            $rawData[$this->_propertyMapping['emailForwards']] = null;
        }
        
        if (empty($rawData[$this->_propertyMapping['emailForwards']])) {
            $rawData[$this->_propertyMapping['emailForwardOnly']] = 0;
        }
        
        $rawData['domain']     = $this->_config['domain'];
        
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
     * delete user by id
     * 
     * @param string $id
     */
    protected function _deleteUserById($id)
    {
        $where = array(
            $this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?' => $id,
            $this->_db->quoteIdentifier($this->_userTable . '.domain') . ' = ?'          => $this->_config['domain']
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . print_r($where, TRUE));
        
        $this->_db->update($this->_userTable, array('is_deleted' => '1', 'last_modified' => Tinebase_DateTime::now()->getIso()), $where);
    }
}
