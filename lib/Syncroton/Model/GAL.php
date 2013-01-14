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
 * class to handle ActiveSync GAL result
 *
 * @package     Syncroton
 * @subpackage  Model
 *
 * @property    string    Alias
 * @property    string    Company
 * @property    string    DisplayName
 * @property    string    EmailAddress
 * @property    string    FirstName
 * @property    string    LastName
 * @property    string    MobilePhone
 * @property    string    Office
 * @property    string    Phone
 * @property    string    Picture
 * @property    string    Title
 */
class Syncroton_Model_GAL extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'ApplicationData';

    protected $_properties = array(
        'GAL' => array(
            'alias'         => array('type' => 'string', 'supportedSince' => '2.5'),
            'company'       => array('type' => 'string', 'supportedSince' => '2.5'),
            'displayName'   => array('type' => 'string', 'supportedSince' => '2.5'),
            'emailAddress'  => array('type' => 'string', 'supportedSince' => '2.5'),
            'firstName'     => array('type' => 'string', 'supportedSince' => '2.5'),
            'lastName'      => array('type' => 'string', 'supportedSince' => '2.5'),
            'mobilePhone'   => array('type' => 'string', 'supportedSince' => '2.5'),
            'office'        => array('type' => 'string', 'supportedSince' => '2.5'),
            'phone'         => array('type' => 'string', 'supportedSince' => '2.5'),
            'picture'       => array('type' => 'container', 'supportedSince' => '14.0'),
            'title'         => array('type' => 'string', 'supportedSince' => '2.5'),
        )
    );
}
