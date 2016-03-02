<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * - identify base event via new base_event_id field instead of UID
     */
    public function update_0()
    {
        if ($this->getTableVersion('cal_events') < 10) {
            $release8 = new Calendar_Setup_Update_Release8($this->_backend);
            $release8->update_8();
        }
        $this->setApplicationVersion('Calendar', '9.1');
    }

    /**
     * update to 9.2
     *
     * @see 0011266: increase size of event fields summary and location
     */
    public function update_1()
    {
        if ($this->getTableVersion('cal_events') < 11) {
            $release8 = new Calendar_Setup_Update_Release8($this->_backend);
            $release8->update_9();
        }
        $this->setApplicationVersion('Calendar', '9.2');
    }

    /**
     * update to 9.3
     *
     * @see 0011312: Make resource notification handling and default status configurable
     */
    public function update_2()
    {
        if ($this->getTableVersion('cal_resources') < 3) {
            $release8 = new Calendar_Setup_Update_Release8($this->_backend);
            $release8->update_10();
        }
        $this->setApplicationVersion('Calendar', '9.3');
    }

    /**
     * force activesync calendar resync for iOS devices
     */
    public function update_3()
    {
        $release8 = new Calendar_Setup_Update_Release8($this->_backend);
        $release8->update_11();
        $this->setApplicationVersion('Calendar', '9.4');
    }

    /**
     * update to 9.5
     *
     * add rrule_constraints
     */
    public function update_4()
    {
        if (!$this->_backend->columnExists('rrule_constraints', 'cal_events')) {
            $seqCol = '<field>
                <name>rrule_constraints</name>
                <type>text</type>
                <notnull>false</notnull>
            </field>';

            $declaration = new Setup_Backend_Schema_Field_Xml($seqCol);
            $this->_backend->addCol('cal_events', $declaration);
        }


        $this->setTableVersion('cal_events', '12');
        $this->setApplicationVersion('Calendar', '9.5');
    }

    /**
     * update to 9.6
     *
     * add resource busy_type
     */
    public function update_5()
    {
        if (!$this->_backend->columnExists('busy_type', 'cal_resources')) {
            $col = '<field>
                <name>busy_type</name>
                <type>text</type>
                <length>32</length>
                <default>BUSY</default>
                <notnull>true</notnull>
            </field>';

            $declaration = new Setup_Backend_Schema_Field_Xml($col);
            $this->_backend->addCol('cal_resources', $declaration);
        }

        $this->setTableVersion('cal_resources', '4');
        $this->setApplicationVersion('Calendar', '9.6');
    }

    /**
     * update to 9.7
     *
     * add rrule_constraints background job
     */
    public function update_6()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Calendar_Scheduler_Task::addUpdateConstraintsExdatesTask($scheduler);

        $this->setApplicationVersion('Calendar', '9.7');
    }
}
