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

        $resourceController = Calendar_Controller_Resource::getInstance();
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

    /**
     * add resource xprops to container
     */
    public function update_3()
    {
        $be = new Tinebase_Backend_Sql(array(
            'modelName' => 'Calendar_Model_Resource',
            'tableName' => 'cal_resources'
        ));

        $persistentObserver = Tinebase_Record_PersistentObserver::getInstance();

        foreach($be->getAll() as $resource) {
            try {
                $container = Tinebase_Container::getInstance()->get($resource->container_id);
                $container->xprops()['Calendar']['Resource']['resource_id'] = $resource->getId();

                Tinebase_Container::getInstance()->update($container);

                $updateObserver = new Tinebase_Model_PersistentObserver(array(
                    'observable_model'      => 'Tinebase_Model_Container',
                    'observable_identifier' => $resource->container_id,
                    'observer_model'        => $this->_modelName,
                    'observer_identifier'   => $resource->getId(),
                    'observed_event'        => 'Tinebase_Event_Record_Update'
                ));
                $persistentObserver->addObserver($updateObserver);

                $deleteObserver = new Tinebase_Model_PersistentObserver(array(
                    'observable_model'      => 'Tinebase_Model_Container',
                    'observable_identifier' => $resource->container_id,
                    'observer_model'        => $this->_modelName,
                    'observer_identifier'   => $resource->getId(),
                    'observed_event'        => 'Tinebase_Event_Record_Delete'
                ));
                $persistentObserver->addObserver($deleteObserver);

            } catch (Exception $e) {
                Tinebase_Exception::log($e, /* suppress trace */ false);
            }
        }

        $this->setApplicationVersion('Calendar', '10.4');
    }

    /**
     * update to 10.5
     *
     * Add fulltext index for description field
     */
    public function update_4()
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

        $this->_backend->addIndex('cal_events', $declaration);

        $this->setTableVersion('cal_events', 13);
        $this->setApplicationVersion('Calendar', '10.5');
    }
}
