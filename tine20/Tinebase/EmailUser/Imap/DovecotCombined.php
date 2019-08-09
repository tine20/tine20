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
 * plugin to handle dovecot imap accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Imap_DovecotCombined extends Tinebase_EmailUser_Sql implements Tinebase_EmailUser_Imap_Interface
{
    /**
     * quotas table name with prefix
     *
     * @var string
     */
    protected $_quotasTable = 'quotas';
    
    /**
     * email user config
     * 
     * @var array 
     */
    protected $_config = array(
        'prefix'            => null,
        'userTable'         => 'mailboxes',
        'quotaTable'        => 'usage',
        'emailScheme'       => 'SSHA256',
        'domain'            => null
    );
    
    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailUserId'       => 'id',
        'emailUsername'     => 'loginname',
        'emailPassword'     => 'password',
        'emailAddress'      => 'email',
        'emailForwardOnly'  => 'forward_only',
        #'emailLastLogin'    => 'last_login',
        #'emailMailQuota'    => 'quota_bytes',
        #'emailSieveQuota'   => 'quota_message',
    
        #'emailMailSize'     => 'storage',
        #'emailSieveSize'    => 'messages',

        // makes mapping data to _config easier
        'emailHome'            => 'home'
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
    protected $_subconfigKey = 'dovecotcombined';
    
    /**
     * interceptor before add
     * 
     * @param array $emailUserData
     */
    protected function _beforeAddOrUpdate(&$emailUserData)
    {
        // add configured domain to domains table
        $select = $this->_db->select()
            ->from(array('domains'), array('name'))
            ->where($this->_db->quoteIdentifier('domains.name') . ' = ?', $this->_config['domain']);
        
        $stmt = $this->_db->query($select);
        $domains = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        // did we find all domains in domains table?
        if (count($domains) < 1) {
            $this->_db->insert('domains', array(
                'id'       => Tinebase_Record_Abstract::generateUID(),
                'name'     => $this->_config['domain'],
                'backupmx' => 0,
                'active'   => 1
            ));
        }
        
        $emailUserData['last_modified'] = Tinebase_DateTime::now()->format(Tinebase_Record_Abstract::ISO8601LONG);
    }
    
    /**
     * delete user by id
     * 
     * @param string $id
     */
    public function deleteUserById($id)
    {
        $where = array(
            $this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?' => $id,
            $this->_db->quoteIdentifier($this->_userTable . '.domain') . ' = ?'          => $this->_config['domain']
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . print_r($where, TRUE));
        
        $this->_db->update($this->_userTable, array('is_deleted' => '1', 'last_modified' => Tinebase_DateTime::now()->getIso()), $where);
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

            // Left Join Quotas Table
            #->joinLeft(
            #    array($this->_quotasTable), // table
            #    '(' . $this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUsername']) .  ' = ' . // ON (left)
            #        $this->_db->quoteIdentifier($this->_quotasTable . '.' . $this->_propertyMapping['emailUsername']) . ')', // ON (right)
            #    array( // Select
            #        $this->_propertyMapping['emailMailSize']  => $this->_quotasTable . '.' . $this->_propertyMapping['emailMailSize'], // emailMailSize
            #        $this->_propertyMapping['emailSieveSize'] => $this->_quotasTable . '.' . $this->_propertyMapping['emailSieveSize'] // emailSieveSize
            #    ) 
            #)
            
            // Only want 1 user (shouldn't be more than 1 anyway)
            ->limit(1)
            
            // limit query to enabled domains
            ->where($this->_db->quoteIdentifier($this->_userTable . '.domain') .     ' = ?', $this->_config['domain'])
            ->where($this->_db->quoteIdentifier($this->_userTable . '.is_deleted') . ' = ?', '0');
            
        return $select;
    }
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array                    $_data
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
                    case 'emailUsername':
                        // do nothing
                        break;
                    case 'emailMailQuota':
                    case 'emailSieveQuota':
                        $data[$keyMapping] = $value > 0 ? $value : null;
                        break;
                    case 'emailMailSize':
                        $data[$keyMapping] = $value > 0 ? round($value/1048576, 2) : 0;
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
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_newUserProperties->imapUser->toArray(), true));
            
            foreach ($_newUserProperties->imapUser as $key => $value) {
                $property = (isset($this->_propertyMapping[$key]) || array_key_exists($key, $this->_propertyMapping)) ? $this->_propertyMapping[$key] : false;
                if ($property && ! in_array($key, $this->_readOnlyFields)) {
                    switch ($key) {
                        case 'emailUserId':
                        case 'emailUsername':
                            // set later
                            break;
                            
                        case 'emailPassword':
                            $rawData[$property] = Hash_Password::generate($this->_config['emailScheme'], $value);
                            break;
                            
                        case 'emailMailQuota':
                            $rawData[$property] = (empty($value)) ? 0 : $value;
                            break;
                            
                        default:
                            $rawData[$property] = $value;
                            break;
                    }
                }
            }
        }
        
        $rawData[$this->_propertyMapping['emailAddress']]     = $_user->accountEmailAddress;
        $rawData[$this->_propertyMapping['emailForwardOnly']] = '0'; // will be overwritten later
        $rawData[$this->_propertyMapping['emailUserId']]      = $_user->getId();
        $rawData[$this->_propertyMapping['emailUsername']]    = $_user->accountLoginName;
        $rawData[$this->_propertyMapping['emailHome']]        = '/' . $_user->accountLoginName . '_' . substr($_user->getId(), 0,8);
        
        $rawData['domain']     = $this->_config['domain'];
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($rawData, true));
        
        return $rawData;
    }
}
