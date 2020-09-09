<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ingo Ratsdorf <ingo@envirology.co.nz>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a iphone vcalendar to event model and back again
 *
 * @package     Tasks
 * @subpackage  Convert
 */
class Tasks_Convert_Task_VCalendar_DavDroid extends Tasks_Convert_Task_VCalendar_Abstract
{
    // DAVdroid/0.7.2
    // DAVx5/2.2.1-gplay
    const HEADER_MATCH = '/(DAVdroid|DAVx5)\/(?P<version>.*)/';
    
    protected $_supportedFields = array(
        'seq',
        'dtstart',
        #'transp',
        'class',
        'description',
        'geo',
        'location',
        'priority',
        'summary',
        'url',
        'alarms',
        'tags',
        'status',
        'due',
        'percent',
        'completed',
        'originator_tz'
    );
}
