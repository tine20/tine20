<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * 
     * add account_id-folder_id index to felamimail_cache_message
     */
    public function update_0()
    {
        $update8 = new Felamimail_Setup_Update_Release8($this->_backend);
        $update8->update_4();
        $this->setApplicationVersion('Felamimail', '9.1');
    }

    /**
     * update to 9.2
     *
     * support plaintext mail => add compose_format and preserve_format options
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>compose_format</name>
                <type>text</type>
                <length>64</length>
                <default>html</default>
            </field>');
        $this->_backend->addCol('felamimail_account', $declaration, 17);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>preserve_format</name>
                <type>boolean</type>
                <default>false</default>
            </field>');
        $this->_backend->addCol('felamimail_account', $declaration, 18);

        $this->setTableVersion('felamimail_account', 21);
        $this->setApplicationVersion('Felamimail', '9.2');
    }
}
