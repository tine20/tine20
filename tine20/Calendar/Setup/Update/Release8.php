<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */
class Calendar_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * - move ack & snooze time from attendee to alarm
     */
    public function update_0()
    {
        // find all events with ack or snooze times set
        $eventIds = $this->_db->query(
            "SELECT DISTINCT " . $this->_db->quoteIdentifier('cal_event_id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_attendee") .
                " WHERE " . $this->_db->quoteIdentifier("alarm_ack_time") . " IS NOT NULL OR ". $this->_db->quoteIdentifier("alarm_snooze_time") . " IS NOT NULL"
            )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $attendeeBE = new Calendar_Backend_Sql_Attendee();
        $alarmBE = Tinebase_Alarm::getInstance();
        
        foreach ($eventIds as $eventId) {
            $eventId = $eventId['cal_event_id'];
            
            $attendeeFilter = new Tinebase_Model_Filter_FilterGroup();
            $attendeeFilter->addFilter(new Tinebase_Model_Filter_Text('cal_event_id', 'equals', $eventId));
            $attendees = $attendeeBE->search($attendeeFilter);
            
            $alarms = $alarmBE->search(new Tinebase_Model_AlarmFilter(array(
                array('field' => 'model',     'operator' => 'equals', 'value' =>'Calendar_Model_Event'),
                array('field' => 'record_id', 'operator' => 'equals', 'value' => $eventId)
            )));
            
            foreach ($alarms as $alarm) {
                foreach ($attendees as $attendee) {
                    if ($attendee->alarm_ack_time instanceof Tinebase_DateTime) {
                        $alarm->setOption("acknowledged-{$attendee->user_id}", $attendee->alarm_ack_time->format(Tinebase_Record_Abstract::ISO8601LONG));
                    }
                    if ($attendee->alarm_snooze_time instanceof Tinebase_DateTime) {
                        $alarm->setOption("snoozed-{$attendee->user_id}", $attendee->alarm_snooze_time->format(Tinebase_Record_Abstract::ISO8601LONG));
                    }
                    
                }
                $alarmBE->update($alarm);
            }
        }
        
        // delte ack & snooze from attendee
        $this->_backend->dropCol('cal_attendee', 'alarm_ack_time');
        $this->_backend->dropCol('cal_attendee', 'alarm_snooze_time');
        
        $this->setTableVersion('cal_attendee', 5);
        $this->setApplicationVersion('Calendar', '8.1');
    }
    
    /**
     * update to 8.2
     * 
     */
    public function update_1()
    {
        $eventBE = new Tinebase_Backend_Sql(array(
                'modelName'    => 'Calendar_Model_Event',
                'tableName'    => 'cal_events',
                'modlogActive' => false
        ));
        // find all events without organizer
        $eventIds = $this->_db->query(
                "SELECT " . $this->_db->quoteIdentifier('id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                " WHERE " . $this->_db->quoteIdentifier("organizer") . " IS NULL OR " .
                            $this->_db->quoteIdentifier("organizer") . " = ''"
        )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach ($eventIds as $eventId) {
            $event = $eventBE->get($eventId['id']);
            $event->organizer = (string) Tinebase_User::getInstance()->getFullUserById($event->created_by)->contact_id;
            
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $eventId),
            );
            
            try {
                $this->_db->update(SQL_TABLE_PREFIX . "cal_events", $event->toArray(), $where);
            } catch (Tinebase_Exception_Record_Validation $terv) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Could not fix invalid event record: ' . print_r($event->toArray(), true));
                Tinebase_Exception::log($terv);
            }
        }
        
        // find all events CONFIDENTIAL class
        $eventIds = $this->_db->query(
                "SELECT " . $this->_db->quoteIdentifier('id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                " WHERE " . $this->_db->quoteIdentifier("class") . " = 'CONFIDENTIAL'"
        )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach ($eventIds as $eventId) {
            $event = $eventBE->get($eventId['id']);
            $class = Calendar_Model_Event::CLASS_PRIVATE;
            $event->class = (string) $class;
            
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $eventId),
            );
            
            try {
                $this->_db->update(SQL_TABLE_PREFIX . "cal_events", $event->toArray(), $where);
            } catch (Tinebase_Exception_Record_Validation $terv) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Could not fix invalid event record: ' . print_r($event->toArray(), true));
                Tinebase_Exception::log($terv);
            }
        }
         $this->setApplicationVersion('Calendar', '8.2');
    }
    
    /**
     * update to 8.3
     * - normalize all rrules
     */
    public function update_2()
    {
        // find all events with rrule
        $eventIds = $this->_db->query(
                "SELECT " . $this->_db->quoteIdentifier('id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                " WHERE " . $this->_db->quoteIdentifier("rrule") . " IS NOT NULL"
        )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // NOTE: we need a generic sql BE to circumvent calendar specific acl issues
        $eventBE = new Tinebase_Backend_Sql(array(
                'modelName'    => 'Calendar_Model_Event',
                'tableName'    => 'cal_events',
                'modlogActive' => false
        ));
        
        foreach ($eventIds as $eventId) {
            $event = $eventBE->get($eventId['id']);
            $oldRruleString = (string) $event->rrule;
            $rrule = Calendar_Model_Rrule::getRruleFromString($oldRruleString);
            $rrule->normalize($event);
            
            if ($oldRruleString != (string) $rrule) {
                $event->rrule = (string) $rrule;
                try {
                    $eventBE->update($event);
                } catch (Tinebase_Exception_Record_Validation $terv) {
                    Tinebase_Exception::log($terv, null, $event->toArray());
                } catch (Tinebase_Exception_UnexpectedValue $teuv) {
                    Tinebase_Exception::log($teuv, null, $event->toArray());
                }
            }
        }
        
        $this->setApplicationVersion('Calendar', '8.3');
    }
    
    /**
     * update to 8.4
     * 
     * - adds etag column
     */
    public function update_3()
    {
        if (! $this->_backend->columnExists('etag', 'cal_events')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>etag</name>
                    <type>text</type>
                    <length>60</length>
                </field>');
            $this->_backend->addCol('cal_events', $declaration);

            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>etag</name>
                    <field>
                        <name>etag</name>
                    </field>
                </index>');
            $this->_backend->addIndex('cal_events', $declaration);
        }
        
        $this->setTableVersion('cal_events', 7);
        $this->setApplicationVersion('Calendar', '8.4');
    }
    
    /**
     * - update import / export
     */
    public function update_4()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Calendar'));
        $this->setTableVersion('cal_events', 7);
        $this->setApplicationVersion('Calendar', '8.5');
    }
    
    /**
     * adds external_seq col
     * 
     * @see 0009890: improve external event invitation support
     */
    public function update_5()
    {
        if (! $this->_backend->columnExists('external_seq', 'cal_events')) {
            $seqCol = '<field>
                <name>external_seq</name>
                <type>integer</type>
                <notnull>true</notnull>
                <default>0</default>
            </field>';
            
            $declaration = new Setup_Backend_Schema_Field_Xml($seqCol);
            $this->_backend->addCol('cal_events', $declaration);
        }
        
        $this->setTableVersion('cal_events', '8');
        $this->setApplicationVersion('Calendar', '8.6');
    }
    
    /**
     * add rrule index
     * 
     * @see 0010214: improve calendar performance / yearly base events
     *
     * TODO re-enable this when it is fixed for postgresql
     * @see 0011194: only drop index if it exists
     */
    public function update_6()
    {
//        $declaration = new Setup_Backend_Schema_Index_Xml('
//            <index>
//                <name>rrule</name>
//                <field>
//                    <name>rrule</name>
//                </field>
//            </index>');
//        try {
//            $this->_backend->addIndex('cal_events', $declaration);
//        } catch (Zend_Db_Statement_Exception $e) {
//            Tinebase_Exception::log($e);
//        }
        
        $this->setTableVersion('cal_events', '9');
        $this->setApplicationVersion('Calendar', '8.7');
    }

    /**
     * repair missing displaycontainer_id
     */
    public function update_7()
    {
        $allUser = $this->_db->query(
            "SELECT " . $this->_db->quoteIdentifier('id') . "," . $this->_db->quoteIdentifier('contact_id') .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "accounts") .
            " WHERE " . $this->_db->quoteIdentifier("contact_id") . " IS NOT NULL"
        )->fetchAll(Zend_Db::FETCH_ASSOC);

        $contactUserMap = array();
        foreach ($allUser as $id => $user) {
            $contactUserMap[$user['contact_id']] = $user['id'];
        }

        // find all user/groupmember attendees with missing displaycontainer
        $attendees = $this->_db->query(
            "SELECT DISTINCT" . $this->_db->quoteIdentifier('user_type') . "," . $this->_db->quoteIdentifier('user_id') .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_attendee") .
            " WHERE " . $this->_db->quoteIdentifier("displaycontainer_id") . " IS  NULL" .
            "  AND " . $this->_db->quoteIdentifier("user_type") . $this->_db->quoteInto(" IN (?)", array('user', 'groupmemeber')) .
            "  AND " . $this->_db->quoteIdentifier("user_id") . $this->_db->quoteInto(" IN (?)", array_keys($contactUserMap))
        )->fetchAll(Zend_Db::FETCH_ASSOC);

        // find all user/groupmember attendees with missing displaycontainer
        $attendees = array_merge($attendees, $this->_db->query(
            "SELECT DISTINCT" . $this->_db->quoteIdentifier('user_type') . "," . $this->_db->quoteIdentifier('user_id') .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_attendee") .
            " WHERE " . $this->_db->quoteIdentifier("displaycontainer_id") . " IS  NULL" .
            "  AND " . $this->_db->quoteIdentifier("user_type") . $this->_db->quoteInto(" IN (?)", array('resource'))
        )->fetchAll(Zend_Db::FETCH_ASSOC));

        $resources = $this->_db->query(
            "SELECT " . $this->_db->quoteIdentifier('id') . "," . $this->_db->quoteIdentifier('container_id') .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_resources")
        )->fetchAll(Zend_Db::FETCH_ASSOC);

        $resourceContainerMap = array();
        foreach ($resources as $resource) {
            $resourceContainerMap[$resource['id']] = $resource['container_id'];
        }

        foreach ($attendees as $attendee) {
            //find out displaycontainer
            if ($attendee['user_type'] != 'resource') {
                $userAccountId = $contactUserMap[$attendee['user_id']];
                try {
                    $attendee['displaycontainerId'] = Calendar_Controller_Event::getDefaultDisplayContainerId($userAccountId);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " Could not find user with id " . $attendee['user_id']);
                    continue;
                }
            } else {
                $attendee['displaycontainerId'] = $resourceContainerMap[$attendee['user_id']];
            }

            // update displaycontainer
            $this->_db->query(
                "UPDATE" . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_attendee") .
                " SET " . $this->_db->quoteIdentifier("displaycontainer_id") . " = " . $this->_db->quote($attendee['displaycontainerId']) .
                " WHERE " . $this->_db->quoteIdentifier("user_type") . " = " . $this->_db->quote($attendee['user_type']) .
                "  AND " . $this->_db->quoteIdentifier("user_id") . " = " . $this->_db->quote($attendee['user_id'])
            );
        }

        $this->setApplicationVersion('Calendar', '8.8');
    }

    /**
     * identify base event via new base_event_id field instead of UID
     */
    public function update_8()
    {
        /* find possibly broken events
         SELECT group_concat(id), uid, count(id) as cnt from tine20_cal_events
             WHERE rrule IS NOT NULL
             GROUP BY uid
             HAVING cnt > 1;
         */

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>base_event_id</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->addCol('cal_events', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>base_event_id</name>
                <field>
                    <name>base_event_id</name>
                </field>
            </index>');
        $this->_backend->addIndex('cal_events', $declaration);

        // find all events with rrule
        $events = $this->_db->query(
            "SELECT " . $this->_db->quoteIdentifier('id') .
                 ', ' . $this->_db->quoteIdentifier('uid') .
                 ', ' . $this->_db->quoteIdentifier('container_id') .
                 ', ' . $this->_db->quoteIdentifier('created_by') .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
            " WHERE " . $this->_db->quoteIdentifier("rrule") . " IS NOT NULL" .
              " AND " . $this->_db->quoteIdentifier("is_deleted") . " = " . $this->_db->quote(0, Zend_Db::INT_TYPE)
        )->fetchAll(Zend_Db::FETCH_ASSOC);

        // update all exdates in same container
        foreach($events as $event) {
            $this->_db->query(
                "UPDATE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                  " SET " . $this->_db->quoteIdentifier('base_event_id') . ' = ' . $this->_db->quote($event['id']) .
                " WHERE " . $this->_db->quoteIdentifier('uid') . ' = ' . $this->_db->quote($event['uid']) .
                  " AND " . $this->_db->quoteIdentifier("container_id") . ' = ' . $this->_db->quote($event['container_id']) .
                  " AND " . $this->_db->quoteIdentifier("recurid") . " IS NOT NULL" .
                  " AND " . $this->_db->quoteIdentifier("is_deleted") . " = " . $this->_db->quote(0, Zend_Db::INT_TYPE)
            );
        }

        // find all container move exdates
        $danglingExdates = $this->_db->query(
            "SELECT " . $this->_db->quoteIdentifier('uid') .
                ', ' . $this->_db->quoteIdentifier('id') .
                ', ' . $this->_db->quoteIdentifier('created_by') .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
            " WHERE " . $this->_db->quoteIdentifier("recurid") . " IS NOT NULL" .
              " AND " . $this->_db->quoteIdentifier("base_event_id") . " IS NULL" .
              " AND " . $this->_db->quoteIdentifier("is_deleted") . " = " . $this->_db->quote(0, Zend_Db::INT_TYPE)
        )->fetchAll(Zend_Db::FETCH_ASSOC);

        // try to match by creator
        foreach ($danglingExdates as $exdate) {
            $possibleBaseEvents = array();
            $matches = array_filter($events, function ($event) use ($exdate, $possibleBaseEvents) {
                if ($event['uid'] == $exdate['uid']) {
                    $possibleBaseEvents[] = $event;
                    return $event['created_by'] == $exdate['created_by'];
                }
                return false;
            });

            switch(count($matches)) {
                case 0:
                    // no match :-(
                    if (count($possibleBaseEvents) == 0) {
                        // garbage? exdate without any base event
                        Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " dangling exdate with id {$exdate['id']}");
                        continue 2;
                    }
                    Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " no match for exdate with id {$exdate['id']}");
                    $baseEvent = current($possibleBaseEvents);
                    break;
                case 1:
                    // exact match :-)
                    $baseEvent = current($matches);
                    break;
                default:
                    // to much matches :-(
                    Setup_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " multiple matches for exdate with id {$exdate['id']}");
                    $baseEvent = current($matches);
            }

            $this->_db->query(
                "UPDATE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                " SET " . $this->_db->quoteIdentifier('base_event_id') . ' = ' . $this->_db->quote($baseEvent['id']) .
                " WHERE " . $this->_db->quoteIdentifier('id') . ' = ' . $this->_db->quote($exdate['id'])
            );
        }

        $this->setTableVersion('cal_events', '10');
        $this->setApplicationVersion('Calendar', '8.9');
    }

    /**
     * @see 0011266: increase size of event fields summary and location
     */
    public function update_9()
    {
        $fieldsToChange = array('location', 'summary');

        foreach ($fieldsToChange as $name) {
            $seqCol = '<field>
                <name>' . $name . '</name>
                <type>text</type>
                <length>1024</length>
            </field>';

            $declaration = new Setup_Backend_Schema_Field_Xml($seqCol);
            $this->_backend->alterCol('cal_events', $declaration);
        }

        $this->setTableVersion('cal_events', 11);
        $this->setApplicationVersion('Calendar', '8.10');
    }

    /**
     * 
     */
    public function update_10()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
                <default>NEEDS-ACTION</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->addCol('cal_resources', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>status</name>
                <field>
                    <name>status</name>
                </field>
            </index>');
        $this->_backend->addIndex('cal_resources', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>suppress_notification</name>
                <type>boolean</type>
                <default>false</default>
            </field>');
        $this->_backend->addCol('cal_resources', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>suppress_notification</name>
                <field>
                    <name>suppress_notification</name>
                </field>
            </index>');
        $this->_backend->addIndex('cal_resources', $declaration);

        $this->setTableVersion('cal_resources', '3');

        $this->setApplicationVersion('Calendar', '8.11');
    }

    /**
     * force activesync calendar resync for iOS devices
     */
    public function update_11()
    {
        $deviceBackend = new ActiveSync_Backend_Device();
        $usersWithiPhones = $deviceBackend->search(new ActiveSync_Model_DeviceFilter(array(
            'devicetype' => 'iphone'
        )), NULL, 'owner_id');

        $activeSyncController = ActiveSync_Controller::getInstance();
        foreach($usersWithiPhones as $userId) {
            $activeSyncController->resetSyncForUser($userId, 'Calendar');
        }

        $this->setApplicationVersion('Calendar', '8.12');
    }
}
