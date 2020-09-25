<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once 'TestHelper.php';

/**
 * Test class for Calendar_Convert_Event_VCalendar_DavDroid
 */
class Calendar_Convert_Event_VCalendar_DavDroidTest extends TestCase
{

    public function testDavDroidGroupAttendeeConvertFromTine20Model()
    {
        $group = Tinebase_Group::getInstance()->getDefaultGroup();

        $event = new Calendar_Model_Event([
            'dtstart' => Tinebase_DateTime::now()->addDay(1),
            'dtend' => Tinebase_DateTime::now()->addDay(1)->addHour(1),
            'creation_time' => Tinebase_DateTime::now(),
            'attendee' => [[
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'user_id' => $group->getId(),
            ]]
        ]);

        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_DAVDROID);

        $vevent = $converter->fromTine20Model($event)->serialize();
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        $domain = isset($smtpConfig['primarydomain']) ? '@' . $smtpConfig['primarydomain'] : '';

        $this->assertContains($group->list_id.$domain, $vevent, $vevent);

        return $vevent;
    }

    public function testDavDriodGroupAttendeeConvertToTine20Model()
    {
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_DAVDROID);

        $vcalendar = $this->testDavDroidGroupAttendeeConvertFromTine20Model();
        $event = $converter->toTine20Model($vcalendar);

        $this->assertCount(1, $event->attendee);
        $this->assertEquals(Calendar_Model_Attender::USERTYPE_GROUP, $event->attendee[0]->user_type);
        $this->assertEquals(Tinebase_Group::getInstance()->getDefaultGroup()->getId(), $event->attendee[0]->user_id);
    }
}
