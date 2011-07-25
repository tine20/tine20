<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - increase size of n_family and org_name
     */
    public function update_0()
    {
        $colsToChange = array('n_family', 'org_name');
        foreach ($colsToChange as $col) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>' . $col . '</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>');
            $this->_backend->alterCol('addressbook', $declaration);
        }
        
        $this->setTableVersion('addressbook', 13);
        $this->setApplicationVersion('Addressbook', '5.1');
    }

    /**
     * update to 5.2
     * - enum -> text
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>gender</name>
                <type>text</type>
                <length>32</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('addressbook_salutations', $declaration);
        $this->setTableVersion('addressbook_salutations', 3);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>text</type>
                <length>32</length>
                <notnull>true</notnull>
                <default>list</default>
            </field>');
        $this->_backend->alterCol('addressbook_lists', $declaration);
        $this->setTableVersion('addressbook_lists', 2);
        
        $this->setApplicationVersion('Addressbook', '5.2');
    }
}
