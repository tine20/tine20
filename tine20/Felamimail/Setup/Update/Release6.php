<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1
     * - default values for boolean cols
     */
    public function update_0()
    {
        $colsToChange = array(
            'felamimail_account' => array(
                'cols'     => array(
                    'has_children_support' => 'true',
                    'sieve_vacation_active' => 'false',
                ),
                'version'  => 18
            ),
            'felamimail_folder' => array(
                'cols'     => array(
                    'is_selectable' => 'true',
                    'has_children' => 'false',
                    'system_folder' => 'false',
                ),
                'version'  => 11
            )
        );
        foreach ($colsToChange as $tablename => $table) {
            foreach ($table['cols'] as $col => $default) {
                $declaration = new Setup_Backend_Schema_Field_Xml('
                    <field>
                        <name>' . $col . '</name>
                        <type>boolean</type>
                        <default>' . $default . '</default>
                    </field>');
                $this->_backend->alterCol($tablename, $declaration);
            }
            $this->setTableVersion($tablename, $table['version']);
        }
        
        $this->_backend->dropCol('felamimail_account', 'sort_folders');
        
        $this->setApplicationVersion('Felamimail', '6.1');
    }

    /**
     * update to 6.2
     * - drop structure col from message cache
     */
    public function update_1()
    {
        $this->_backend->dropCol('felamimail_cache_message', 'structure');
        $this->setTableVersion('felamimail_cache_message', 8);
        
        $this->setApplicationVersion('Felamimail', '6.2');
    }
    
    /**
     * update to 6.3
     * - rename "All inboxes" persistent filter
     * 
     * @see 0007280: "All INBOXES" favorite not translated 
     */
    public function update_2()
    {
        // rename "all inboxes" filter
        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . "filter SET name = 'All inboxes' WHERE name = 'All INBOXES'");
        
        $this->setApplicationVersion('Felamimail', '6.3');
    }
    
    /**
     * update to 6.4
     * - rule id needs to be an integer
     * 
     * @see 0007240: order of sieve rules changes when vacation message is saved 
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>integer</type>
            </field>');
        $this->_backend->alterCol('felamimail_sieve_rule', $declaration);
        $this->setTableVersion('felamimail_sieve_rule', 2);
        
        $this->setApplicationVersion('Felamimail', '6.4');
    }

    /**
     * update to 7.0
     * 
     * @return void
     */
    public function update_4()
    {
        $this->setApplicationVersion('Felamimail', '7.0');
    }
}
