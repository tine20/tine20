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
 * class to handle MeetingResponse request
 *
 * @package     Model
 * @property    int     userResponse
 * @property    string  collectionId
 * @property    string  calendarId
 * @property    string  requestId
 * @property    string  instanceId
 * @property    string  longId
 */

class Syncroton_Model_MeetingResponse extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Request';
    
    /**
     * attendee status
     */
    const RESPONSE_ACCEPTED  = 1;
    const RESPONSE_TENTATIVE = 2;
    const RESPONSE_DECLINED  = 3;
    
    protected $_properties = array(
        'MeetingResponse' => array(
            'userResponse'  => array('type' => 'number'),
            'collectionId'  => array('type' => 'string'),
            'calendarId'    => array('type' => 'string'),
            'requestId'     => array('type' => 'string'),
            'instanceId'    => array('type' => 'datetime'),
        ),
        'Search' => array(
            'longId'        => array('type' => 'string')
        )
    );
}