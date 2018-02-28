<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
}