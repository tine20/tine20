<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * add imap_lastmodseq and supports_condstore
     * 
     * @see 0003730: support CONDSTORE extension for quick flag sync
     */
    public function update_0()
    {
        $this->validateTableVersion('felamimail_folder', 12);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>imap_lastmodseq</name>
                <type>integer</type>
                <length>64</length>
            </field>');
        $this->_backend->addCol('felamimail_folder', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>supports_condstore</name>
                <type>boolean</type>
                <default>null</default>
            </field>');
        $this->_backend->addCol('felamimail_folder', $declaration);
        
        $this->setTableVersion('felamimail_folder', 13);
        
        $this->setApplicationVersion('Felamimail', '8.1');
    }
    
    /**
     * add conjunction field to sieve rule to allow "anyof"-conjunction
     */
    public function update_1()
    {
        if(! $this->_backend->columnExists('conjunction', 'felamimail_sieve_rule')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                <name>conjunction</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
                <default>allof</default>
            </field>');

            $this->_backend->addCol('felamimail_sieve_rule', $declaration);
        }

        $this->setTableVersion('felamimail_sieve_rule', 3);
        $this->setApplicationVersion('Felamimail', '8.2');
    }
    
    
    /**
     * update to 8.3
     * - update 256 char fields
     * 
     * @see 0008070: check index lengths
     */
    public function update_2()
    {
        $columns = array("felamimail_account" => array(
                            "name" => "",
                            "host" => "",
                            "email" => "",
                            "from" => "",
                            "organization" => "",
                            "sent_folder" => "",
                            "trash_folder" => "",
                            "drafts_folder" => "",
                            "templates_folder" => "",
                            "ns_personal" => "",
                            "ns_other" => "",
                            "ns_shared" => "",
                            "smtp_hostname" => "",
                            "sieve_hostname" => ""
                            ),
                        "felamimail_folder" => array(
                            "localname" => "",
                            "parent" => ""
                            ),
            // NOTE: we do not update the current message cache structure as this could
            // lead to problems for large installations with lots of messages in the cache
            // TODO: find out cache size, no problems with smaller sizes
            // TODO: find out ways to improve alter table speed for big caches
            
//                         "felamimail_cache_message" => array(
//                             "content_type" => "",
//                             "body_content_type" => "",
//                             "from_email" => "",
//                             "from_name" => "",
//                             "sender" => ""
//                             ),
//                         "felamimail_cache_message_to" => array(
//                             "name" => "",
//                             "email" => ""
//                             ),
//                         "felamimail_cache_message_cc" => array(
//                             "name" => "",
//                             "email" => ""
//                             ),
//                         "felamimail_cache_message_bcc" => array(
//                             "name" => "",
//                             "email" => ""
//                             ),

                        "felamimail_sieve_rule" => array(
                            "action_type" => "",
                            "action_argument" => ""
                            ),
                        "felamimail_sieve_vacation" => array(
                            "subject" => "",
                            "from" => "",
                            "mime" => ""
                            )
                        );
        
        $this->truncateTextColumn($columns, 255);
        $this->setTableVersion('felamimail_account', 20);
        $this->setTableVersion('felamimail_folder', 14);
//         $this->setTableVersion('felamimail_cache_message', 9);
//         $this->setTableVersion('felamimail_cache_message_to', 2);
//         $this->setTableVersion('felamimail_cache_message_cc', 2);
//         $this->setTableVersion('felamimail_cache_message_bcc', 2);
        $this->setTableVersion('felamimail_sieve_rule', 3);
        $this->setTableVersion('felamimail_sieve_vacation', 3);
        $this->setApplicationVersion('Felamimail', '8.3');
    }
    
    /**
     * update to 8.4
     * - fix translation string
     *
     */
    public function update_3()
    {
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', 'All Highlighted mail');
        $this->_db->update(SQL_TABLE_PREFIX . 'filter', array(
                'name' => 'All highlighted mail',
        ), $where);
        $this->setApplicationVersion('Felamimail', '8.4');
    }
}
