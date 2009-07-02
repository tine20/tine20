<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Felamimail_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update function 1
     * - add 'none' to smtp_auth for accounts
     *
     */    
    public function update_1()
    {
        $field = '<field>
                    <name>smtp_auth</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>login</value>
                    <value>plain</value>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('felamimail_account', $declaration);
        
        $this->setApplicationVersion('Felamimail', '0.2');
        $this->setTableVersion('felamimail_account', '2');
    }

    /**
     * update function 2
     * - add namespaces for accounts
     *
     */    
    public function update_2()
    {
        $newFields = array('ns_personal', 'ns_other', 'ns_shared');
        
        foreach ($newFields as $field) {
            $field = '<field>
                <name>' . $field . '</name>
                <type>text</type>
                <length>256</length>
            </field>';
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('felamimail_account', $declaration);
        }
        
        $this->setApplicationVersion('Felamimail', '0.3');
        $this->setTableVersion('felamimail_account', '3');
    }
}
