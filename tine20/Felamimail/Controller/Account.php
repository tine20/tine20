<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add default account pref
 * @todo        add acl?
 */

/**
 * Account controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Account extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * holdes the instance of the singleton
     *
     * @var Felamimail_Controller_Account
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_modelName = 'Felamimail_Model_Account';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new Felamimail_Backend_Account();
        
        $this->_currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Account
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Account();
        }
        
        return self::$_instance;
    }

    /******************************** overwritten funcs *********************************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        $result = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds);
        
        // check preference/config if we should add default account with tine user credentials or from config.inc.php 
        $this->_addDefaultAccount($result);
        
        return $result;
    }

    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter) 
    {
        $count = parent::searchCount($_filter);        
        if (isset(Tinebase_Core::getConfig()->imap)) {
            $count++;
        }
        return $count;
    }
    
    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @return Tinebase_Record_Interface
     */
    public function get($_id, $_containerId = NULL)
    {
        if ($_id === Felamimail_Model_Account::DEFAULT_ACCOUNT_ID) {
            if (! isset(Tinebase_Core::getConfig()->imap)) {
                throw new Felamimail_Exception('No default imap account defined in config.inc.php!');
            }
            
            // get account data from config file    
            $record = new Felamimail_Model_Account(Tinebase_Core::getConfig()->imap->toArray());
        } else {
            $record = parent::get($_id, $_containerId);
        }
        
        return $record;    
    }
    
    /**
     * add default account with tine user credentials or from config.inc.php 
     *
     * @param Tinebase_Record_RecordSet $_accounts
     * 
     * @todo finish implementation
     * @todo get default account data from preferences?
     * @todo encrypt password
     */
    protected function _addDefaultAccount($_accounts)
    {
        // add account from config.inc.php if available
        if (isset(Tinebase_Core::getConfig()->imap) && Tinebase_Core::getConfig()->imap->useAsDefault) {
            try {
                $defaultAccount = new Felamimail_Model_Account(
                    Tinebase_Core::getConfig()->imap->toArray()
                );
                $defaultAccount->setId('default');
                $_accounts->addRecord($defaultAccount);
            } catch (Tinebase_Exception $e) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . $e->getMessage());
            }
        } else if (count($_accounts) == 0 && Tinebase_Core::getPreference('Felamimail')->userEmailAccount) {
            $defaultAccount = new Felamimail_Model_Account(Tinebase_Core::getConfig()->imap->toArray());
            
            $fullUser = Tinebase_User::getInstance()->getFullUserById($this->_currentAccount->getId());
            $defaultAccount->user = $fullUser->accountLoginName;
            //$defaultAccount->password = Tinebase_User::getInstance()->
            //$userCred = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE);
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($userCred, true));
        }
    }
}
