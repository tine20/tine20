<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012-2012 Kolab Systems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * Class to handle ActiveSync SmartReply element
 *
 * @package    Syncroton
 * @subpackage Model
 */
class Syncroton_Model_SmartReply extends Syncroton_Model_AEntry
{
    protected $_properties = array(
        'ComposeMail' => array(
            'accountId'       => array('type' => 'string'),
            'clientId'        => array('type' => 'string'),
            'mime'            => array('type' => 'byteArray'),
            'replaceMime'     => array('type' => 'string'),
            'saveInSentItems' => array('type' => 'string'),
            'source'          => array('type' => 'container'), // or string
            'status'          => array('type' => 'number'),
        ),
        'RightsManagement' => array(
            'templateID'      => array('type' => 'string'),
        )
    );
}
