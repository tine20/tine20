<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 */

/**
 * Test class for Calendar_Export_Ods
 */
class Calendar_Export_OdsTests extends Calendar_TestCase
{
    public function testExportOds()
    {
        // export & check
        $csvExportClass = new Calendar_Export_Ods(new Calendar_Model_EventFilter(array()));
        $result = $csvExportClass->generate();
    }
}