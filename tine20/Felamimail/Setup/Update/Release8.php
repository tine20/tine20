<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $declaration = new Setup_Backend_Schema_Field_Xml('<field>
            <name>conjunction</name>
            <type>text</type>
            <length>40</length>
            <notnull>true</notnull>
            <default>allof</default>
        </field>');
        
        $this->_backend->addCol('felamimail_sieve_rule', $declaration);
        
        $this->setTableVersion('felamimail_sieve_rule', 3);
        
        $this->setApplicationVersion('Felamimail', '8.2');
    }
}
