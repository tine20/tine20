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
 * exception for Status element in FolderCreate response
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status_FolderCreate extends Syncroton_Exception_Status
{
    const FOLDER_EXISTS       = 2;
    const SPECIAL_FOLDER      = 3;
    const PARENT_NOT_FOUND    = 5;
    const FOLDER_SERVER_ERROR = 6;
    const INVALID_SYNCKEY     = 9;
    const INVALID_REQUEST     = 10;
    const UNKNOWN_ERROR       = 11;
    const UNKNOWN_CODE        = 12;

    /**
     * Error messages assigned to error codes
     *
     * @var array
     */
    protected $_errorMessages = array(
        self::FOLDER_EXISTS       => "A folder that has this name already exists",
        self::SPECIAL_FOLDER      => "The specified folder is a special system folder",
        self::PARENT_NOT_FOUND    => "The specified parent folder was not found",
        self::FOLDER_SERVER_ERROR => "An error occurred on the server",
        self::INVALID_SYNCKEY     => "Synchronization key mismatch or invalid synchronization key",
        self::INVALID_REQUEST     => "Malformed request",
        self::UNKNOWN_ERROR       => "An unknown error occurred",
        self::UNKNOWN_CODE        => "Unusual back-end issue",
    );
}
