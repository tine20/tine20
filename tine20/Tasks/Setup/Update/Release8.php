<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tasks_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     *
     * - adds etag column
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>etag</name>
                <type>text</type>
                <length>60</length>
            </field>');
        $this->_backend->addCol('tasks', $declaration);
    
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>etag</name>
                <field>
                    <name>etag</name>
                </field>
            </index>');
        $this->_backend->addIndex('tasks', $declaration);
    
        $this->setTableVersion('tasks', 8);
        $this->setApplicationVersion('Tasks', '8.1');
    }
}
