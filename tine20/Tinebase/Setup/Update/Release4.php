<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase updates for version 4.x
 *
 * @package     Tinebase
 * @subpackage  Setup
 */
class Tinebase_Setup_Update_Release4 extends Setup_Update_Abstract
{    
    /**
     * update to 4.1
     * - add index for accounts.contact_id
     */
    public function update_0()
    {
        if ($this->getTableVersion('accounts') < 7) {
            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>contact_id</name>
                    <field>
                        <name>contact_id</name>
                    </field>
                </index>
            ');
            $this->_backend->addIndex('accounts', $declaration);
            $this->setTableVersion('accounts', '7');
        }
        
        $this->setApplicationVersion('Tinebase', '4.1');
    }
        
    /**
     * update to 4.2
     * - add index for groups.list_id and access_log.sessionid
     */
    public function update_1()
    {
        if ($this->getTableVersion('groups') < 3) {
            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>list_id</name>
                    <field>
                        <name>list_id</name>
                    </field>
                </index>
            ');
            $this->_backend->addIndex('groups', $declaration);
            $this->setTableVersion('groups', '3');
        }
        
        if ($this->getTableVersion('access_log') < 3) {
            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>sessionid</name>
                    <field>
                        <name>sessionid</name>
                    </field>
                </index>
            ');
            $this->_backend->addIndex('access_log', $declaration);
            $this->setTableVersion('access_log', '3');
        }
        
        $this->setApplicationVersion('Tinebase', '4.2');
    }    
}
