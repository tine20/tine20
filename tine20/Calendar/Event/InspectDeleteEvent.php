<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * event class for event deletion inspection
 *
 * @package     Calendar
 */
class Calendar_Event_InspectDeleteEvent extends Tinebase_Event_Observer_Abstract
{
    /**
     * the event to inspect
     *
     * @var Calendar_Model_Event
     */
    public $observable;

    /**
     * All deleted ids
     *
     * @var array
     */
    public $deletedIds;
}