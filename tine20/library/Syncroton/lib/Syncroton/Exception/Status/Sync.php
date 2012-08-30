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
 * exception for Status element in Sync response
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status_Sync extends Syncroton_Exception_Status
{
    const INVALID_SYNCKEY     = 3;
    const PROTOCOL_ERROR      = 4;
    const SYNC_SERVER_ERROR   = 5;
    const INVALID_ITEM        = 6;
    const OBJECT_CONFLICT     = 7;
    const OBJECT_NOT_FOUND    = 8;
    const SYNC_ERROR          = 9;
    const HIERARCHY_CHANGED   = 12;
    const INCOMPLETE_REQUEST  = 13;
    const INVALID_INTERVAL    = 14;
    const INVALID_REQUEST     = 15;
    const SYNC_RETRY          = 16;

    /**
     * Error messages assigned to error codes
     *
     * @var array
     */
    protected $_errorMessages = array(
        self::INVALID_SYNCKEY     => "Invalid synchronization key",
        self::PROTOCOL_ERROR      => "Protocol error",
        self::SYNC_SERVER_ERROR   => "Server error",
        self::INVALID_ITEM        => "Error in client/server conversion",
        self::OBJECT_CONFLICT     => "Conflict matching the client and server object",
        self::OBJECT_NOT_FOUND    => "Object not found",
        self::SYNC_ERROR          => "The Sync command cannot be completed",
        self::HIERARCHY_CHANGED   => "The folder hierarchy has changed",
        self::INCOMPLETE_REQUEST  => "The Sync command request is not complete",
        self::INVALID_INTERVAL    => "Invalid Wait or HeartbeatInterval value",
        self::INVALID_REQUEST     => "Too many collections are included in the Sync request",
        self::SYNC_RETRY          => "Something on the server caused a retriable error",
    );
}
