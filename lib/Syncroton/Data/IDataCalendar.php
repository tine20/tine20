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
 * interface for extended calendar backend
 *
 * @package     Model
 */
interface Syncroton_Data_IDataCalendar
{
    /**
     * set attendee status for meeting
     * 
     * @param  Syncroton_Model_MeetingResponse  $request  the meeting response
     * @return  string  id of new calendar entry
     */
    public function setAttendeeStatus(Syncroton_Model_MeetingResponse $request);
}

