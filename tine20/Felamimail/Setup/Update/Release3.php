<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Felamimail updates for version 3.x
 *
 * @package     Felamimail
 * @subpackage  Setup
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
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        
        if (array_key_exists('host', $imapConfig)) {
            $accounts = Felamimail_Controller_Account::getInstance()->getAll();
            $accountBackend = new Felamimail_Backend_Account();
            foreach ($accounts as $account) {
                try {
                    if (Tinebase_Core::getPreference('Felamimail')->getValueForUser('userEmailAccount', $account->user_id)) {
                        $user = Tinebase_User::getInstance()->getFullUserById($account->user_id);
                        // account email == user->emailAddress && account->host == system account host -> type = system
                        if ($account->email == $user->accountEmailAddress && $account->host == $imapConfig['host']) {
                            $account->type = Felamimail_Model_Account::TYPE_SYSTEM;
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Switching to system account: ' . $account->name);
                            $accountBackend->update($account);
                        }
                    }
                } catch (Exception $e) {
                    // do nothing
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
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (array_key_exists('useAsDefault', $imapConfig)) {
            $imapConfig['useSystemAccount'] = $imapConfig['useAsDefault'];
            unset($imapConfig['useAsDefault']);
            Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $imapConfig);
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
                    <name>cache_job_actions_est</name>
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
    
    /**
     * update function (-> 3.6)
     * - clear all existing folder message caches by dropping and recreating the caching tables
     */    
    public function update_5()
    {
        $this->_clearCache();
                
        $this->setApplicationVersion('Felamimail', '3.6');
    }
    
    /**
     * update function (-> 3.7)
     * - add new fields to felamimail_cache_message table 
     */    
    public function update_6()
    {
        $this->_clearCache();
        
        $newFields = array(
            '<field>
                <name>structure</name>
                <type>blob</type>
                <notnull>true</notnull>
            </field>',
            '<field>
                <name>has_attachment</name>
                <type>boolean</type>
                <default>false</default>
                <notnull>true</notnull>
            </field>',
            '<field>
                <name>text_partid</name>
                <type>text</type>
                <length>128</length>
            </field>',
            '<field>
                <name>html_partid</name>
                <type>text</type>
                <length>128</length>
            </field>',
            '<field>
                <name>priority</name>
                <type>integer</type>
            </field>'
        );
        
        foreach ($newFields as $col) {
            $this->_backend->addCol('felamimail_cache_message', new Setup_Backend_Schema_Field_Xml($col));
        }
        
        $this->setTableVersion('felamimail_cache_message', '2');
        $this->setApplicationVersion('Felamimail', '3.7');
    }
    
    /**
     * update function (-> 3.8)
     * - drop message cache 
     */    
    public function update_7()
    {
        $this->_clearCache();
        
        $this->setApplicationVersion('Felamimail', '3.8');
    }
    
    /**
     * update function (-> 3.9)
     * - add sieve config fields to account
     */    
    public function update_8()
    {
        $newFields = array(
                '<field>
                    <name>sieve_hostname</name>
                    <type>text</type>
                    <length>256</length>
                </field>',
                '<field>
                    <name>sieve_port</name>
                    <type>integer</type>
                </field>',
                '<field>
                    <name>sieve_ssl</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>TLS</value>
                </field>'
        );
        
        foreach ($newFields as $col) {
            $this->_backend->addCol('felamimail_account', new Setup_Backend_Schema_Field_Xml($col));
        }
        
        $this->setTableVersion('felamimail_account', '10');
        $this->setApplicationVersion('Felamimail', '3.9');
    }    

    /**
     * update function (-> 3.10)
     * - add sieve vacation active field to account
     */    
    public function update_9()
    {
        $newFields = array(
                '<field>
                    <name>sieve_vacation_active</name>
                    <type>boolean</type>
                </field>'
        );
        
        foreach ($newFields as $col) {
            $this->_backend->addCol('felamimail_account', new Setup_Backend_Schema_Field_Xml($col));
        }
        
        $this->setTableVersion('felamimail_account', '11');
        $this->setApplicationVersion('Felamimail', '3.10');
    }    

    /**
     * update function (-> 3.11)
     * - split from into from_email/from_name
     * - added account_id
     */    
    public function update_10()
    {
        $this->_clearCache();
        
        $newFields = array(
                '<field>
                    <name>from_email</name>
                    <type>text</type>
                    <length>256</length>
                </field>',
                '<field>
                    <name>from_name</name>
                    <type>text</type>
                    <length>256</length>
                </field>',
                '<field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>'
        );
        
        foreach ($newFields as $col) {
            $this->_backend->addCol('felamimail_cache_message', new Setup_Backend_Schema_Field_Xml($col));
        }
        
        $this->_backend->dropCol('felamimail_cache_message', 'from');
        
        $this->setTableVersion('felamimail_cache_message', '3');
        $this->setApplicationVersion('Felamimail', '3.11');
    }

    /**
     * update function (-> 3.12)
     * - added another display option (content_type) to display_format (account table)
     */    
    public function update_11()
    {
        $field = new Setup_Backend_Schema_Field_Xml( 
            '<field>
                <name>display_format</name>
                <type>text</type>
                <length>64</length>
                <default>html</default>
            </field>'
        );
        
        $this->_backend->alterCol('felamimail_account', $field);
        
        $this->setTableVersion('felamimail_account', '12');
        $this->setApplicationVersion('Felamimail', '3.12');
    }    
    
    /**
     * update function (-> 3.13)
     * - added templates/drafts folder
     */    
    public function update_12()
    {
        $fields = array(new Setup_Backend_Schema_Field_Xml( 
            '<field>
                <name>drafts_folder</name>
                <type>text</type>
                <length>64</length>
            </field>'),
            new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>templates_folder</name>
                <type>text</type>
                <length>64</length>
            </field>')
        );
        
        foreach ($fields as $field) {
            $this->_backend->addCol('felamimail_account', $field, 11);
        }
        
        $this->setTableVersion('felamimail_account', '13');
        $this->setApplicationVersion('Felamimail', '3.13');
    }
    
    /**
     * update function (-> 3.14)
     * - added body content type
     */    
    public function update_13()
    {
        $fields = array(new Setup_Backend_Schema_Field_Xml( 
            '<field>
                <name>body_content_type</name>
                <type>text</type>
                <length>256</length>
            </field>'),
        );
        
        foreach ($fields as $field) {
            $this->_backend->addCol('felamimail_cache_message', $field, 5);
        }
        
        $this->setTableVersion('felamimail_cache_message', '4');
        $this->setApplicationVersion('Felamimail', '3.14');
        $this->_clearCache();
    }

    /**
     * update function (-> 3.15)
     * - add sender property to messages
     */    
    public function update_14()
    {
        $newFields = array(
                '<field>
                    <name>sender</name>
                    <type>text</type>
                    <length>256</length>
                </field>',
        );
        
        foreach ($newFields as $col) {
            $this->_backend->addCol('felamimail_cache_message', new Setup_Backend_Schema_Field_Xml($col));
        }
        
        $this->setTableVersion('felamimail_cache_message', '5');
        $this->setApplicationVersion('Felamimail', '3.15');
    }
    
    /**
     * update function (-> 3.16)
     * - drop intelligent_folders column
     */    
    public function update_15()
    {
        $this->_backend->dropCol('felamimail_account', 'intelligent_folders');
        
        $this->setTableVersion('felamimail_account', '14');
        $this->setApplicationVersion('Felamimail', '3.16');
    }

    /**
     * update function (-> 3.17)
     * - add default favorite for all inboxes
     */    
    public function update_16()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        $myInboxPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Felamimail_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All inboxes of my email accounts",
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId(),
            'model'             => 'Felamimail_Model_MessageFilter',
            'filters'           => array(
                array('field' => 'path'    , 'operator' => 'in', 'value' => Felamimail_Model_MessageFilter::PATH_ALLINBOXES),
            )
        )));
        
        $this->setApplicationVersion('Felamimail', '3.17');
    }

    /**
     * update function (-> 3.18)
     * - add more default favorites
     */    
    public function update_17()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId(),
            'model'             => 'Felamimail_Model_MessageFilter',
        );
        
        $myUnseenPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All unread mail',
            'description'       => 'All unread mail of my email accounts',
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'notin', 'value' => Zend_Mail_Storage::FLAG_SEEN),
            )
        ))));

        $myHighlightedPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All highlighted mail',
            'description'       => 'All highlighted mail of my email accounts',
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'in', 'value' => Zend_Mail_Storage::FLAG_FLAGGED),
            )
        ))));

        $myDraftsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => 'All drafts',
            'description'       => 'All mails with the draft flag',
            'filters'           => array(
                array('field' => 'flags'    , 'operator' => 'in', 'value' => Zend_Mail_Storage::FLAG_DRAFT),
            )
        ))));
        
        $this->setApplicationVersion('Felamimail', '3.18');
    }
    
    /**
     * update function (-> 3.19)
     * - DEFAULTACCOUNT preference is personal only
     */    
    public function update_18()
    {
        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . "preferences SET personal_only = 1 WHERE name = 'defaultEmailAccount'");
        
        $this->setApplicationVersion('Felamimail', '3.19');
    }
    
    /**
     * update to 4.0
     * @return void
     */
    public function update_19()
    {
        $this->setApplicationVersion('Felamimail', '4.0');
    }
    
    /**
     * update from 3.0 -> 4.0
     * 
     * Neele release received updates up to 3.22 after branching
     * 
     * @return void
     */
    public function update_22()
    {
        $this->setApplicationVersion('Felamimail', '4.0');
    }
    
    /**
     * clear cache tables and reset folder status
     */
    protected function _clearCache()
    {
        $cachingTables = array(
            'felamimail_cache_message_bcc',
            'felamimail_cache_message_cc',
            'felamimail_cache_message_flag',
            'felamimail_cache_message_to',
            'felamimail_cache_message',
            'felamimail_folder',
        );
        
        // truncate tables (disable foreign key checks for this)
        $this->_db->query("SET AUTOCOMMIT=0");
        $this->_db->query("SET FOREIGN_KEY_CHECKS=0");
        
        foreach ($cachingTables as $table) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Truncate cache table ' . $table . ' ...');
            $this->_backend->truncateTable($table);
        }
        
        $this->_db->query("SET FOREIGN_KEY_CHECKS=1");
        $this->_db->query("SET AUTOCOMMIT=1");
    }
}
