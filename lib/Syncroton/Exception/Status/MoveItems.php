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
 * exception for Status element in MoveItems response
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status_MoveItems extends Syncroton_Exception_Status
{
    const INVALID_SOURCE          = 1;
    const INVALID_DESTINATION     = 2;
    const SAME_FOLDER             = 4;
    const ITEM_EXISTS_OR_LOCKED   = 5;
    const FOLDER_LOCKED           = 7;

    /**
     * Error messages assigned to error codes
     *
     * @var array
     */
    protected $_errorMessages = array(
        self::INVALID_SOURCE          => "Invalid source collection ID or item ID",
        self::INVALID_DESTINATION     => "Invalid destination collection ID",
        self::SAME_FOLDER             => "Source and destination collection IDs are the same",
        self::ITEM_EXISTS_OR_LOCKED   => "The item cannot be moved to more than one item at a time, or the source or destination item was locked",
        self::FOLDER_LOCKED           => "Source or destination item was locked",
    );
}
