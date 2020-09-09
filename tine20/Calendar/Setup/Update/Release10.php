<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * add type and max_number_of_people columns to resources
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
        if ($user) {
            Tinebase_Core::set(Tinebase_Core::USER, $user);

            $resources = $resourceController->getAll();
            foreach ($resources as $resource) {
                if ($resource->is_location) {
                    $resource->type = 'ROOM';

                    try {
                        $resourceController->update($resource);
                    } catch (Tinebase_Exception_AccessDenied $tead) {
                        Tinebase_Exception::log($tead);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        Tinebase_Exception::log($tenf);
                    }
                }
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
                    'observer_model'        => 'Calendar_Model_Resource',
                    'observer_identifier'   => $resource->getId(),
                    'observed_event'        => 'Tinebase_Event_Record_Update'
                ));
                $persistentObserver->addObserver($updateObserver);

                $deleteObserver = new Tinebase_Model_PersistentObserver(array(
                    'observable_model'      => 'Tinebase_Model_Container',
                    'observable_identifier' => $resource->container_id,
                    'observer_model'        => 'Calendar_Model_Resource',
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

        try {
            $this->_backend->addIndex('cal_events', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('cal_events', 13);
        $this->setApplicationVersion('Calendar', '10.5');
    }

    /**
     * update to 10.6
     *
     * import export definitions
     */
    public function update_5()
    {
        $addressbookApplication = Tinebase_Application::getInstance()->getApplicationByName('Calendar');
        $definitionDirectory = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'Export' . DIRECTORY_SEPARATOR . 'definitions' . DIRECTORY_SEPARATOR;

        $dir = new DirectoryIterator($definitionDirectory);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile()) {
                Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename(
                    $fileinfo->getPath() . '/' . $fileinfo->getFilename(),
                    $addressbookApplication
                );
            }
        }

        $this->setApplicationVersion('Calendar', '10.6');
    }

    /**
     * update to 10.7
     *
     * import export definitions
     */
    public function update_6()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Calendar_Scheduler_Task::addTentativeNotificationTask($scheduler);

        $this->setApplicationVersion('Calendar', '10.7');
    }

    public function update_7()
    {
        if ($this->getTableVersion('cal_events') < 14) {
            $this->setTableVersion('cal_events', 14);
        }
        if ($this->getTableVersion('cal_attendee') < 6) {
            $this->setTableVersion('cal_attendee', 6);
        }
        if ($this->getTableVersion('cal_resources') < 6) {
            $this->setTableVersion('cal_resources', 6);
        }
        $this->setApplicationVersion('Calendar', '10.8');
    }

    /**
     * force activesync calendar resync for iOS devices
     */
    public function update_8()
    {
        $release8 = new Calendar_Setup_Update_Release8($this->_backend);
        $release8->update_11();
        $this->setApplicationVersion('Calendar', '10.9');
    }

    /**
     * update to 10.10
     *
     * redo release9->update_4, it may have been skipped if updating from 2015.11
     */
    public function update_9()
    {
        $release9 = new Calendar_Setup_Update_Release9($this->_backend);
        $release9->update_4();
        $this->setApplicationVersion('Calendar', '10.10');
    }

    /**
     * update to 10.11
     *
     * add xprops to cal_attendee
     */
    public function update_10()
    {
        if (!$this->_backend->columnExists('xprops', 'cal_attendee')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                <name>xprops</name>
                <type>text</type>
                <length>65535</length>
                </field>');

            $this->_backend->addCol('cal_attendee', $declaration);
        }

        if ($this->getTableVersion('cal_attendee') < 7) {
            $this->setTableVersion('cal_attendee', 7);
        }

        $this->setApplicationVersion('Calendar', '10.11');
    }

    public function update_11()
    {
        $containerController = Tinebase_Container::getInstance();
        try {
            $oldValue = $containerController->doSearchAclFilter(false);
            $containerBackend = new Tinebase_Backend_Sql(array(
                'modelName' => 'Tinebase_Model_Container',
                'tableName' => 'container',
            ));
            foreach ($containerBackend->search(new Tinebase_Model_ContainerFilter([
                ['field' => 'application_id', 'operator' => 'equals', 'value' =>
                    Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()],
                ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_SHARED],
                ['field' => 'name', 'operator' => 'contains', 'value' => '@'],
                ['field' => 'is_deleted', 'operator' => 'equals', 'value' => 0],
            ])) as $container) {
                if (!preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $container->name)) {
                    continue;
                }
                $grants = $containerController->getGrantsOfContainer($container, true);
                if ($grants->count() !== 1 || $grants->getFirstRecord()->account_type !==
                    Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE ||
                    $grants->getFirstRecord()->{Tinebase_Model_Grants::GRANT_READ}) {
                    continue;
                }
                $grants->getFirstRecord()->{Tinebase_Model_Grants::GRANT_DELETE} = false;
                $containerController->setGrants($container, $grants, true, false);
            }
        } finally {
            $containerController->doSearchAclFilter($oldValue);
        }
        $this->setApplicationVersion('Calendar', '10.12');
    }

    public function update_12()
    {
        $this->setApplicationVersion('Calendar', '11.0');
    }
}
