<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012-2012 Kolab Systems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * class to handle ActiveSync GAL Picture element
 *
 * @package     Syncroton
 * @subpackage  Model
 *
 * @property    string    Status
 * @property    string    Data
 */
class Syncroton_Model_GALPicture extends Syncroton_Model_AEntry
{
    const STATUS_SUCCESS   = 1;
    const STATUS_NOPHOTO   = 173;
    const STATUS_TOOLARGE  = 174;
    const STATUS_OVERLIMIT = 175;

    protected $_xmlBaseElement = 'ApplicationData';

    protected $_properties = array(
        'AirSync' => array(
            'status'       => array('type' => 'number'),
        ),
        'GAL' => array(
            'data'         => array('type' => 'byteArray'),
        ),
    );
}
