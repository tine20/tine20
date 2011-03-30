<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
            try {
                $this->_backend->addIndex('accounts', $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
            }
            
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
            try {
                $this->_backend->addIndex('groups', $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
            }
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
            try {
                $this->_backend->addIndex('access_log', $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
            }
            $this->setTableVersion('access_log', '3');
        }
        
        $this->setApplicationVersion('Tinebase', '4.2');
    }
        
    /**
     * update to 4.3
     * - add index for applications.status
     */
    public function update_2()
    {
        if ($this->getTableVersion('applications') < 2) {
            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>status</name>
                    <field>
                        <name>status</name>
                    </field>
                </index>
            ');
            try {
                $this->_backend->addIndex('applications', $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
            }
            $this->setTableVersion('applications', '7');
        }
        
        $this->setApplicationVersion('Tinebase', '4.3');
    }
    
    /**
     * update to 4.4
     * - extend length of some accounts fields
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
    		<field>
            	<name>login_name</name>
            	<type>text</type>
            	<length>255</length>
            	<notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
    		<field>
                <name>email</name>
                <type>text</type>
                <length>255</length>
            </field>
        ');
        $this->_backend->alterCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
    		<field>
                <name>first_name</name>
                <type>text</type>
                <length>255</length>
            </field>
        ');
        $this->_backend->alterCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
    		<field>
                <name>last_name</name>
                <type>text</type>
                <length>255</length>
                <notnull>true</notnull>
    		</field>
        ');
        $this->_backend->alterCol('accounts', $declaration);
        
        $this->setTableVersion('accounts', '8');
        
        $this->setApplicationVersion('Tinebase', '4.4');
    }
}
