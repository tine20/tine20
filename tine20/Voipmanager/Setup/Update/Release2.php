<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

class Voipmanager_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * rename username to defaultuser
     * add auto to dtmfmode enum
     * add column regserver, useragent and lastms
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>dtmfmode</name>
                <type>enum</type>
                <value>inband</value>
                <value>info</value>
                <value>rfc2833</value>
                <value>auto</value>
                <default>rfc2833</default>
            </field>');
        $this->_backend->alterCol('asterisk_sip_peers', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>defaultuser</name>
                <type>text</type>
                <length>80</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('asterisk_sip_peers', $declaration, 'username');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>regserver</name>
                <type>text</type>
                <length>254</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>useragent</name>
                <type>text</type>
                <length>254</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lastms</name>
                <type>integer</type>
                <length>11</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $this->setApplicationVersion('Voipmanager', '2.1');
    }    
    
    /**
     * add the asterisk_redirects table
     */    
    public function update_1()
    {
        $tableDefinition = '        
            <table>
                <name>asterisk_redirects</name>
                <engine>InnoDB</engine>
                <charset>utf8</charset>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>sippeer_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>cfi_mode</name>
                        <type>enum</type>
                        <value>off</value>
                        <value>number</value>
                        <value>voicemail</value>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>cfi_number</name>
                        <type>text</type>
                        <length>80</length>
                    </field>
                    <field>
                        <name>cfb_mode</name>
                        <type>enum</type>
                        <value>off</value>
                        <value>number</value>
                        <value>voicemail</value>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>cfb_number</name>
                        <type>text</type>
                        <length>80</length>
                    </field>
                    <field>
                        <name>cfd_mode</name>
                        <type>enum</type>
                        <value>off</value>
                        <value>number</value>
                        <value>voicemail</value>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>cfd_number</name>
                        <type>text</type>
                        <length>80</length>
                    </field>
                    <field>
                        <name>cfd_time</name>
                        <type>integer</type>
                        <length>11</length>
                    </field>                                
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>asterisk_redirects::sippeer_id--asterisk_sip_peers::id</name>
                        <field>
                            <name>sippeer_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>asterisk_sip_peers</table>
                            <field>id</field>
                        </reference>
                    </index>   
                </declaration>
            </table>
        ';

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Voipmanager', '2.2');
    }
           
    /**
     * rename context to context_id and add foreign key for context in voicemail table
     * add auto to dtmfmode enum
     * add column regserver, useragent and lastms
     */
    public function update_2()
    {        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>context_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->addCol('asterisk_voicemail', $declaration);
        
        $select = $this->_db->select()
            ->distinct()
            ->from(SQL_TABLE_PREFIX . 'asterisk_context', array('id', 'name'));
        $contextes = $this->_db->fetchAll($select);
        
        foreach($contextes as $context) {
            $this->_db->update(
                SQL_TABLE_PREFIX . 'asterisk_voicemail', 
                array('context_id' => $context['id']), 
                $this->_db->quoteInto($this->_db->quoteIdentifier('context') .' = ?', $context['name'])
            );
        }
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>asterisk_voicemail::context_id--asterisk_context::id</name>
                <field>
                    <name>context_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>asterisk_context</table>
                    <field>id</field>
                </reference>
            </index>');
        $this->_backend->addForeignKey('asterisk_voicemail', $declaration);       
        
        $this->_backend->dropIndex('asterisk_voicemail', 'mailbox-context');
        $this->_backend->dropCol('asterisk_voicemail', 'context');
        
        $this->setApplicationVersion('Voipmanager', '2.3');
    }    

    /**
     * rename context to context_id and add foreign key for context
     * add auto to dtmfmode enum
     * add column regserver, useragent and lastms
     */
    public function update_3()
    {        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>members</name>
                <type>integer</type>
            </field>');
        $this->_backend->addCol('asterisk_meetme', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>starttime</name>
                <type>datetime</type>
            </field>');
        $this->_backend->addCol('asterisk_meetme', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>endtime</name>
                <type>datetime</type>
            </field>');
        $this->_backend->addCol('asterisk_meetme', $declaration);
        
        $this->setApplicationVersion('Voipmanager', '2.4');
    }
        
    /**
     * make lastms a signed integer
     */
    public function update_4()
    {        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lastms</name>
                <type>integer</type>
                <unsigned>false</unsigned>
                <length>11</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('asterisk_sip_peers', $declaration);
        
        $this->setApplicationVersion('Voipmanager', '2.5');
    }
        
    /**
     * rename context to context_id and add foreign key for context in sippeers table
     */
    public function update_5()
    {        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>context_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $select = $this->_db->select()
            ->distinct()
            ->from(SQL_TABLE_PREFIX . 'asterisk_context', array('id', 'name'));
        $contextes = $this->_db->fetchAll($select);
        
        foreach($contextes as $context) {
            $this->_db->update(
                SQL_TABLE_PREFIX . 'asterisk_sip_peers', 
                array('context_id' => $context['id']), 
                $this->_db->quoteInto($this->_db->quoteIdentifier('context') .' = ?', $context['name'])
            );
        }
        
        // delete any phones which have no valid context_id set
        $this->_db->delete(
            SQL_TABLE_PREFIX . 'asterisk_sip_peers',
            array("context_id = ''")
        );
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>asterisk_sip_peers::context_id--asterisk_context::id</name>
                <field>
                    <name>context_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>asterisk_context</table>
                    <field>id</field>
                </reference>
            </index>');
        $this->_backend->addForeignKey('asterisk_sip_peers', $declaration);       
        
        $this->_backend->dropCol('asterisk_sip_peers', 'context');
        
        $this->setApplicationVersion('Voipmanager', '2.6');
    }
        
    /**
     * add index for sip_peers.name
     */
    public function update_6()
    {        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>name</name>
                <unique>true</unique>
                <field>
                    <name>name</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('asterisk_sip_peers', $declaration);
        
        $this->setApplicationVersion('Voipmanager', '2.7');
    }
    
    /**
     * update to 3.0
     * @return void
     */
    public function update_7()
    {
        $this->setApplicationVersion('Voipmanager', '3.0');
    }
}
