<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - enum -> text
     */
    public function update_0()
    {
        $colsToChange = array('ssl', 'smtp_auth', 'smtp_ssl', 'sieve_ssl');
        foreach ($colsToChange as $col) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>' . $col . '</name>
                    <type>text</type>
                    <length>32</length>
                    <default>none</default>
                </field>');
            $this->_backend->alterCol('felamimail_account', $declaration);
        }
        
        $this->setTableVersion('felamimail_account', 16);
        $this->setApplicationVersion('Felamimail', '5.1');
    }

    /**
     * update to 5.2
     * - increase globalname length
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>globalname</name>
                <type>text</type>
            </field>');
        $this->_backend->alterCol('felamimail_folder', $declaration);
        
        $this->setTableVersion('felamimail_folder', 9);
        $this->setApplicationVersion('Felamimail', '5.2');
    }

    /**
     * update to 5.3
     * - add sieve tables
     */
    public function update_2()
    {
        $tableDefinition = new Setup_Backend_Schema_Table_Xml('
        <table>
            <name>felamimail_sieve_rule</name>
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
                    <name>action_type</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>action_argument</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>conditions</name>
                    <type>text</type>
                </field>
                <field>
                    <name>enabled</name>
                    <type>boolean</type>
                    <default>false</default>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>id-account_id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>
            </declaration>
        </table>
        ');
        // tables might already exist because of an update script in Maischa
        if (! $this->_backend->tableExists('felamimail_sieve_rule')) {
            $this->_backend->createTable($tableDefinition, 'Felamimail', 'felamimail_sieve_rule');
        }

        $tableDefinition = new Setup_Backend_Schema_Table_Xml('
        <table>
            <name>felamimail_sieve_vacation</name>
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
                    <name>subject</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>from</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>mime</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>days</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>reason</name>
                    <type>text</type>
                </field>
                <field>
                    <name>addresses</name>
                    <type>text</type>
                </field>
                <field>
                    <name>enabled</name>
                    <type>boolean</type>
                    <default>false</default>
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
        </table>
        ');
        // tables might already exist because of an update script in Maischa
        if (! $this->_backend->tableExists('felamimail_sieve_vacation')) {
            $this->_backend->createTable($tableDefinition, 'Felamimail', 'felamimail_sieve_vacation');
        }
        $this->setApplicationVersion('Felamimail', '5.3');
    }

    /**
     * update to 5.4
     * - cache_job_actions_estimate -> cache_job_actions_est
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cache_job_actions_est</name>
                <type>integer</type>
            </field>');
        try {
            $this->_backend->alterCol('felamimail_folder', $declaration, 'cache_job_actions_estimate');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // already done
        }
        
        $this->setTableVersion('felamimail_folder', 10);
        $this->setApplicationVersion('Felamimail', '5.4');
    }
    
    /**
     * update to 5.5
     * - add signature_position
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>signature_position</name>
                <type>text</type>
                <length>64</length>
                <default>below</default>
            </field>');
        try {
            $this->_backend->addCol('felamimail_account', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // already done
        }
        
        $this->setTableVersion('felamimail_account', 17);
        $this->setApplicationVersion('Felamimail', '5.5');
    }
    
    
}
