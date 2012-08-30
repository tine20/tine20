<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Exception
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012-2012 Kolab Systems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * exception for Status element in MeetingResponse response
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status_MeetingResponse extends Syncroton_Exception_Status
{
    const INVALID_REQUEST       = 2;
    const MEETING_SERVER_ERROR  = 3;
    const MEETING_ERROR         = 4;

    /**
     * Error messages assigned to error codes
     *
     * @var array
     */
    protected $_errorMessages = array(
        self::INVALID_REQUEST       => "Invalid meeting request",
        self::MEETING_SERVER_ERROR  => "An error occurred on the server mailbox",
        self::MEETING_ERROR         => "An error occurred on the server",
    );
}
