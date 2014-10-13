<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Filemanager_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * 
     * - add downloadlink table
     * 
     * @see 0009908: anonymous download links for files and folders
     * 
     * @return void
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('
        <table>
            <name>filemanager_downloadlink</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>node_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>password</name>
                    <type>text</type>
                    <length>64</length>
                </field>
                <field>
                    <name>expiry_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>access_count</name>
                    <type>integer</type>
                    <default>0</default>
                </field>
                <field>
                    <name>created_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>last_modified_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
                <field>
                    <name>deleted_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>filemanager_downloadlink::node_id--tree_nodes::id</name>
                    <field>
                        <name>node_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>tree_nodes</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>
            </declaration>
        </table>');
        $this->_backend->createTable($declaration, 'Tinebase', 'filemanager_downloadlink');
        
        $this->setApplicationVersion('Filemanager', '8.1');
    }
    
    /**
     * update to 9.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Filemanager', '9.0');
    }
}
