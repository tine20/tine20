<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Voipmanager_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * add the asterisk_peers table
     */    
    public function update_1()
    {
        $tableDefinition = "  
        <table>
            <name>asterisk_peers</name>
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
                    <name>name</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>accountcode</name>
                    <type>text</type>
                    <length>20</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>amaflags</name>
                    <type>text</type>
                    <length>13</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>callgroup</name>
                    <type>text</type>
                    <length>10</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>callerid</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>canreinvite</name>
                    <type>enum</type>
                    <value>yes</value>
                    <value>no</value>
                    <default>yes</default>
                </field>
                <field>
                    <name>context</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>defaultip</name>
                    <type>text</type>
                    <length>15</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>dtmfmode</name>
                    <type>enum</type>
                    <value>inband</value>
                    <value>info</value>
                    <value>rfc2833</value>
                    <default>rfc2833</default>
                </field>
                <field>
                    <name>fromuser</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>fromdomain</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>fullcontact</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>host</name>
                    <type>text</type>
                    <length>31</length>
                    <default>dynamic</default>
                </field>
                <field>
                    <name>insecure</name>
                    <type>enum</type>
                    <value>very</value>
                    <value>yes</value>
                    <value>no</value>
                    <value>invite</value>
                    <value>port</value>
                    <default>no</default>
                </field>
                <field>
                    <name>language</name>
                    <type>text</type>
                    <length>2</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>mailbox</name>
                    <type>text</type>
                    <length>50</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>md5secret</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>nat</name>
                    <type>enum</type>
                    <value>yes</value>
                    <value>no</value>
                    <default>no</default>
                </field>
                <field>
                    <name>deny</name>
                    <type>text</type>
                    <length>95</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>permit</name>
                    <type>text</type>
                    <length>95</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>mask</name>
                    <type>text</type>
                    <length>95</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>pickupgroup</name>
                    <type>text</type>
                    <length>10</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>port</name>
                    <type>text</type>
                    <length>5</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>qualify</name>
                    <type>enum</type>
                    <value>yes</value>
                    <value>no</value>
                    <default>no</default>
                </field>
                <field>
                    <name>restrictcid</name>
                    <type>text</type>
                    <length>1</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>rtptimeout</name>
                    <type>text</type>
                    <length>3</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>rtpholdtimeout</name>
                    <type>text</type>
                    <length>3</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>secret</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>type</name>
                    <type>enum</type>
                    <value>friend</value>
                    <value>user</value>
                    <value>peer</value>
                    <default>friend</default>
                </field>
                <field>
                    <name>username</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>disallow</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>allow</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>musiconhold</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>regseconds</name>
                    <type>integer</type>
                    <length>11</length>
                    <default>0</default>
                </field>
                <field>
                    <name>ipaddr</name>
                    <type>text</type>
                    <length>15</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>regexten</name>
                    <type>text</type>
                    <length>80</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>cancallforward</name>
                    <type>enum</type>
                    <value>yes</value>
                    <value>no</value>
                    <default>yes</default>
                </field>
                <field>
                    <name>setvar</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>notifyringing</name>
                    <type>enum</type>
                    <value>yes</value>
                    <value>no</value>
                    <default>yes</default>
                </field>
                <field>
                    <name>useclientcode</name>
                    <type>enum</type>
                    <value>yes</value>
                    <value>no</value>
                    <default>yes</default>
                </field>
                <field>
                    <name>authuser</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>call-limit</name>
                    <type>integer</type>
                    <length>11</length>
                    <default>5</default>
                </field>
                <field>
                    <name>busy-level</name>
                    <type>integer</type>
                    <length>11</length>
                    <default>1</default>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Voipmanager', '0.2');
    }    

    /**
     * add the snom_lines table
     */    
    public function update_2()
    {
        $tableDefinition = "  
        <table>
            <name>snom_lines</name>
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
                    <name>snomphone_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>asteriskline_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>linenumber</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>lineactive</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>idletext</name>
                    <type>text</type>
                    <length>40</length>
                </field>                
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>snom_lines_snomphone_id</name>
                    <field>
                        <name>snomphone_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>snom_phones</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>   
                <index>
                    <name>snom_lines_asteriskline_id</name>
                    <field>
                        <name>asteriskline_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>asterisk_peers</table>
                        <field>id</field>
                    </reference>
                </index>   
            </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Voipmanager', '0.3');
    }    
    
    
    /**
     * add the asterisk_context table
     */    
    public function update_3()
    {
        $tableDefinition = "  
            <table>
                <name>asterisk_context</name>
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
                        <name>name</name>
                        <type>text</type>
                        <length>150</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>description</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>true</notnull>
                    </field>            
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                </declaration>
            </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Voipmanager', '0.4');
    }  
    
    
    
   /**
     * add the asterisk_voicemail table
     */    
    public function update_4()
    {
        $tableDefinition = "
        <table>
            <name>asterisk_voicemail</name>
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
                    <name>context</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>mailbox</name>
                    <type>text</type>
                    <length>11</length>
                    <default>0</default>
                </field>
               <field>
                   <name>password</name>
                   <type>text</type>
                   <length>5</length>
                   <default>0</default>
               </field>
               <field>
                   <name>fullname</name>
                   <type>text</type>
                   <length>150</length>
                   <notnull>true</notnull>
               </field>
               <field>
                   <name>email</name>
                   <type>text</type>
                   <length>50</length>
                   <notnull>true</notnull>
               </field>
               <field>
                   <name>pager</name>
                   <type>text</type>
                   <length>50</length>
                   <notnull>true</notnull>
               </field>
               <field>
                   <name>tz</name>
                   <type>text</type>
                   <length>10</length>
                   <default>central</default>
               </field>
               <field>
                   <name>attach</name>
                   <type>text</type>
                   <length>4</length>
                   <default>yes</default>
               </field>
               <field>
                   <name>saycid</name>
                   <type>text</type>
                   <length>4</length>
                   <default>yes</default>
               </field>
               <field>
                   <name>dialout</name>
                   <type>text</type>
                   <length>10</length>
                   <notnull>true</notnull>
               </field>
               <field>
                   <name>callback</name>
                   <type>text</type>
                   <length>10</length>
                   <notnull>true</notnull>
               </field>
               <field>
                   <name>review</name>
                   <type>text</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>operator</name>
                   <type>text</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>envelope</name>
                   <type>text</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>sayduration</name>
                   <type>integer</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>saydurationm</name>
                   <type>integer</type>
                   <length>4</length>
                   <default>1</default>
               </field>
               <field>
                   <name>sendvoicemail</name>
                   <type>text</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>delete</name>
                   <type>text</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>nextaftercmd</name>
                   <type>text</type>
                   <length>4</length>
                   <default>yes</default>
               </field>
               <field>
                   <name>forcename</name>
                   <type>text</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>forcegreetings</name>
                   <type>text</type>
                   <length>4</length>
                   <default>no</default>
               </field>
               <field>
                   <name>hidefromdir</name>
                   <type>text</type>
                   <length>4</length>
                   <default>yes</default>
               </field>
               <index>
                   <name>id</name>
                   <primary>true</primary>
                   <field>
                       <name>id</name>
                   </field>
               </index>
               <index>
                    <name>mailbox-context</name>
                    <unique>true</unique>
                    <field>
                        <name>mailbox</name>
                    </field>
                    <field>
                        <name>context</name>
                    </field>
                </index> 
           </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);        

        $this->setApplicationVersion('Voipmanager', '0.5');
    } 
  
  
   /**
     * add the snom_settings table
     */    
    public function update_5()
    {
        $tableDefinition = "
        <table>
            <name>snom_settings</name>
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
                    <name>name</name>
                    <type>text</type>
                    <length>150</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field> 
                <field>
                    <name>web_language</name>
                    <type>enum</type>
                    <value>English</value>
                    <value>Deutsch</value>
                    <value>Espanol</value>
                    <value>Francais</value>
                    <value>Italiano</value>
                    <value>Nederlands</value>
                    <value>Portugues</value>
                    <value>Suomi</value>
                    <value>Svenska</value>
                    <value>Dansk</value>
                    <value>Norsk</value>
                    <default>English</default>
                </field>
                <field>
                    <name>language</name>
                    <type>enum</type>
                    <value>English</value>
                    <value>English(UK)</value>
                    <value>Deutsch</value>
                    <value>Espanol</value>
                    <value>Francais</value>
                    <value>Italiano</value>
                    <value>Cestina</value>
                    <value>Nederlands</value>
                    <value>Polski</value>
                    <value>Portugues</value>
                    <value>Slovencina</value>
                    <value>Suomi</value>
                    <value>Svenska</value>
                    <value>Dansk</value>
                    <value>Norsk</value>
                    <value>Japanese</value>
                    <value>Chinese</value>
                    <default>English</default>
                </field>
                <field>
                    <name>display_method</name>
                    <type>enum</type>
                    <value>full_contact</value>
                    <value>display_name</value>
                    <value>display_number</value>
                    <value>display_name_number</value>
                    <value>display_number_name</value>
                    <default>display_name</default>
                </field>
                <field>
                    <name>mwi_notification</name>
                    <type>enum</type>
                    <value>silent</value>
                    <value>beep</value>
                    <value>reminder</value>
                    <default>silent</default>
                </field>
                <field>
                    <name>mwi_dialtone</name>
                    <type>enum</type>
                    <value>normal</value>
                    <value>stutter</value>
                    <default>stutter</default>
                </field>
                <field>
                    <name>headset_device</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>headset_rj</value>
                    <default>none</default>
                </field>
                <field>
                    <name>with_flash</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>
                <field>
                    <name>message_led_other</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>
                <field>
                    <name>global_missed_counter</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>
                <field>
                    <name>scroll_outgoing</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>
                <field>
                    <name>show_local_line</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>
                <field>
                    <name>show_call_status</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>
                <field>
                    <name>redirect_event</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>all</value>
                    <value>busy</value>
                    <value>time</value>            
                    <default>none</default>
                </field>
                <field>
                    <name>redirect_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_always_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_always_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time</name>
                    <type>integer</type>
                    <length>255</length>
                    <notnull>false</notnull>    
                </field>                
                <field>
                    <name>redirect_time_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>dnd_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>dnd_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>ringer_headset_device</name>
                    <type>enum</type>
                    <value>speaker</value>
                    <value>headset</value>
                    <default>speaker</default>
                </field>
                <field>
                    <name>ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>
                    <name>alert_internal_ring_text</name>
                    <type>text</type>
                    <length>255</length>
                    <default>alert-internal</default>
                </field>
                <field>
                    <name>alert_internal_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>                    
                <field>
                    <name>alert_external_ring_text</name>
                    <type>text</type>
                    <length>255</length>
                    <default>alert-external</default>
                </field>
                <field>
                    <name>alert_external_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>                    
                <field>
                    <name>alert_group_ring_text</name>                    
                    <type>text</type>
                    <length>255</length>
                    <default>alert-group</default>                    
                </field>
                <field>
                    <name>alert_group_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>friends_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>family_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>colleagues_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>vip_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>
                    <name>custom_melody_url</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>                    
                <field>
                    <name>auto_connect_indication</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <notnull>false</notnull>
                </field>                   
                <field>
                    <name>auto_connect_type</name>
                    <type>enum</type>
                    <value>auto_connect_type_handsfree</value>
                    <value>auto_connect_type_handset</value>
                    <value>auto_connect_type_headset</value>
                    <default>auto_connect_type_handsfree</default>
                </field>
                <field>
                    <name>privacy_out</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>             
                <field>
                    <name>privacy_in</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>             
                <field>
                    <name>presence_timeout</name>
                    <type>integer</type>
                    <length>20</length>
                    <default>15</default>
                </field>
                <field>
                    <name>enable_keyboard_lock</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>            
                <field>
                    <name>keyboard_lock</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>     
                <field>
                    <name>keyboard_lock_pw</name>       
                    <type>integer</type>
                    <length>50</length>
                    <default>15</default>
                </field>     
                <field>
                    <name>keyboard_lock_emergency</name>
                    <type>text</type>
                    <length>255</length>
                    <default>911 112 110 999</default>                                                       
                </field>
                <field>
                    <name>emergency_proxy</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>                                                       
                </field>  
                <field>
                    <name>call_waiting</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>visual</value>
                    <value>ringer</value>
                    <value>off</value>
                    <default>off</default>
                </field>     
                <field>
                    <name>web_language_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>language_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>display_method_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>tone_scheme_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>mwi_notification_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>mwi_dialtone_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>headset_device_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>message_led_other_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>global_missed_counter_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>scroll_outgoing_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>show_local_line_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>show_call_status_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_event_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_number_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_time_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>   
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 

        $this->_backend->createTable($table);        
        $this->setApplicationVersion('Voipmanager', '0.6');
    }   

   /**
     * rename asterisk_peers to asterisk_sip_peers
     */    
    public function update_6()
    {
        $this->renameTable('asterisk_peers', 'asterisk_sip_peers');
        
        $this->setApplicationVersion('Voipmanager', '0.7');
    }

   /**
     * update snom_location
     */    
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'tone_scheme';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('AUS', 'AUT', 'CHN', 'DNK', 'FRA', 'GER', 'GBR', 'IND', 'ITA', 'JPN', 'MEX', 'NLD', 'NOR', 'NZL', 'ESP', 'SWE', 'SWI', 'USA');
        
        $this->_backend->addCol('snom_location', $declaration);        


        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'date_us_format';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('on', 'off');
        
        $this->_backend->addCol('snom_location', $declaration);                
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'time_24_format';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('on', 'off');
        
        $this->_backend->addCol('snom_location', $declaration);                 
        
        $this->setApplicationVersion('Voipmanager', '0.8');               

    }
 
 
    /**
     * rebuild snom_settings
     */    
    public function update_8()
    {   	
		
        $tableDefinition = "
          <table>
            <name>snom_settings</name>
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
                    <name>name</name>
                    <type>text</type>
                    <length>150</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field> 
                <field>
                    <name>web_language</name>
                    <type>enum</type>
                    <value>English</value>
                    <value>Deutsch</value>
                    <value>Espanol</value>
                    <value>Francais</value>
                    <value>Italiano</value>
                    <value>Nederlands</value>
                    <value>Portugues</value>
                    <value>Suomi</value>
                    <value>Svenska</value>
                    <value>Dansk</value>
                    <value>Norsk</value>
                    <default>English</default>
                </field>
                <field>
                    <name>language</name>
                    <type>enum</type>
                    <value>English</value>
                    <value>English(UK)</value>
                    <value>Deutsch</value>
                    <value>Espanol</value>
                    <value>Francais</value>
                    <value>Italiano</value>
                    <value>Cestina</value>
                    <value>Nederlands</value>
                    <value>Polski</value>
                    <value>Portugues</value>
                    <value>Slovencina</value>
                    <value>Suomi</value>
                    <value>Svenska</value>
                    <value>Dansk</value>
                    <value>Norsk</value>
                    <value>Japanese</value>
                    <value>Chinese</value>
                    <default>English</default>
                </field>
                <field>
                    <name>display_method</name>
                    <type>enum</type>
                    <value>full_contact</value>
                    <value>display_name</value>
                    <value>display_number</value>
                    <value>display_name_number</value>
                    <value>display_number_name</value>
                    <default>display_name</default>
                </field>
                <field>
                    <name>mwi_notification</name>
                    <type>enum</type>
                    <value>silent</value>
                    <value>beep</value>
                    <value>reminder</value>
                    <default>silent</default>
                </field>
                <field>
                    <name>mwi_dialtone</name>
                    <type>enum</type>
                    <value>normal</value>
                    <value>stutter</value>
                    <default>stutter</default>
                </field>
                <field>
                    <name>headset_device</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>headset_rj</value>
                    <default>none</default>
                </field>
                <field>
                    <name>with_flash</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>message_led_other</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>global_missed_counter</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>scroll_outgoing</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>show_local_line</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>show_call_status</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_event</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>all</value>
                    <value>busy</value>
                    <value>time</value>            
                    <default>none</default>
                </field>
                <field>
                    <name>redirect_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_always_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_always_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time</name>
                    <type>integer</type>
                    <length>255</length>
                    <notnull>false</notnull>    
                </field>                
                <field>
                    <name>redirect_time_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>dnd_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>dnd_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>ringer_headset_device</name>
                    <type>enum</type>
                    <value>speaker</value>
                    <value>headset</value>
                    <default>speaker</default>
                </field>
                <field>
                    <name>ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>
                    <name>alert_internal_ring_text</name>
                    <type>text</type>
                    <length>255</length>
                    <default>alert-internal</default>
                </field>
                <field>
                    <name>alert_internal_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>                    
                <field>
                    <name>alert_external_ring_text</name>
                    <type>text</type>
                    <length>255</length>
                    <default>alert-external</default>
                </field>
                <field>
                    <name>alert_external_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>                    
                <field>
                    <name>alert_group_ring_text</name>                    
                    <type>text</type>
                    <length>255</length>
                    <default>alert-group</default>                    
                </field>
                <field>
                    <name>alert_group_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>friends_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>family_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>colleagues_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>vip_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>
                    <name>custom_melody_url</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>                    
                <field>
                    <name>auto_connect_indication</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>                   
                <field>
                    <name>auto_connect_type</name>
                    <type>enum</type>
                    <value>auto_connect_type_handsfree</value>
                    <value>auto_connect_type_handset</value>
                    <value>auto_connect_type_headset</value>
                    <default>auto_connect_type_handsfree</default>
                </field>
                <field>
                    <name>privacy_out</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>             
                <field>
                    <name>privacy_in</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>             
                <field>
                    <name>presence_timeout</name>
                    <type>integer</type>
                    <length>20</length>
                    <default>15</default>
                </field>
                <field>
                    <name>enable_keyboard_lock</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>            
                <field>
                    <name>keyboard_lock</name>
                    <type>enum</type>
                    <value>true</value>
                    <value>false</value>
                    <notnull>false</notnull>
                </field>     
                <field>
                    <name>keyboard_lock_pw</name>       
                    <type>integer</type>
                    <length>50</length>
                    <default>15</default>
                </field>     
                <field>
                    <name>keyboard_lock_emergency</name>
                    <type>text</type>
                    <length>255</length>
                    <default>911 112 110 999</default>                                                       
                </field>
                <field>
                    <name>emergency_proxy</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>                                                       
                </field>  
                <field>
                    <name>call_waiting</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>visual</value>
                    <value>ringer</value>
                    <value>off</value>
                    <default>off</default>
                </field>     
                <field>
                    <name>web_language_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>language_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>display_method_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>tone_scheme_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>mwi_notification_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>mwi_dialtone_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>headset_device_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>message_led_other_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>global_missed_counter_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>scroll_outgoing_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>show_local_line_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>show_call_status_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_event_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_number_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_time_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>   
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 

        $this->_backend->dropTable('snom_settings');
        $this->_backend->createTable($table);        
        $this->setApplicationVersion('Voipmanager', '0.9');
    }  
 
  /**
     * update snom_location
     */    
    public function update_9()
    {
    	
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'attach';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        


        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'saycid';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'review';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'operator';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'envelope';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'sayduration';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'sendvoicemail';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'delete';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'nextaftercmd';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'forcename';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'forcegreetings';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        

        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'hidefromdir';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('true', 'false');
        
        $this->_backend->alterCol('asterisk_voicemail', $declaration);        
                                                                                
        $this->setApplicationVersion('Voipmanager', '0.10');               
    } 
 

    /**
     * rebuild snom_settings
     */    
    public function update_10()
    {
        
        $tableDefinition = "
          <table>
            <name>snom_settings</name>
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
                    <name>name</name>
                    <type>text</type>
                    <length>150</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field> 
                <field>
                    <name>web_language</name>
                    <type>enum</type>
                    <value>English</value>
                    <value>Deutsch</value>
                    <value>Espanol</value>
                    <value>Francais</value>
                    <value>Italiano</value>
                    <value>Nederlands</value>
                    <value>Portugues</value>
                    <value>Suomi</value>
                    <value>Svenska</value>
                    <value>Dansk</value>
                    <value>Norsk</value>
                    <default>English</default>
                </field>
                <field>
                    <name>language</name>
                    <type>enum</type>
                    <value>English</value>
                    <value>English(UK)</value>
                    <value>Deutsch</value>
                    <value>Espanol</value>
                    <value>Francais</value>
                    <value>Italiano</value>
                    <value>Cestina</value>
                    <value>Nederlands</value>
                    <value>Polski</value>
                    <value>Portugues</value>
                    <value>Slovencina</value>
                    <value>Suomi</value>
                    <value>Svenska</value>
                    <value>Dansk</value>
                    <value>Norsk</value>
                    <value>Japanese</value>
                    <value>Chinese</value>
                    <default>English</default>
                </field>
                <field>
                    <name>display_method</name>
                    <type>enum</type>
                    <value>full_contact</value>
                    <value>display_name</value>
                    <value>display_number</value>
                    <value>display_name_number</value>
                    <value>display_number_name</value>
                    <default>display_name</default>
                </field>
                <field>
                    <name>mwi_notification</name>
                    <type>enum</type>
                    <value>silent</value>
                    <value>beep</value>
                    <value>reminder</value>
                    <default>silent</default>
                </field>
                <field>
                    <name>mwi_dialtone</name>
                    <type>enum</type>
                    <value>normal</value>
                    <value>stutter</value>
                    <default>stutter</default>
                </field>
                <field>
                    <name>headset_device</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>headset_rj</value>
                    <default>none</default>
                </field>
                <field>
                    <name>message_led_other</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>
                <field>
                    <name>global_missed_counter</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>
                <field>
                    <name>scroll_outgoing</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>
                <field>
                    <name>show_local_line</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>
                <field>
                    <name>show_call_status</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>
                <field>
                    <name>redirect_event</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>all</value>
                    <value>busy</value>
                    <value>time</value>            
                    <default>none</default>
                </field>
                <field>
                    <name>redirect_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_always_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_always_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_busy_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time</name>
                    <type>integer</type>
                    <length>255</length>
                    <notnull>false</notnull>    
                </field>                
                <field>
                    <name>redirect_time_number</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>redirect_time_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>dnd_on_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>dnd_off_code</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>ringer_headset_device</name>
                    <type>enum</type>
                    <value>speaker</value>
                    <value>headset</value>
                    <default>speaker</default>
                </field>
                <field>
                    <name>ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>
                    <name>alert_internal_ring_text</name>
                    <type>text</type>
                    <length>255</length>
                    <default>alert-internal</default>
                </field>
                <field>
                    <name>alert_internal_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>                    
                <field>
                    <name>alert_external_ring_text</name>
                    <type>text</type>
                    <length>255</length>
                    <default>alert-external</default>
                </field>
                <field>
                    <name>alert_external_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>                    
                <field>
                    <name>alert_group_ring_text</name>                    
                    <type>text</type>
                    <length>255</length>
                    <default>alert-group</default>                    
                </field>
                <field>
                    <name>alert_group_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>friends_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>family_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>colleagues_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>                    
                    <name>vip_ring_sound</name>
                    <type>enum</type>
                    <value>Ringer1</value>
                    <value>Ringer2</value>
                    <value>Ringer3</value>
                    <value>Ringer4</value>
                    <value>Ringer5</value>
                    <value>Ringer6</value>
                    <value>Ringer7</value>
                    <value>Ringer8</value>
                    <value>Ringer9</value>
                    <value>Ringer10</value>
                    <value>Silent</value>
                    <default>Ringer1</default>
                </field>
                <field>
                    <name>custom_melody_url</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>                    
                <field>
                    <name>auto_connect_indication</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                </field>                   
                <field>
                    <name>auto_connect_type</name>
                    <type>enum</type>
                    <value>auto_connect_type_handsfree</value>
                    <value>auto_connect_type_handset</value>
                    <value>auto_connect_type_headset</value>
                    <default>auto_connect_type_handsfree</default>
                </field>
                <field>
                    <name>privacy_out</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>             
                <field>
                    <name>privacy_in</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>             
                <field>
                    <name>presence_timeout</name>
                    <type>integer</type>
                    <length>20</length>
                    <default>15</default>
                </field>
                <field>
                    <name>enable_keyboard_lock</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>on</default>
                </field>            
                <field>
                    <name>keyboard_lock</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>off</value>
                    <default>off</default>
                </field>     
                <field>
                    <name>keyboard_lock_pw</name>       
                    <type>integer</type>
                    <length>50</length>
                    <default>15</default>
                </field>     
                <field>
                    <name>keyboard_lock_emergency</name>
                    <type>text</type>
                    <length>255</length>
                    <default>911 112 110 999</default>                                                       
                </field>
                <field>
                    <name>emergency_proxy</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>                                                       
                </field>  
                <field>
                    <name>call_waiting</name>
                    <type>enum</type>
                    <value>on</value>
                    <value>visual</value>
                    <value>ringer</value>
                    <value>off</value>
                    <default>off</default>
                </field>     
                <field>
                    <name>web_language_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>language_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>display_method_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>tone_scheme_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>mwi_notification_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>mwi_dialtone_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>headset_device_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>message_led_other_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>global_missed_counter_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>scroll_outgoing_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>show_local_line_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>show_call_status_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_event_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_number_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>     
                <field>
                    <name>redirect_time_writable</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>   
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>";

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 

        $this->_backend->dropTable('snom_settings');
        $this->_backend->createTable($table);        
        $this->setApplicationVersion('Voipmanager', '0.11');
    }  
          
    /**
     * create asterisk_meetme
     */    
    public function update_11()
    {         
        $tableDefinition = "
          <table>
                <name>asterisk_meetme</name>
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
                        <name>confno</name>
                        <type>text</type>
                        <length>80</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>pin</name>
                        <type>text</type>
                        <length>80</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>adminpin</name>
                        <type>text</type>
                        <length>80</length>
                        <notnull>true</notnull>
                    </field>                                
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                </declaration>
            </table>" ;

        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 

        $this->_backend->createTable($table);        
        $this->setApplicationVersion('Voipmanager', '0.12');
    }         
          
   /**
     * update snom_location
     */    
    public function update_12()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'web_language';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('English', 'Deutsch', 'Espanol', 'Francais', 'Italiano', 'Nederlands', 'Portugues', 'Suomi', 'Svenska', 'Dansk', 'Norsk');        
        $this->_backend->addCol('snom_phones', $declaration);        

        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'language';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('English', 'English(UK)', 'Deutsch', 'Espanol', 'Francais', 'Italiano', 'Cestina', 'Nederlands', 'Polski', 'Portugues', 'Slovencina', 'Suomi', 'Svenska', 'Dansk', 'Norsk', 'Japanese', 'Chinese');        
        $this->_backend->addCol('snom_phones', $declaration);        

        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'display_method';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('full_contact', 'display_name', 'display_number', 'display_name_number', 'display_number_name');        
        $this->_backend->addCol('snom_phones', $declaration);        

        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'mwi_notification';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('silent', 'beep', 'reminder');        
        $this->_backend->addCol('snom_phones', $declaration);        

        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'mwi_dialtone';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('normal', 'stutter');        
        $this->_backend->addCol('snom_phones', $declaration);       

        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'headset_device';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('none', 'headset_rj');        
        $this->_backend->addCol('snom_phones', $declaration);       

        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'message_led_other';
        $declaration->type      = 'int';
        $declaration->notnull   = 'false';
        $this->_backend->addCol('snom_phones', $declaration);       

        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'global_missed_counter';
        $declaration->type      = 'int';
        $declaration->notnull   = 'false';
        $this->_backend->addCol('snom_phones', $declaration);       
        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'scroll_outgoing';
        $declaration->type      = 'int';
        $declaration->notnull   = 'false';
        $this->_backend->addCol('snom_phones', $declaration);       
        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'show_local_line';
        $declaration->type      = 'int';
        $declaration->notnull   = 'false';
        $this->_backend->addCol('snom_phones', $declaration);       
        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'show_call_status';
        $declaration->type      = 'int';
        $declaration->notnull   = 'false';
        $this->_backend->addCol('snom_phones', $declaration);                               
        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'redirect_event';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('none', 'all', 'busy', 'time');        
        $this->_backend->addCol('snom_phones', $declaration);        
 
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'redirect_number';
        $declaration->type      = 'varchar';
        $declaration->notnull   = 'false';
        $this->_backend->addCol('snom_phones', $declaration);       
        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'redirect_time';
        $declaration->type      = 'int';
        $declaration->notnull   = 'false';
        $this->_backend->addCol('snom_phones', $declaration);                               
        
        $declaration = new Setup_Backend_Schema_Field_Xml();
        $declaration->name      = 'call_waiting';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'false';
        $declaration->value     = array('on', 'visual', 'ringer', 'off');        
        $this->_backend->addCol('snom_phones', $declaration);         
        
        $this->setApplicationVersion('Voipmanager', '0.13');               

    }          
          
    
}