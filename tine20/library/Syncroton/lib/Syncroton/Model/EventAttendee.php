<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync event
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */

class Syncroton_Model_EventAttendee extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Attendee';
    
    /**
     * attendee status
     */
    const ATTENDEE_STATUS_UNKNOWN       = 0;
    const ATTENDEE_STATUS_TENTATIVE     = 2;
    const ATTENDEE_STATUS_ACCEPTED      = 3;
    const ATTENDEE_STATUS_DECLINED      = 4;
    const ATTENDEE_STATUS_NOTRESPONDED  = 5;
    
    /**
     * attendee types
     */
    const ATTENDEE_TYPE_REQUIRED = 1;
    const ATTENDEE_TYPE_OPTIONAL = 2;
    const ATTENDEE_TYPE_RESOURCE = 3;
    
    protected $_properties = array(
        'Calendar' => array(
            'attendeeStatus'          => array('type' => 'number'),
            'attendeeType'            => array('type' => 'number'),
            'email'                   => array('type' => 'string'),
            'name'                    => array('type' => 'string'),
        )
    );
}