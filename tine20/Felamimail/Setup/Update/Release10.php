<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * change signature to medium text
     */
    public function update_0()
    {
        $update9 = new Felamimail_Setup_Update_Release9($this->_backend);
        $update9->update_2();
        $this->setApplicationVersion('Felamimail', '10.1');
    }

    /**
     * update to 10.2
     *
     * @see 0002284: add reply-to setting to email account
     */
    public function update_1()
    {
        $update9 = new Felamimail_Setup_Update_Release9($this->_backend);
        $update9->update_3();
        $this->setApplicationVersion('Felamimail', '10.2');
    }

    /**
     * update to 10.3
     *
     * update vacation templates node id in config
     */
    public function update_2()
    {
        try {
            $container = Tinebase_Container::getInstance()->get(
                Felamimail_Config::getInstance()->{Felamimail_Config::VACATION_TEMPLATES_CONTAINER_ID},
                /* $_getDeleted */ true
            );
            $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Felamimail', Tinebase_FileSystem::FOLDER_TYPE_SHARED);
            $path .= '/' . $container->name;
            $node = Tinebase_FileSystem::getInstance()->stat($path);
            Felamimail_Config::getInstance()->set(Felamimail_Config::VACATION_TEMPLATES_CONTAINER_ID, $node->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        $this->setApplicationVersion('Felamimail', '10.3');
    }

    /**
     * update to 10.4
     *
     * add sieve script part table
     */
    public function update_3()
    {
        $tableDefinition = new Setup_Backend_Schema_Table_Xml('<table>
            <name>felamimail_sieve_scriptpart</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>type</name>
                    <type>text</type>
                    <length>60</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>script</name>
                    <type>text</type>
                    <length>65535</length>
                </field>
                <field>
                    <name>requires</name>
                    <type>text</type>
                    <length>65535</length>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>account_id-type-name</name>
                    <unique>true</unique>
                    <field>
                        <name>account_id</name>
                    </field>
                    <field>
                        <name>type</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                </index>
            </declaration>
        </table>');

        if (! $this->_backend->tableExists('felamimail_sieve_scriptpart')) {
            $this->_backend->createTable($tableDefinition, 'Felamimail', 'felamimail_sieve_scriptpart');
        }

        $this->setApplicationVersion('Felamimail', '10.4');
    }
}
