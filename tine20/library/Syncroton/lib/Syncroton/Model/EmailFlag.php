<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012-2012 Kolab Systems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * class to handle ActiveSync Flag element
 *
 * @package     Model
 * @property    DateTime  CompleteTime
 * @property    DateTime  DateCompleted
 * @property    DateTime  DueDate
 * @property    string    FlagType
 * @property    DateTime  OrdinalDate
 * @property    int       ReminderSet
 * @property    DateTime  ReminderTime
 * @property    DateTime  StartDate
 * @property    string    Status
 * @property    string    Subject
 * @property    string    SubOrdinalDate
 * @property    DateTime  UtcDueDate
 * @property    DateTime  UtcStartDate
 */
class Syncroton_Model_EmailFlag extends Syncroton_Model_AEntry
{
    const STATUS_CLEARED  = 0;
    const STATUS_COMPLETE = 1;
    const STATUS_ACTIVE   = 2;

    protected $_xmlBaseElement = 'Flag';

    protected $_properties = array(
        'Email' => array(
            'completeTime'       => array('type' => 'datetime'),
            'flagType'           => array('type' => 'string'),
            'status'             => array('type' => 'number'),
        ),
        'Tasks' => array(
            'dateCompleted'      => array('type' => 'datetime'),
            'dueDate'            => array('type' => 'datetime'),
            'ordinalDate'        => array('type' => 'datetime'),
            'reminderSet'        => array('type' => 'number'),
            'reminderTime'       => array('type' => 'datetime'),
            'startDate'          => array('type' => 'datetime'),
            'subject'            => array('type' => 'string'),
            'subOrdinalDate'     => array('type' => 'string'),
            'utcStartDate'       => array('type' => 'datetime'),
            'utcDueDate'         => array('type' => 'datetime'),
        ),
    );
}
