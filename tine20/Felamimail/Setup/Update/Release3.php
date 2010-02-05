<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Felamimail_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * update function (-> 3.1)
     * - add type to account table (user/system)
     */    
    public function update_0()
    {
        $this->_backend->addCol('felamimail_account', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>type</name>
                <type>text</type>
                <length>20</length>
                <default>user</default>
            </field>'
        ));
        
        $this->setTableVersion('felamimail_account', '9');
        $this->setApplicationVersion('Felamimail', '3.1');
    }

    /**
     * update function (-> 3.2)
     * - check all users with 'userEmailAccount' and update their accounts / preferences
     */    
    public function update_1()
    {
        // update account types for users with userEmailAccount preference
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        
        if (array_key_exists('host', $imapConfig)) {
            $accounts = Felamimail_Controller_Account::getInstance()->getAll();
            $accountBackend = new Felamimail_Backend_Account();
            foreach ($accounts as $account) {
                if (Tinebase_Core::getPreference('Felamimail')->getValueForUser('userEmailAccount', $account->user_id)) {
                    $user = Tinebase_User::getInstance()->getFullUserById($account->user_id);
                    // account email == user->emailAddress && account->host == system account host -> type = system
                    if ($account->email == $user->accountEmailAddress && $account->host == $imapConfig['host']) {
                        $account->type = Felamimail_Model_Account::TYPE_SYSTEM;
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Switching to system account: ' . $account->name);
                        $accountBackend->update($account);
                    }
                }
            }
        }
        
        // rename preference
        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . "preferences SET name = 'useSystemAccount' WHERE name = 'userEmailAccount'");
        
        $this->setApplicationVersion('Felamimail', '3.2');
    }
    
    /**
     * update function (-> 3.3)
     * - renamed config useAsDefault -> useSystemAccount
     */    
    public function update_2()
    {
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        if (array_key_exists('useAsDefault', $imapConfig)) {
            $imapConfig['useSystemAccount'] = $imapConfig['useAsDefault'];
            unset($imapConfig['useAsDefault']);
            Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::IMAP, Zend_Json::encode($imapConfig));
        }
        $this->setApplicationVersion('Felamimail', '3.3');
    }
}
