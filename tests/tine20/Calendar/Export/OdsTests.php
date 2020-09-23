<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 */

/**
 * Test class for Calendar_Export_Ods
 */
class Calendar_Export_OdsTests extends Calendar_TestCase
{
    /**
     * TODO add assertions
     */
    public function testExportOds()
    {
        // export & check
        $csvExportClass = new Calendar_Export_Ods(new Calendar_Model_EventFilter(array()));
        $csvExportClass->generate();
    }

    public function testExportWithAttendeeOdsSheet()
    {
        // @TODO have some demodata to export here

        $calendar = $this->_getTestCalendar();

        $filter = new Calendar_Model_EventFilter([
            ['field' =>	'query', 'operator' => 'contains', 'value' => ''],
            ['field' => 'period', 'operator' => 'within', 'value' => [
                'from' => '2020-09-21 00:00:00',
                'until' => '2020-09-28 00:00:00',
            ]],
            ['condition' => 'OR', 'filters' => [
                ['field' => 'attender_status', 'operator' => 'notin', 'value' => 'DECLINED'],
                ['field' => 'container_id', 'operator' => 'equals', 'value' => $calendar->getId()],
                ['field' => 'attender', 'operator' => 'in', 'value' => [
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => $this->_getPersonasContacts('sclever')->getId()
                ]]] , 'id' => 'FilterPanel']
        ]);

        $doc = new Calendar_Export_Ods($filter);
        $doc->generate();
    }
}
