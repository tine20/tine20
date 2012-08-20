<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
}
