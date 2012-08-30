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
 * Exception for Status element in GetItemEstimate response
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status_GetItemEstimate extends Syncroton_Exception_Status
{
    const INVALID_COLLECTION   = 2;
    const SYNCSTATE_NOT_PRIMED = 3;
    const INVALID_SYNCKEY      = 4;

    /**
     * Error messages assigned to error codes
     *
     * @var array
     */
    protected $_errorMessages = array(
        self::INVALID_COLLECTION   => "A collection was invalid or one of the specified collection IDs was invalid",
        self::SYNCSTATE_NOT_PRIMED => "The synchronization state has not been primed",
        self::INVALID_SYNCKEY      => "The specified synchronization key was invalid",
    );
}
