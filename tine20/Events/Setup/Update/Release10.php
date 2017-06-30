<?php
/**
 * Tine 2.0
 *
 * @package     Events
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Events_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * Update to 10.1
     *
     * Add fulltext index for description field of events_event
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        $this->_backend->addIndex('events_event', $declaration);

        $this->setTableVersion('events_event', '2');
        $this->setApplicationVersion('Events', '10.1');
    }

    public function update_1()
    {}
}
