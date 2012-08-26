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
 * Exception for Status element
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status extends Syncroton_Exception
{
    // http://msdn.microsoft.com/en-us/library/ee218647%28v=exchg.80%29
    const INVALID_CONTENT                    = 101;
    const INVALID_WBXML                      = 102;
    const INVALID_XML                        = 103;
    const INVALID_DATE_TIME                  = 104;
    const INVALID_COMBINATION_OF_IDS         = 105;
    const INVALID_IDS                        = 106;
    const INVALID_MIME                       = 107;
    const DEVICE_MISSING_OR_INVALID          = 108;
    const DEVICE_TYPE_MISSING_OR_INVALID     = 109;
    const SERVER_ERROR                       = 110;
    const SERVER_ERROR_RETRY_LATER           = 111;
    const ACTIVE_DIRECTORY_ACCESS_DENIED     = 112;
    const MAILBOX_QUOTA_EXCEEDED             = 113;
    const MAILBOX_SERVER_OFFLINE             = 114;
    const SEND_QUOTA_EXCEEDED                = 115;
    const MESSAGE_RECIPIENT_UNRESOLVED       = 116;
    const MESSAGE_REPLY_NOT_ALLOWED          = 117;
    const MESSAGE_PREVIOUSLY_SENT            = 118;
    const MESSAGE_HAS_NO_RECIPIENT           = 119;
    const MAIL_SUBMISSION_FAILED             = 120;
    const MESSAGE_REPLY_FAILED               = 121;
    const ATTACHMENT_IS_TOO_LARGE            = 122;
    const USER_HAS_NO_MAILBOX                = 123;
    const USER_CANNOT_BE_ANONYMOUS           = 124;
    const USER_PRINCIPAL_COULD_NOT_BE_FOUND  = 125;
    const USER_DISABLED_FOR_SYNC             = 126;
    const USER_ON_NEW_MAILBOX_CANNOT_SYNC    = 127;
    const USER_ON_LEGACY_MAILBOX_CANNOT_SYNC = 128;
    const DEVICE_IS_BLOCKED_FOR_THIS_USER    = 129;
    const ACCESS_DENIED                      = 130;
    const ACCOUNT_DISABLED                   = 131;
    const SYNC_STATE_NOT_FOUND               = 132;
    const SYNC_STATE_LOCKED                  = 133;
    const SYNC_STATE_CORRUPT                 = 134;
    const SYNC_STATE_ALREADY_EXISTS          = 135;
    const SYNC_STATE_VERSION_INVALID         = 136;
    const COMMAND_NOT_SUPPORTED              = 137;
    const VERSION_NOT_SUPPORTED              = 138;
    const DEVICE_NOT_FULLY_PROVISIONABLE     = 139;
    const REMOTE_WIPE_REQUESTED              = 140;
    const LEGACY_DEVICE_ON_STRICT_POLICY     = 141;
    const DEVICE_NOT_PROVISIONED             = 142;
    const POLICY_REFRESH                     = 143;
    const INVALID_POLICY_KEY                 = 144;
    const EXTERNALLY_MANAGED_DEVICES_NOT_ALLOWED = 145;
    const NO_RECURRENCE_IN_CALENDAR          = 146;
    const UNEXPECTED_ITEM_CLASS              = 147;
    const REMOTE_SERVER_HAS_NO_SSL           = 148;
    const INVALID_STORED_REQUEST             = 149;
    const ITEM_NOT_FOUND                     = 150;
    const TOO_MANY_FOLDERS                   = 151;
    const NO_FOLDERS_FOUND                   = 152;
    const ITEMS_LOST_AFTER_MOVE              = 153;
    const FAILURE_IN_MOVE_OPERATION          = 154;
    const MOVE_COMMAND_DISALLOWED            = 155;
    const MOVE_COMMAND_INVALID_DESTINATION   = 156;
    const AVAILABILITY_TO_MANY_RECIPIENTS    = 160;
    const AVAILABILITY_DL_LIMIT_REACHED      = 161;
    const AVAILABILITY_TRANSIENT_FAILURE     = 162;
    const AVAILABILITY_FAILURE               = 163;
    const BODY_PART_PREFERENCE_TYPE_NOT_SUPPORTED = 164;
    const DEVICE_INFORMATION_REQUIRED        = 165;
    const INVALID_ACCOUNT_ID                 = 166;
    const ACCOUNT_SEND_DISABLED              = 167;
    CONST IRM_FEATURE_DISABLED               = 168;
    const IRM_TRANSIENT_ERROR                = 169;
    const IRM_PERMANENT_ERROR                = 170;
    const IRM_INVALID_TEMPLATE_ID            = 171;
    const IRM_OPERATION_NOT_PERMITTED        = 172;
    const NO_PICTURE                         = 173;
    const PICTURE_TO_LARGE                   = 174;
    const PICTURE_LIMIT_REACHED              = 175;
    const BODY_PART_CONVERSATION_TOO_LARGE   = 176;
    const MAXIMUM_DEVICES_REACHED            = 177;

    /**
     * Common error messages assigned to error codes
     *
     * @var array
     */
    protected $_commonMessages = array(
        self::INVALID_CONTENT                    => "Invalid request body",
        self::INVALID_WBXML                      => "Invalid WBXML request",
        self::INVALID_XML                        => "Invalid XML request",
        self::INVALID_DATE_TIME                  => "Invalid datetime string",
        self::INVALID_COMBINATION_OF_IDS         => "Invalid combination of parameters",
        self::INVALID_IDS                        => "Invalid one or more IDs",
        self::INVALID_MIME                       => "Invalid MIME content",
        self::DEVICE_MISSING_OR_INVALID          => "Device ID invalid or missing",
        self::DEVICE_TYPE_MISSING_OR_INVALID     => "Device type invalid or missing",
        self::SERVER_ERROR                       => "Unknown server error",
        self::SERVER_ERROR_RETRY_LATER           => "Unknown server error. Device should retry later",
        self::ACTIVE_DIRECTORY_ACCESS_DENIED     => "No access to an object in the directory service",
        self::MAILBOX_QUOTA_EXCEEDED             => "The mailbox quota size exceeded",
        self::MAILBOX_SERVER_OFFLINE             => "The mailbox server is offline",
        self::SEND_QUOTA_EXCEEDED                => "The request would exceed the send quota",
        self::MESSAGE_RECIPIENT_UNRESOLVED       => "Recipient could not be resolved to an e-mail address",
        self::MESSAGE_REPLY_NOT_ALLOWED          => "The mailbox server doesn't allow a reply of this message",
        self::MESSAGE_PREVIOUSLY_SENT            => "The message was already sent in a previous request",
        self::MESSAGE_HAS_NO_RECIPIENT           => "The message being sent contains no recipient",
        self::MAIL_SUBMISSION_FAILED             => "The server failed to submit the message for delivery",
        self::MESSAGE_REPLY_FAILED               => "The server failed to create a reply message",
        self::ATTACHMENT_IS_TOO_LARGE            => "The attachment is too large",
        self::USER_HAS_NO_MAILBOX                => "A mailbox could not be found for the user",
        self::USER_CANNOT_BE_ANONYMOUS           => "The request was sent without credentials. Anonymous requests are not allowed",
        self::USER_PRINCIPAL_COULD_NOT_BE_FOUND  => "The user was not found in the directory service",
        self::USER_DISABLED_FOR_SYNC             => "This user is not allowed to use ActiveSync",
        self::USER_ON_NEW_MAILBOX_CANNOT_SYNC    => "The server is configured to prevent users from syncing",
        self::USER_ON_LEGACY_MAILBOX_CANNOT_SYNC => "The server is configured to prevent users on legacy servers from syncing",
        self::DEVICE_IS_BLOCKED_FOR_THIS_USER    => "This device is not the allowed device",
        self::ACCESS_DENIED                      => "The user is not allowed to perform that request",
        self::ACCOUNT_DISABLED                   => "The user's account is disabled",
        self::SYNC_STATE_NOT_FOUND               => "Missing data file that contains the state of the client",
        self::SYNC_STATE_LOCKED                  => "Locked data file that contains the state of the client",
        self::SYNC_STATE_CORRUPT                 => "Corrupted data file that contains the state of the client",
        self::SYNC_STATE_ALREADY_EXISTS          => "The data file that contains the state of the client already exists",
        self::SYNC_STATE_VERSION_INVALID         => "Version of the data file that contains the state of the client is invalid",
        self::COMMAND_NOT_SUPPORTED              => "The command is not supported by this server",
        self::VERSION_NOT_SUPPORTED              => "The command is not supported in the protocol version specified",
        self::DEVICE_NOT_FULLY_PROVISIONABLE     => "The device uses a protocol version that cannot send all the policy settings the admin enabled",
        self::REMOTE_WIPE_REQUESTED              => "A remote wipe was requested",
        self::LEGACY_DEVICE_ON_STRICT_POLICY     => "A policy is in place but the device is not provisionable",
        self::DEVICE_NOT_PROVISIONED             => "There is a policy in place",
        self::POLICY_REFRESH                     => "The policy is configured to be refreshed every few hours",
        self::INVALID_POLICY_KEY                 => "The device's policy key is invalid",
        self::EXTERNALLY_MANAGED_DEVICES_NOT_ALLOWED => "The server doesn't allow externally managed devices to sync",
        self::NO_RECURRENCE_IN_CALENDAR          => "The request tried to forward an occurrence of a meeting that has no recurrence",
        self::UNEXPECTED_ITEM_CLASS              => "The request tried to operate on a type of items unknown to the server",
        self::REMOTE_SERVER_HAS_NO_SSL           => "Remote server doesn't have SSL enabled",
        self::INVALID_STORED_REQUEST             => "The stored result is invalid. The device should send the full request again",
        self::ITEM_NOT_FOUND                     => "Item not found",
        self::TOO_MANY_FOLDERS                   => "The mailbox contains too many folders",
        self::NO_FOLDERS_FOUND                   => "The mailbox contains no folders",
        self::ITEMS_LOST_AFTER_MOVE              => "Items lost after move",
        self::FAILURE_IN_MOVE_OPERATION          => "The mailbox server returned an unknown error while moving items",
        self::MOVE_COMMAND_DISALLOWED             => "An ItemOperations command request to move a conversation is missing the MoveAlways element",
        self::MOVE_COMMAND_INVALID_DESTINATION   => "The destination folder for the move is invalid",
        self::AVAILABILITY_TO_MANY_RECIPIENTS    => "The command has reached the maximum number of recipients that it can request availability for",
        self::AVAILABILITY_DL_LIMIT_REACHED      => "The size of the distribution list is larger than the availability service is configured to process",
        self::AVAILABILITY_TRANSIENT_FAILURE     => "Availability service request failed with a transient error",
        self::AVAILABILITY_FAILURE               => "Availability service request failed with an error",
        self::BODY_PART_PREFERENCE_TYPE_NOT_SUPPORTED => "The BodyPartPreference node has an unsupported Type element",
        self::DEVICE_INFORMATION_REQUIRED        => "The required DeviceInformation element is missing in the Provision request",
        self::INVALID_ACCOUNT_ID                 => "Invalid AccountId value",
        self::ACCOUNT_SEND_DISABLED              => "The AccountId value specified in the request does not support sending e-mail",
        self::IRM_FEATURE_DISABLED               => "The Information Rights Management feature is disabled",
        self::IRM_TRANSIENT_ERROR                => "Information Rights Management encountered a transient error",
        self::IRM_PERMANENT_ERROR                => "Information Rights Management encountered a permanent error",
        self::IRM_INVALID_TEMPLATE_ID            => "The Template ID value is not valid",
        self::IRM_OPERATION_NOT_PERMITTED        => "Information Rights Management does not support the specified operation",
        self::NO_PICTURE                         => "The user does not have a contact photo",
        self::PICTURE_TO_LARGE                   => "The contact photo exceeds the size limit set by the MaxSize element",
        self::PICTURE_LIMIT_REACHED              => "The number of contact photos returned exceeds the size limit set by the MaxPictures element",
        self::BODY_PART_CONVERSATION_TOO_LARGE   => "The conversation is too large to compute the body parts",
        self::MAXIMUM_DEVICES_REACHED            => "The user's account has too many device partnerships",
    );

    /**
     * Error messages assigned to class-specific error codes
     *
     * @var array
     */
    protected $_errorMessages = array();


    /**
     * Constructor
     */
    function __construct()
    {
        $args = func_get_args();

        if (isset($args[1])) {
            $code    = $args[1];
            $message = $args[0];
        } elseif (is_int($args[0])) {
            $code    = $args[0];
            $message = null;
        } else {
            $message = $args[0];
        }

        if (!$code) {
            $code = self::SERVER_ERROR;
        }

        if (!$message) {
            if (isset($this->_errorMessages[$code])) {
                $message = $this->_errorMessages[$code];
            } elseif (isset($this->_commonMessages[$code])) {
                $message = $this->_commonMessages[$code];
            }
        }

        parent::__construct($message, $code);
    }
}
