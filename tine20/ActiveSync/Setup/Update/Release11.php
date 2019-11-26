<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);

/**
 * updates for major release 11
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     *
     * @return void
     */
    public function update_0()
    {
        if (!$this->_backend->columnExists('monitor_lastping', 'acsync_device')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>monitor_lastping</name>
                    <type>integer</type>
                    <default>0</default>
                </field>
            ');
            $this->_backend->addCol('acsync_device', $declaration);

            $this->setTableVersion('acsync_device', '7');
        }

        $scheduler = Tinebase_Core::getScheduler();
        if (!$scheduler->hasTask('ActiveSync_Controller_Device::monitorDeviceLastPingTask')) {
            ActiveSync_Scheduler_Task::addMonitorDeviceLastPingTask($scheduler);
        }

        $this->setApplicationVersion('ActiveSync', '11.1');
    }

    /**
     * update to 12.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('ActiveSync', '12.0');
    }
}
