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
 * exception for Status element in Autodiscover response
 *
 * @package     Syncroton
 * @subpackage  Exception
 */
class Syncroton_Exception_Status_Autodiscover extends Syncroton_Exception_Status
{
    const PROTOCOL_ERROR = 2;

    /**
     * Error messages assigned to error codes
     *
     * @var array
     */
    protected $_errorMessages = array(
        self::PROTOCOL_ERROR => "Protocol error",
    );
}
