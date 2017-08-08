<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * event class for event inspection
 *
 * @package     Calendar
 */
class Calendar_Event_InspectEvent extends Tinebase_Event_Observer_Abstract
{
    /**
     * the event to inspect
     *
     * @var Calendar_Model_Event
     */
    public $observable;
}