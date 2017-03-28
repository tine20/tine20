<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     * - Update Calendar Import Export definitions
     */
    public function update_0()
    {
        $release9 = new Calendar_Setup_Update_Release9($this->_backend);
        $release9->update_7();
        $this->setApplicationVersion('Calendar', '10.1');
    }

    /**
     * fix displaycontainer in organizers attendee records
     */
    public function update_1()
    {
        $release9 = new Calendar_Setup_Update_Release9($this->_backend);
        $release9->update_8();
        $this->setApplicationVersion('Calendar', '10.2');
    }

    /**
     * fix displaycontainer in organizers attendee records
     */
    public function update_2()
    {

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>max_number_of_people</name>
                <type>integer</type>
                <notnull>false</notnull>
                <default>null</default>
            </field>');
        $this->_backend->addCol('cal_resources', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>text</type>
                <length>32</length>
                <default>RESOURCE</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->addCol('cal_resources', $declaration);

        $resourceController =  Calendar_Controller_Resource::getInstance();
        $user = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        Tinebase_Core::set(Tinebase_Core::USER, $user);

        $resources = $resourceController->getAll();
        foreach ($resources as $resource) {
            if ($resource->is_location) {
                $resource->type = 'ROOM';

                $resourceController->update($resource);
            }
        }

        $this->setTableVersion('cal_resources', 5);


        $this->setApplicationVersion('Calendar', '10.3');
    }
}
