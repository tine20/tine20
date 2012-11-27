<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * plugin to handle Dbmail imap accounts 
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 * 
 * @todo generalize some logic and move it to abstract parent class
 */
class Tinebase_EmailUser_Imap_Dbmail extends Tinebase_User_Plugin_Abstract
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
     * client id
     *
     * @var string
     */
    protected $_clientId = NULL;
    
    /**
     * dbmail config
     * 
     * @var array 
     * 
     * @todo add those to imap config?
     */
    protected $_config = array(
        'prefix'       => 'dbmail_',
        'userTable'    => 'users',
        'emailScheme'  => 'md5',
        'mailboxTable' => 'mailboxes',
        'emailGID'     => null
    );

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailUserId'       => 'user_idnr',
        'emailUsername'     => 'userid',
        'emailPassword'     => 'passwd',
        'emailGID'          => 'client_idnr', 
        'emailLastLogin'    => 'last_login',
        
        'emailMailQuota'    => 'maxmail_size',
        'emailMailSize'     => 'curmail_size',
        'emailSieveQuota'   => 'maxsieve_size',
        'emailSieveSize'    => 'cursieve_size',
    
        // makes mapping data to _config easier
        'emailScheme'       => 'encryption_type',
    );
    
    /**
     * dbmail readonly
     * 
     * @var array
     */
    protected $_readOnlyFields = array(
        'emailMailSize',
        'emailSieveSize',
        'emailLastLogin',
    );
    
    /**
     * stores if dbmail_users has tine20_userid column
     * 
     * @var boolean
     */
    protected $_hasTine20Userid = false;
    
    /**
     * the constructor
     *
     */
    public function __construct(array $_options = array())
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        
        // merge _config and dbmail imap
        $this->_config = array_merge($imapConfig['dbmail'], $this->_config);
        
        // set domain from imap config
        $this->_config['domain'] = !empty($imapConfig['domain']) ? $imapConfig['domain'] : null;
        
        // _tablename = "dbmail_users"
        $this->_userTable = $this->_config['prefix'] . $this->_config['userTable'];
        
        // connect to DB
        $this->_getDb($this->_config);
        
        $columns = Tinebase_Db_Table::getTableDescriptionFromCache('dbmail_users', $this->_db);
        if(array_key_exists('tine20_userid', $columns) && array_key_exists('tine20_clientid', $columns)) {
            $this->_hasTine20Userid = true;
            $this->_propertyMapping['emailUserId'] = 'tine20_userid';
            $this->_propertyMapping['emailGID']    = 'tine20_clientid';
        }
        
        $this->_clientId = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
        
        $this->_config['emailGID'] = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
    }
    
    /**
     * delete user by id
     *
     * @param  Tinebase_Model_FullUser  $_user
     */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete Dbmail settings for user ' . $_user->accountLoginName);

        if($this->_hasTine20Userid === true) {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_user->getId()),
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailGID'])    . ' = ?', $this->_config['emailGID'])
            );
        } else {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $this->_convertToInt($_user->getId())),
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailGID'])    . ' = ?', $this->_convertToInt($this->_config['emailGID']))
            );
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " delete from {$this->_userTable} " . print_r($where, true));
        
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
        
        $select = $this->_getSelect();
        
        if($this->_hasTine20Userid === true) {
            $select->where($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?',   $userId);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?',   $this->_convertToInt($userId));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        // Perferom query - retrieve user from database
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'Dbmail config for user ' . $userId . ' not found!');
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
        $_user->emailUser = Tinebase_EmailUser::merge(clone $_user->imapUser, isset($_user->emailUser) ? $_user->emailUser : null);
    }
    
    /**
     * update/set email user password
     * 
     * @param  string  $_userId
     * @param  string  $_password
     * @param  bool    $_encrypt encrypt password
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt = TRUE)
    {
        if (! $_encrypt && preg_match('/\{(.*)\}(.*)/', $_password, $matches)) {
            // if password should not be encrypted but already contains encryption type, we separate pw and type 
            $scheme = $matches[1];
            $password = $matches[2];
        } else {
            $scheme = $this->_config['emailScheme'];
            $password = ($_encrypt) ? Hash_Password::generate($scheme, $_password, false) : $_password;
        }
        
        $values = array(
            $this->_propertyMapping['emailScheme']   => $scheme,
            $this->_propertyMapping['emailPassword'] => $password,
        );
        
        if($this->_hasTine20Userid === true) {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_userId),
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailGID'])    . ' = ?', $this->_config['emailGID'])
            );
        } else {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $this->_convertToInt($userId)),
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailGID'])    . ' = ?', $this->_convertToInt($this->_config['emailGID']))
            );
        }
        
        $this->_db->update($this->_userTable, $values, $where);
    }
    
    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser  $_addedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _addUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        if (! $_addedUser->accountEmailAddress) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                . ' User ' . $_addedUser->accountDisplayName . ' has no email address defined. Skipping dbmail user creation.');
            return;
        }
        
        $imapSettings = $this->_recordToRawData($_addedUser, $_newUserProperties);
        
        $this->_removeNonDBValues($imapSettings);
        
        if (! $this->_checkOldUserRecord($imapSettings)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Adding Dbmail user ' . $imapSettings[$this->_propertyMapping['emailUsername']]);
            
            // generate random password if not set
            if (empty($imapSettings[$this->_propertyMapping['emailPassword']])) {
                $imapSettings[$this->_propertyMapping['emailPassword']] = Hash_Password::generate($this->_config['emailScheme'], Tinebase_Record_Abstract::generateUID(), FALSE);
            }
            
            $this->_db->insert($this->_userTable, $imapSettings);
        }
        
        $this->inspectGetUserByProperty($_addedUser);
    }
    
    /**
     * remove some values that should not be written to dbmail DB
     * 
     * @param array $userdata
     */
    protected function _removeNonDBValues(&$userdata)
    {
        unset($userdata[$this->_propertyMapping['emailMailSize']]);
        unset($userdata[$this->_propertyMapping['emailSieveSize']]);
        unset($userdata[$this->_propertyMapping['emailLastLogin']]);
    }
    
    /**
     * check if old entry exists and update it
     * 
     * @param array $_userData
     * @return boolean (TRUE if old record exists)
     */
    protected function _checkOldUserRecord($_userData)
    {
        $userIdProperty = $this->_propertyMapping['emailUsername'];
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier($userIdProperty) . ' = ?', $_userData[$userIdProperty]);
        $select = $this->_db->select();
        $select->from(array($this->_userTable => $this->_userTable), array($userIdProperty))
            ->where($where)
            ->limit(1);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        if ($queryResult) {
            // preserve current pw
            unset($_userData[$this->_propertyMapping['emailPassword']]);
            
            $this->_update($_userData, $where);
            return TRUE;
        }
        
        return FALSE;
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
        
        if($this->_hasTine20Userid === true) {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $_updatedUser->getId()),
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailGID'])    . ' = ?', $this->_config['emailGID'])
            );
        } else {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $this->_convertToInt($_updatedUser->getId())),
                $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailGID'])    . ' = ?', $this->_convertToInt($this->_config['emailGID']))
            );
        }

        unset($imapSettings[$this->_propertyMapping['emailUserId']]);
        $this->_removeNonDBValues($imapSettings);
        
        $this->_update($imapSettings, $where);
        
        $this->inspectGetUserByProperty($_updatedUser);
    }
    
    /**
     * update user in dbmail db
     * 
     * @param array $userData
     * @param mixed $where
     */
    protected function _update($userData, $where)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . " Update user {$userData[$this->_propertyMapping['emailUsername']]} in {$this->_userTable}");
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . " " . print_r($userData, TRUE));
        
        $this->_db->update($this->_userTable, $userData, $where);
    }
    
    /**
     * check if user exists already in dbmail user table
     * 
     * @param  Tinebase_Model_FullUser  $_user
     */
    protected function _userExists(Tinebase_Model_FullUser $_user)
    {
        $userId = $_user->getId();
        
        $select = $this->_getSelect();
        
        $select->where($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?',   ($this->_hasTine20Userid === true) ? $userId : $this->_convertToInt($userId));
                  
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

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
     * @param  array                     $_data
     * @return Tinebase_Model_EmailUser
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
                        
                    case 'emailMailQuota':
                    case 'emailMailSize':
                    case 'emailSieveQuota':
                    case 'emailSieveSize':
                        $data[$keyMapping] = convertToMegabytes($value);
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
     * returns array of raw Dbmail data
     *
     * @param  Tinebase_Model_EmailUser  $_user
     * @param  Tinebase_Model_EmailUser  $_newUserProperties
     * @return array
     */
    protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties)
    {
        $rawData = array();
        
        foreach ($_newUserProperties->imapUser as $key => $value) {
            $property = array_key_exists($key, $this->_propertyMapping) ? $this->_propertyMapping[$key] : false;
            if ($property && ! in_array($key, $this->_readOnlyFields)) {
                switch ($key) {
                    case 'emailPassword':
                        $rawData[$property] =  Hash_Password::generate($this->_config['emailScheme'], $value, false);
                        $rawData[$this->_propertyMapping['emailScheme']]   = $this->_config['emailScheme'];
                        break;
                        
                    case 'emailUserId':
                    case 'emailGID':
                    case 'emailUsername':
                        // do nothing
                        break;
                        
                    case 'emailMailQuota':
                    case 'emailMailSize':
                    case 'emailSieveQuota':
                    case 'emailSieveSize':
                        // convert to bytes
                        $rawData[$property] = convertToBytes($value . 'M');
                        break;
                        
                    default:
                        $rawData[$property] = $value;
                }
            }
        }
        
        $rawData[$this->_propertyMapping['emailUserId']]   = $this->_hasTine20Userid === true ? $_user->getId() : $this->_convertToInt($_user->getId());
        if($this->_hasTine20Userid === true) {
            $rawData[$this->_propertyMapping['emailGID']]  = $this->_config['emailGID'];
            $rawData['client_idnr']                        = $this->_convertToInt($this->_config['emailGID']);
        } else {
            $rawData[$this->_propertyMapping['emailGID']]  = $this->_convertToInt($this->_config['emailGID']);
        }
        $rawData[$this->_propertyMapping['emailUsername']] = $this->_appendDomain($_user->accountLoginName);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($rawData, TRUE));
        
        return $rawData;
    }
    
    /**
     * convert some string to absolute int with crc32
     * 
     * @param  $_string
     * @return integer
     */
    protected function _convertToInt($_string)
    {
        return sprintf("%u", crc32($_string));
    }
    
    /**
     * create mailbox for user
     * 
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @param  string                    $_mailboxName
     * @return void
     */
    protected function _createMailbox(Tinebase_Model_EmailUser $_emailUser, $_mailboxName = 'INBOX')
    {
        $data = array(
            'owner_idnr'    => $_emailUser->emailUID,
            'name'          => $_mailboxName,
            'seen_flag'     => 1,
            'answered_flag' => 1,
            'deleted_flag'  => 1,
            'flagged_flag'  => 1,
            'recent_flag'   => 1,
            'draft_flag'    => 1,
        );
        
        $this->_db->insert($this->_config['prefix'] . $this->_config['mailboxTable'], $data);
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
        $select = $this->_db->select()
            ->from($this->_userTable);

        if($this->_hasTine20Userid === true) {
            $select->where($this->_db->quoteIdentifier($this->_propertyMapping['emailGID']) . ' = ?', $this->_config['emailGID'])
                   ->limit(1);
        } else {
            $select->where($this->_db->quoteIdentifier($this->_propertyMapping['emailGID']) . ' = ?', $this->_convertToInt($this->_config['emailGID']))
                   ->limit(1);
        }
        
        return $select;
    }
}  
