<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tasks_Convert_Task_VCalendar_MacOSX
 */
class Tasks_Convert_Task_VCalendar_MacOSXTest extends TestCase
{
    /**
     * test converting vtodo without trigger valarm
     */
    public function testConvertToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/apple_valarm.ics', 'r');
    
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_MACOSX);
    
        $task = $converter->toTine20Model($vcalendarStream);
        
        $this->assertEquals('Stundenaufstellung Heinz Walter', $task->summary, print_r($task->toArray(), true));
        $this->assertEquals(1, count($task->alarms), print_r($task->toArray(), true));
        $this->assertEquals('2014-09-12 06:00:00', $task->alarms->getFirstRecord()->alarm_time->toString(), print_r($task->toArray(), true));
    }
}
