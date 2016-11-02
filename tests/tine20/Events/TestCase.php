<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Events
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Events_TestCase
 */
class Events_TestCase extends TestCase
{
    /**
     * get Event record
     *
     * @return Events_Model_Event
     */
    protected function _getEvent()
    {
        return new Events_Model_Event(array(
            'title' => 'minimal example record by PHPUnit::Events_JsonTest',
            'event_dtstart'     => '2015-03-25 06:00:00',
            'event_dtend'       => '2015-03-25 06:15:00'
        ));
    }
    
    /**
     * get filter for Events search
     *
     * @return array
     */
    protected function _getFilter()
    {
        // define filter
        return array(
            array('field' => 'container_id', 'operator' => 'specialNode', 'value' => 'all'),
            array('field' => 'title'       , 'operator' => 'contains',    'value' => 'example record by PHPUnit'),
        );
    }
    
    /**
     * get default paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        // define paging
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'title',
            'dir' => 'ASC',
        );
    }
}
