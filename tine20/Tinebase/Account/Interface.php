<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * interface for the accounts class
 * 
 * @package     Tinebase
 * @subpackage  Account
 */
interface Tinebase_Account_Interface
{
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_Account
     */
    public function getAccounts($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Account_Model_Account');
    
    /**
     * get account by login name
     *
     * @param string $_loginName the loginname of the account
     * @return Tinebase_Account_Model_Account the account object
     *
     * @throws Tinebase_Record_Exception_NotDefined when row is empty
     */
    public function getAccountByLoginName($_loginName, $_accountClass = 'Tinebase_Account_Model_Account');
    
    /**
     * get account by accountId
     *
     * @param int $_accountId the account id
     * @return Tinebase_Account_Model_Account the account object
     */
    public function getAccountById($_accountId, $_accountClass = 'Tinebase_Account_Model_Account');
    
    /**
     * update the lastlogin time of account
     *
     * @param int $_accountId
     * @param string $_ipAddress
     * @return void
     */
    public function setLoginTime($_accountId, $_ipAddress) ;
}
