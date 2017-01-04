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

    /**
     * update to 9.8
     *
     * Update Calendar Import Export definitions
     */
    public function update_7()
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName('Calendar');
        Setup_Controller::getInstance()->createImportExportDefinitions($app);
        $this->setApplicationVersion('Calendar', '9.8');
    }

    /**
     * fix displaycontainer in organizers attendee records
     */
    public function update_8()
    {
        $allBroken = $this->_db->query(
            "SELECT " . $this->_db->quoteIdentifier('events.id') . " AS " . $this->_db->quoteIdentifier('event_id') . "," .
            $this->_db->quoteIdentifier('events.is_deleted') . " AS " . $this->_db->quoteIdentifier('event_is_deleted') . "," .
            $this->_db->quoteIdentifier('events.container_id') . "," .
            $this->_db->quoteIdentifier('events.seq') . "," .
            $this->_db->quoteIdentifier('attendee.id') . " AS " . $this->_db->quoteIdentifier('attendee_id') .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") . " AS " . $this->_db->quoteIdentifier('events') .
            " INNER JOIN " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "container") . " AS " . $this->_db->quoteIdentifier('container') . " ON " .
            $this->_db->quoteIdentifier('events.container_id') . " = " . $this->_db->quoteIdentifier('container.id') . " AND " .
            $this->_db->quoteIdentifier('container.type') . " = " . $this->_db->quote('personal') .
            " INNER JOIN " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "accounts") . " AS " . $this->_db->quoteIdentifier('owner') . " ON " .
            $this->_db->quoteIdentifier('container.owner_id') . " = " . $this->_db->quoteIdentifier('owner.id') .
            " INNER JOIN " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_attendee") . " AS " . $this->_db->quoteIdentifier('attendee') . " ON " .
            $this->_db->quoteIdentifier('attendee.cal_event_id') . " = " . $this->_db->quoteIdentifier('events.id') . " AND " .
            $this->_db->quoteIdentifier('attendee.user_id') . " = " . $this->_db->quoteIdentifier('owner.contact_id') . " AND " .
            $this->_db->quoteIdentifier('attendee.user_type') . " IN (" . $this->_db->quote(array('user', 'groupmember')) . ") AND " .
            $this->_db->quoteIdentifier('attendee.displaycontainer_id') . " != " . $this->_db->quoteIdentifier('events.container_id')
        )->fetchAll(Zend_Db::FETCH_ASSOC);

        foreach ($allBroken as $broken) {
            $time = Tinebase_DateTime::now()->toString();

            $this->_db->query("START TRANSACTION");
            $this->_db->query(
                "UPDATE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_attendee") .
                " SET " . $this->_db->quoteIdentifier("displaycontainer_id") . " = " . $this->_db->quote($broken['container_id']) .
                " WHERE " . $this->_db->quoteIdentifier("id") . " = " . $this->_db->quote($broken['attendee_id'])
            );

            // care for consistent history for client updates
            if (!$broken['event_is_deleted']) {
                $this->_db->query(
                    "UPDATE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                    " SET " . $this->_db->quoteIdentifier("seq") . " = (" . $this->_db->quoteIdentifier('seq') . " +1)," .
                    $this->_db->quoteIdentifier("last_modified_time") . " = " . $this->_db->quote($time) .
                    " WHERE " . $this->_db->quoteIdentifier("id") . " = " . $this->_db->quote($broken['event_id'])
                );
                $this->_db->query(
                    "UPDATE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "container") .
                    " SET " . $this->_db->quoteIdentifier("content_seq") . " = (" . $this->_db->quoteIdentifier('content_seq') . " +1)" .
                    " WHERE " . $this->_db->quoteIdentifier("id") . " = " . $this->_db->quote($broken['container_id'])
                );
                $content_seq = Tinebase_Helper::array_value('content_seq', Tinebase_Helper::array_value(0, $this->_db->query(
                    "SELECT " . $this->_db->quoteIdentifier('content_seq') .
                    " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "container") .
                    " WHERE " . $this->_db->quoteIdentifier('id') . " = " . $this->_db->quote($broken['container_id'])
                )->fetchAll(Zend_Db::FETCH_ASSOC)));
                $this->_db->query(
                    "INSERT INTO" . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "container_content") .
                    " (" .
                    $this->_db->quoteIdentifier("id") . "," .
                    $this->_db->quoteIdentifier("container_id") . "," .
                    $this->_db->quoteIdentifier("record_id") . "," .
                    $this->_db->quoteIdentifier("action") . "," .
                    $this->_db->quoteIdentifier("content_seq") . "," .
                    $this->_db->quoteIdentifier("time") .
                    ") VALUES (" .
                    $this->_db->quote(Tinebase_Record_Abstract::generateUID()) . "," .
                    $this->_db->quote($broken['container_id']) . "," .
                    $this->_db->quote($broken['event_id']) . "," .
                    $this->_db->quote('update') . "," .
                    $this->_db->quote($content_seq) . "," .
                    $this->_db->quote($time) .
                    ")"
                );
            }
            $this->_db->query("COMMIT");
        }

        $this->setApplicationVersion('Calendar', '9.9');
    }

    /**
     * update to 10.0
     *
     * @return void
     */
    public function update_9()
    {
        $this->setApplicationVersion('Calendar', '10.0');
    }
}
