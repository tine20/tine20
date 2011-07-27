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
}
