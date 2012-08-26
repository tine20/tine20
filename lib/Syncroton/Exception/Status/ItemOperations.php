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
 * Exception for Status element in ItemOperations response
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status_ItemOperations extends Syncroton_Exception_Status
{
    const PROTOCOL_ERROR         = 2;
    const ITEM_SERVER_ERROR      = 3;
    const DOCLIB_INVALID_URI     = 4;
    const DOCLIB_ACCESS_DENIED   = 5;
    const DOCLIB_NOT_FOUND       = 6;
    const DOCLIB_CONN_FAILED     = 7;
    const INVALID_BYTE_RANGE     = 8;
    const UNKNOWN_STORE          = 9;
    const FILE_EMPTY             = 10;
    const DATA_TOO_LARGE         = 11;
    const FILE_IO_ERROR          = 12;
    const CONVERSION_ERROR       = 14;
    const INVALID_ATTACHMENT     = 15;
    const RESOURCE_ACCESS_DENIED = 16;
    const PARTIAL_SUCCESS        = 17;
    const CREDENTIALS_REQUIRED   = 18;

    /**
     * Error messages assigned to error codes
     *
     * @var array
     */
    protected $_errorMessages = array(
        self::PROTOCOL_ERROR         => "Protocol error - protocol violation/XML validation error",
        self::ITEM_SERVER_ERROR      => "Server error",
        self::DOCLIB_INVALID_URI     => "Document library access - The specified URI is bad",
        self::DOCLIB_ACCESS_DENIED   => "Document library - Access denied",
        self::DOCLIB_NOT_FOUND       => "Document library - The object was not found or access denied",
        self::DOCLIB_CONN_FAILED     => "Document library - Failed to connect to the server",
        self::INVALID_BYTE_RANGE     => "The byte-range is invalid or too large",
        self::UNKNOWN_STORE          => "The store is unknown or unsupported",
        self::FILE_EMPTY             => "The file is empty",
        self::DATA_TOO_LARGE         => "The requested data size is too large",
        self::FILE_IO_ERROR          => "Failed to download file because of input/output (I/O) failure",
        self::CONVERSION_ERROR       => "Mailbox fetch provider - The item failed conversion",
        self::INVALID_ATTACHMENT     => "Attachment fetch provider - Attachment or attachment ID is invalid",
        self::RESOURCE_ACCESS_DENIED => "Access to the resource is denied",
        self::PARTIAL_SUCCESS        => "Partial success; the command completed partially",
        self::CREDENTIALS_REQUIRED   => "Credentials required",
    );
}
