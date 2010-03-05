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

    /**
     * update function (-> 3.4)
     * - add field to felamimail_folder table for better caching 
     */    
    public function update_3()
    {
        // rename cols
        $this->_backend->alterCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>imap_uidnext</name>
                    <type>integer</type>
                    <length>64</length>
                </field>'
        ), 'uidnext');
        $this->_backend->alterCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>imap_uidvalidity</name>
                    <type>text</type>
                    <length>40</length>
                </field>'
        ), 'uidvalidity');
        $this->_backend->alterCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>imap_totalcount</name>
                    <type>integer</type>
                </field>'
        ), 'totalcount');
        $this->_backend->alterCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_unreadcount</name>
                    <type>integer</type>
                </field>'
        ), 'unreadcount');
        $this->_backend->alterCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>imap_timestamp</name>
                    <type>datetime</type>
                </field>'
        ), 'timestamp');
        $this->_backend->alterCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_status</name>
                    <type>text</type>
                    <length>40</length>
                </field>'
        ));
        $this->_backend->alterCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_job_lowestuid</name>
                    <type>integer</type>
                    <length>64</length>
                </field>'
        ), 'cache_lowest_uid');

        // add new cols
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>imap_status</name>
                    <type>text</type>
                    <length>40</length>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>imap_recentcount</name>
                    <type>integer</type>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_uidnext</name>
                    <type>integer</type>
                    <length>64</length>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_job_startuid</name>
                    <type>integer</type>
                    <length>64</length>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_totalcount</name>
                    <type>integer</type>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_recentcount</name>
                    <type>integer</type>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_timestamp</name>
                    <type>datetime</type>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_job_actions_estimate</name>
                    <type>integer</type>
                </field>'
        ));
        $this->_backend->addCol('felamimail_folder', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>cache_job_actions_done</name>
                    <type>integer</type>
                </field>'
        ));
        
        $this->setTableVersion('felamimail_folder', '5');
        $this->setApplicationVersion('Felamimail', '3.4');
    }
    
    /**
     * update function (-> 3.5)
     * - remove useSystemAccount preference
     */    
    public function update_4()
    {
        $this->_db->query('DELETE FROM ' . SQL_TABLE_PREFIX . "preferences where name = 'useSystemAccount'");
        
        $this->setApplicationVersion('Felamimail', '3.5');
    }
}
