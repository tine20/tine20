<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync device information
 *
 * @package     Model
 * @property    string  friendlyName
 * @property    string  iMEI
 * @property    string  mobileOperator
 * @property    string  model
 * @property    string  oS
 * @property    string  oSLanguage
 * @property    string  phoneNumber
 */

class Syncroton_Model_DeviceInformation extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Set';
    
    protected $_properties = array(
        'Settings' => array(
            'enableOutboundSMS' => array('type' => 'number'),
            'friendlyName'      => array('type' => 'string'),
            'iMEI'              => array('type' => 'string'),
            'mobileOperator'    => array('type' => 'string'),
            'model'             => array('type' => 'string'),
            'oS'                => array('type' => 'string'),
            'oSLanguage'        => array('type' => 'string'),
            'phoneNumber'       => array('type' => 'string')
        ),
    );
}