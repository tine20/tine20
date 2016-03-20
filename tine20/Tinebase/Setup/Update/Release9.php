<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * 
     * @see 0011178: allow to lock preferences for individual users
     */
    public function update_0()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_11();
        $this->setApplicationVersion('Tinebase', '9.1');
    }

    /**
     * update to 9.2
     *
     * adds index to relations
     */
    public function update_1()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_12();
        $this->setApplicationVersion('Tinebase', '9.2');
    }

    /**
     * update to 9.3
     *
     * adds ondelete cascade to some indices (tags + roles)
     */
    public function update_2()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_13();
        $this->setApplicationVersion('Tinebase', '9.3');
    }

    /**
     * update to 9.4
     *
     * move keyFieldConfig defaults to config files
     */
    public function update_3()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_14();
        $this->setApplicationVersion('Tinebase', '9.4');
    }

    /**
     * update to 9.5
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>order</name>
                    <type>integer</type>
                    <default>0</default>
                    <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('container', $declaration);
        $this->setTableVersion('container', 10);
        $this->setApplicationVersion('Tinebase', '9.5');
    }

    /**
     * update to 9.6
     *
     * - rename relation degree (fix direction)
     * - add type to constrain
     */
    public function update_5()
    {
        if (!$this->_backend->columnExists('related_degree', 'relations')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>related_degree</name>
                    <type>text</type>
                    <length>32</length>
                    <notnull>true</notnull>
                </field>
            ');
            $this->_backend->alterCol('relations', $declaration, 'own_degree');
        }

        // delete index unique-fields
        try {
            $this->_backend->dropIndex('relations', 'unique-fields');
        } catch (Exception $e) {}
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>unique-fields</name>
                <unique>true</unique>
                <field>
                    <name>own_model</name>
                </field>
                <field>
                    <name>own_backend</name>
                </field>
                <field>
                    <name>own_id</name>
                </field>
                <field>
                    <name>related_model</name>
                </field>
                <field>
                    <name>related_backend</name>
                </field>
                <field>
                    <name>related_id</name>
                </field>
                <field>
                    <name>type</name>
                </field>
            </index>'
        );

        $this->_backend->addIndex('relations', $declaration);

        $this->setTableVersion('relations', '9');
        $this->setApplicationVersion('Tinebase', '9.6');
    }

    /**
     * update to 9.7
     *
     * @see 0011620: add "path" filter for records
     */
    public function update_6()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>path</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>record_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>path</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>shadow_path</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>path</name>
                    <field>
                        <name>path</name>
                    </field>
                </index>
                <index>
                    <name>shadow_path</name>
                    <unique>true</unique>
                    <field>
                        <name>shadow_path</name>
                    </field>
                </index>
                <index>
                    <name>record_id</name>
                    <field>
                        <name>record_id</name>
                    </field>
                </index>
            </declaration>
        </table>');

        $this->createTable('path', $declaration);
        $this->setApplicationVersion('Tinebase', '9.7');
    }
}
