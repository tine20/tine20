<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */
class Calendar_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     * - add polls & poll_id
     */
    public function update_0()
    {
        if (!$this->_backend->columnExists('poll_id', 'cal_events')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>poll_id</name>
                <type>text</type>
                <length>40</length>
            </field>');
            $this->_backend->addCol('cal_events', $declaration);

            $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>poll_id</name>
                <field>
                    <name>poll_id</name>
                </field>
            </index>');
            $this->_backend->addIndex('cal_events', $declaration);
        }

        $this->updateSchema('Calendar', [
            Calendar_Model_Poll::class,
        ]);

        $this->setTableVersion('cal_events', 15);
        $this->setApplicationVersion('Calendar', '11.1');
    }

    /**
     * update to 11.2
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_1()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Calendar'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Calendar', '11.2');
    }

    /**
     * update to 11.3
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_2()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Calendar'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Calendar', '11.3');
    }

    /**
     * update to 11.4
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_3()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Calendar'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Calendar', '11.4');
    }

    /**
     * update to 11.5
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_4()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Calendar'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Calendar', '11.5');
    }

    /**
     * force activesync calendar resync for iOS devices
     */
    public function update_5()
    {
        $release8 = new Calendar_Setup_Update_Release8($this->_backend);
        $release8->update_11();
        $this->setApplicationVersion('Calendar', '11.6');
    }

    /**
     * update to 11.7
     * Calendar_Model_ResourceGrants change
     */
    public function update_6()
    {
        $containerController = Tinebase_Container::getInstance();
        $resourceController = Calendar_Controller_Resource::getInstance();
        $resourceController->doContainerACLChecks(false);
        $resources = $resourceController->getAll();

        /** @var Calendar_Model_Resource $resource */
        foreach ($resources as $resource) {
            try {
                $container = $containerController->getContainerById($resource->container_id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Resource might have lost its container... deleting invalid resource.'
                    . ' Error: ' . $tenf->getMessage());
                $resourceController->delete($resource->getId());
                continue;
            }
            if (!isset($container->xprops()['Tinebase']['Container']['GrantsModel'])) {
                $container->xprops()['Tinebase']['Container']['GrantsModel'] = Calendar_Model_ResourceGrants::class;
                $container->xprops()['Calendar']['Resource']['resource_id'] = $resource->getId();
                /** @var Tinebase_Model_Container $container */
                $container = $containerController->update($container);

                $grants = $containerController->getGrantsOfContainer($container, true);
                /** @var Calendar_Model_ResourceGrants $grant */
                foreach ($grants as $grant) {
                    if ($grant->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                        foreach (Calendar_Model_ResourceGrants::getAllGrants() as $grantName) {
                            $grant->{$grantName} = true;
                        }
                    } else {
                        if ($grant->{Tinebase_Model_Grants::GRANT_ADD}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_ADD} = true;
                        }
                        if ($grant->{Tinebase_Model_Grants::GRANT_DELETE}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_DELETE} = true;
                        }
                        if ($grant->{Tinebase_Model_Grants::GRANT_EDIT}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_EDIT} = true;
                            $grant->{Calendar_Model_ResourceGrants::RESOURCE_EDIT} = true;
                        }
                        if ($grant->{Tinebase_Model_Grants::GRANT_EXPORT}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_EXPORT} = true;
                            $grant->{Calendar_Model_ResourceGrants::RESOURCE_EXPORT} = true;
                        }
                        if ($grant->{Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_FREEBUSY} = true;
                        }
                        if ($grant->{Tinebase_Model_Grants::GRANT_EXPORT}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_EXPORT} = true;
                            $grant->{Calendar_Model_ResourceGrants::RESOURCE_EXPORT} = true;
                        }
                        if ($grant->{Tinebase_Model_Grants::GRANT_READ}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_READ} = true;
                            $grant->{Calendar_Model_ResourceGrants::RESOURCE_READ} = true;
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_FREEBUSY} = true;
                            $grant->{Calendar_Model_ResourceGrants::RESOURCE_INVITE} = true;
                        }
                        if ($grant->{Tinebase_Model_Grants::GRANT_SYNC}) {
                            $grant->{Calendar_Model_ResourceGrants::EVENTS_SYNC} = true;
                            $grant->{Calendar_Model_ResourceGrants::RESOURCE_SYNC} = true;
                        }
                    }
                }
                $resource->grants = $grants->toArray();
                $resourceController->update($resource);
            }
        }

        Calendar_Controller_Resource::destroyInstance();

        $this->setApplicationVersion('Calendar', '11.7');
    }

    /**
     * update to 11.8
     * Calendar_Model_EventPersonalGrants change
     */
    public function update_7()
    {
        $containers = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter([
            ['field' => 'application_id', 'operator' => 'equals', 'value' => Tinebase_Application::getInstance()
                ->getApplicationByName('Calendar')->getId()],
            ['field' => 'model', 'operator' => 'equals', 'value' => Calendar_Model_Event::class],
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_PERSONAL],
        ]));

        /** @var Tinebase_Model_Container $container */
        foreach ($containers as $container) {
            $container->xprops()['Tinebase']['Container']['GrantsModel'] = Calendar_Model_EventPersonalGrants::class;
            Tinebase_Container::getInstance()->update($container);
        }

        $this->setApplicationVersion('Calendar', '11.8');
    }

    /**
     * update to 11.9
     */
    public function update_8()
    {
        $this->updateKeyFieldIcon(Calendar_Config::getInstance(), Calendar_Config::ATTENDEE_STATUS);
        $this->updateKeyFieldIcon(Calendar_Config::getInstance(), Calendar_Config::EVENT_STATUS);

        $this->setApplicationVersion('Calendar', '11.9');
    }

    /**
     * update to 11.10
     *
     * add hierarchy column to cal_resources (filling will be done in Tinebase)
     */
    public function update_9($updateCalenderVersion = true)
    {
        if (! $this->_backend->columnExists('hierarchy', 'cal_resources')) {
            $this->_backend->addCol('cal_resources', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>hierarchy</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));
        }

        if ($this->getTableVersion('cal_resources') == 6) {
            $this->setTableVersion('cal_resources', 7);
        }

        if ($updateCalenderVersion) {
            $this->setApplicationVersion('Calendar', '11.10');
        }
    }

    /**
     * update to 11.11
     *
     * add xprops to cal_attendee
     */
    public function update_10()
    {
        $update10 = new Calendar_Setup_Update_Release10($this->_backend);
        $update10->update_10();

        $this->setApplicationVersion('Calendar', '11.11');
    }

    /**
     * update to 11.12
     *
     * add xprops to external invitation calendars
     */
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

                if (isset($container->xprops()[Calendar_Controller::XPROP_EXTERNAL_INVITATION_CALENDAR]) ||
                        !preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $container->name)) {
                    continue;
                }
                $grants = $containerController->getGrantsOfContainer($container, true);
                if ($grants->count() !== 1 || $grants->getFirstRecord()->account_type !==
                        Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE ||
                        $grants->getFirstRecord()->{Tinebase_Model_Grants::GRANT_READ}) {
                    continue;
                }
                $container->xprops()[Calendar_Controller::XPROP_EXTERNAL_INVITATION_CALENDAR] = true;
                $containerController->update($container);
            }

        } finally {
            $containerController->doSearchAclFilter($oldValue);
        }

        $this->setApplicationVersion('Calendar', '11.12');
    }

    public function update_12()
    {
        if (! $this->_backend->columnExists('xprops', 'cal_events')) {
            $this->_backend->addCol('cal_events', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>xprops</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));
        }

        if ($this->getTableVersion('cal_events') < 16) {
            $this->setTableVersion('cal_events', 16);
        }

        $this->setApplicationVersion('Calendar', '11.13');
    }

    public function update_13()
    {
        $records = Calendar_Controller_Resource::getInstance()->getAll();

        foreach ($records as $record)
        {
            $container = Tinebase_Container::getInstance()->getContainerById($record->container_id);
            $resource_type = Calendar_Config::getInstance()->get(Calendar_Config::RESOURCE_TYPES)->getValue($record['type']);
            $container->xprops()['Calendar']['Resource']['resource_icon'] = Calendar_Config::getInstance()->get(Calendar_Config::RESOURCE_TYPES)
                ->getKeyfieldRecordByValue($resource_type)['icon'];

            Tinebase_Container::getInstance()->update($container);
        }



        $this->setApplicationVersion('Calendar', '11.14');
    }

    public function update_14()
    {
        $records = Calendar_Controller_Resource::getInstance()->getAll();
        foreach ($records as $record)
        {
            $container = Tinebase_Container::getInstance()->getContainerById($record->container_id);

            unset($container->xprops()['Calendar']['Resource']['resource_icon']);

            $resource_type = Calendar_Config::getInstance()->get(Calendar_Config::RESOURCE_TYPES)->getValue($record['type']);
            $container->xprops()['Calendar']['Resource']['resource_type'] = Calendar_Config::getInstance()->get(Calendar_Config::RESOURCE_TYPES)
                ->getKeyfieldRecordByValue($resource_type)['id'];

            Tinebase_Container::getInstance()->update($container);
        }



        $this->setApplicationVersion('Calendar', '11.15');
    }

    public function update_15()
    {
        $update10 = new Calendar_Setup_Update_Release10($this->_backend);
        $update10->update_11();

        $this->setApplicationVersion('Calendar', '11.16');
    }

    public function update_16()
    {
        $this->_backend->dropForeignKey('cal_attendee', 'cal_attendee::displaycontainer_id--container::id');
        $this->_backend->addForeignKey('cal_attendee', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>cal_attendee::displaycontainer_id--container::id</name>
                    <field>
                        <name>displaycontainer_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                        <ondelete>SET NULL</ondelete>
                    </reference>
                </index>'));
        if ($this->getTableVersion('cal_events') < 8) {
            $this->setTableVersion('cal_events', 8);
        }
        $this->setApplicationVersion('Calendar', '11.17');
    }
}
