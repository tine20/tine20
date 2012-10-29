<?php

/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Account Controller for Sipgate
 *
 * @package     Sipgate
 * @subpackage  Controller
 */
class Sipgate_Controller_Account extends Tinebase_Controller_Record_Abstract
{

    /**
     * check for container ACLs
     *
     * @var boolean
     *
     */
    protected $_doContainerACLChecks = false;

    /**
     * do right checks - can be enabled/disabled by _setRightChecks
     *
     * @var boolean
     */
    protected $_doRightChecks = false;

    /**
     * forces not to apply default filters
     * 
     * @var boolean
     */
    private $_rightsLessSearch = false;

    /**
     * holds the instance of the singleton
     *
     * @var Sipgate_Controller_Account
     */
    private static $_instance = NULL;

    /**
     * the current credential key
     * 
     * @var string
     */
    private $_credential_key = NULL;

    /**
     * duplicate check fields / if this is NULL -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = array(array('username'));
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'Sipgate';
        $this->_modelName = 'Sipgate_Model_Account';
        $this->_backend = new Sipgate_Backend_Account();
        $cfg = Tinebase_Core::getConfig();
        if($cfg->shared_credential_key) {
            $this->_credential_key = $cfg->shared_credential_key;
            if(strlen($this->_credential_key) != 24) {
                throw new Sipgate_Exception_ResolveCredentials('The shared_credential_key must have a length of 24. Your key has a length of '. strlen($this->_credential_key));
            }
        } else {
            throw new Sipgate_Exception_ResolveCredentials('You must configure a shared_credential_key in config.inc.php');
        }
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Sipgate_Controller_Account
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sipgate_Controller_Account();
        }

        return self::$_instance;
    }
    
    /**
     * @param   Array $account
     */
    public function validateAccount($account)
    {
        $b = Sipgate_Backend_Api::getInstance();
        if(empty($account['data']['username'])) {
            $b->connect($account['data']['id']);
        } else {
            $b->connect(NULL, $account['data']['username'], $account['data']['password'], $account['data']['accounttype']);
        }
        return true;
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     */
    public function resolveMultipleAccounts(Tinebase_Record_RecordSet $_records)
    {
        $accountIds = array_unique($_records->account_id);
        $accounts = $this->getMultiple($accountIds);
        foreach ($_records as $record) {
            $idx = $accounts->getIndexById($record->account_id);
            $record->account_id = $accounts[$idx];
        }
    }

    /**
     * returns resolved credentials
     * 
     * @param string $_id
     * @throws Exception
     * 
     * @return Tinebase_Model_CredentialCache
     */
    public function getResolved($_id)
    {
        $record = $this->get($_id);
        if(! $this->_resolveCredentials($record)) {
            throw new Exception('Could not resolve Credentials!');
        }
        return $record;
    }

    /**
     * resolve credentials
     * 
     * @return boolean
     */
    private function _resolveCredentials(&$_record)
    {
        Tinebase_Auth_CredentialCache::getInstance()->setCacheAdapter('Config');
        $cc = Tinebase_Auth_CredentialCache::getInstance()->get($_record->credential_id);
        $cc->key = $this->_credential_key;
        $cc->username = NULL;
        $cc->password = NULL;
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($cc);

        if(! ($cc->password || $cc->username)) {
            return false;
        }

        $_record->password = $cc->password;
        $_record->username = $cc->username;

        return true;
    }

    /**
     * inspect creation of one record
     * - add credentials and user id here
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $cId = $this->_createCredentials($_record->username, $_record->password);
        // add user id
        $_record->credential_id = $cId;
        $_record->password = NULL;
        // username saved md5, so uniquity-check is possible
        $_record->username = md5($_record->username);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        // If password has changed
        if(!empty($_record->password)) {
            if(empty($_record->username)) {
                $this->_resolveCredentials($_oldRecord);
                $newCacheId = $this->_createCredentials($_oldRecord->username, $_record->password);
                $_record->username = md5($_oldRecord->username);
            } else {
                $newCacheId = $this->_createCredentials($_record->username, $_record->password);
            }
            $_record->credential_id = $newCacheId;
            // If password has not changed
        } else {
            $this->_resolveCredentials($_oldRecord);
            $_record->username = md5($_oldRecord->username);
            $_record->password = NULL;
            $_record->credential_id = $_oldRecord->credential_id;
        }
        $lines = new Tinebase_Record_RecordSet('Sipgate_Model_Line');
        foreach($_record->lines as $lineArray) {
            Sipgate_Controller_Line::getInstance()->update(new Sipgate_Model_Line($lineArray));
        }
    }

    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
        if(!is_array($_ids)) {
            $_ids = array($_ids);
        }
        $filter = new Sipgate_Model_LineFilter(array(), 'OR');
        
        foreach($_ids as $id) {
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'account_id', 'operator' => 'equals', 'value' => $id)));
        }
        Sipgate_Controller_Line::getInstance()->deleteByFilter($filter);
        return $_ids;
    }

    /**
     * create account credentials and return new credentials id
     *
     * @param string $_username
     * @param string $_password
     * @return boolean
     */
    protected function _createCredentials($_username = NULL, $_password = NULL)
    {
        Tinebase_Auth_CredentialCache::getInstance()->setCacheAdapter('Config');
        $cc = new Tinebase_Model_CredentialCache(array(
            'username' => $_username,
            'password' => $_password,
        ));

        $ac = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials(
            $cc->username,
            $cc->password,
            $this->_credential_key,
            TRUE
        );

        return $ac->getId();
    }

    /**
     * the method without rights and justice
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Record_Interface $_pagination
     * @param unknown_type $_getRelations
     * @param unknown_type $_onlyIds
     * @param unknown_type $_action
     */
    public function searchRightsLess(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get') {
        $this->_rightsLessSearch = true;
        $ret = $this->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        $this->_rightsLessSearch = false;
        return $ret;
    }
    /**
     * adds default filter on search
     * @see Tinebase_Controller_Record_Abstract::_addDefaultFilter()
     */
    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        if($this->_rightsLessSearch || Tinebase_Core::getUser()->hasRight('Sipgate', 'admin')) {
            return;
        }
        if(!Tinebase_Core::getUser()->hasRight('Sipgate', Sipgate_Acl_Rights::MANAGE_ACCOUNTS)) {
            throw new Tinebase_Exception_AccessDenied('You don\'t have insufficient permissions to manage accounts!');
        }

        $fg = new Sipgate_Model_AccountFilter(array(), 'OR');
        if (Tinebase_Core::getUser()->hasRight('Sipgate', Sipgate_Acl_Rights::MANAGE_PRIVATE_ACCOUNTS)) {
            $fg1 = new Sipgate_Model_AccountFilter(array(), 'AND');
            $fg1->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'type', 'operator' => 'equals', 'value' => 'private')));
            $fg1->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'created_by', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())));
            $fg->addFilterGroup($fg1);
            $_filter->addFilterGroup($fg);
        }

        if (Tinebase_Core::getUser()->hasRight('Sipgate', Sipgate_Acl_Rights::MANAGE_SHARED_ACCOUNTS)) {
            $fg->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'type', 'operator' => 'equals', 'value' => 'shared')));
        }
    }
}
