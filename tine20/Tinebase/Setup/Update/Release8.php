<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * 
     * @see 0009152: saving of record fails because of too many relations
     */
    public function update_0()
    {
        $valueFields = array('old_value', 'new_value');
        foreach ($valueFields as $field) {
            
            // check schema, only change if type == text
            $typeMapping = $this->_backend->getTypeMapping('text');
            $schema = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'timemachine_modlog', $this->_backend->getDb());
            
            if ($schema[$field]['DATA_TYPE'] === $typeMapping['defaultType']) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Old column type (' . $schema[$field]['DATA_TYPE'] . ') is going to be altered to clob');
                
                $declaration = new Setup_Backend_Schema_Field_Xml('
                    <field>
                        <name>' . $field . '</name>
                        <type>clob</type>
                    </field>
                ');
            
                $this->_backend->alterCol('timemachine_modlog', $declaration);
            }
        }
        $this->setTableVersion('timemachine_modlog', '3');
        $this->setApplicationVersion('Tinebase', '8.1');
    }

    /**
     * update to 8.2
     * 
     * @see 0009644: remove user registration
     */
    public function update_1()
    {
        if ($this->_backend->tableExists('registrations')) {
            $this->dropTable('registrations');
        }
        
        if ($this->_backend->tableExists('registration_invitation')) {
            $this->dropTable('registration_invitation');
        }
        
        $this->setApplicationVersion('Tinebase', '8.2');
    }
    
    /**
     * update to 8.3
     * - update 256 char fields
     * 
     * @see 0008070: check index lengths
     */
    public function update_2()
    {
        $columns = array("container" => array(
                            "name" => 'true'
                            ),
                        "note_types" => array(
                            "icon" => 'true',
                            "description" => 'null'
                            ),
                        "tags" => array(
                            "name" => 'null',
                            "description" => 'null'
                            ),
                        "accounts" => array(
                            "home_dir" => 'false'
                            )
                        );
        $this->truncateTextColumn($columns, 255);
        $this->setTableVersion('container', '9');
        $this->setTableVersion('note_types', '3');
        $this->setTableVersion('tags', '7');
        $this->setTableVersion('accounts', '10');
        $this->setApplicationVersion('Tinebase', '8.3');
    }
    
    /**
     * adds a label property to hold a humanreadable text
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('<field>
            <name>label</name>
            <type>text</type>
            <length>128</length>
            <notnull>false</notnull>
        </field>');
        
        $this->_backend->addCol('importexport_definition', $declaration);
        
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        
        $this->setTableVersion('importexport_definition', '8');
        $this->setApplicationVersion('Tinebase', '8.4');
    }
}
