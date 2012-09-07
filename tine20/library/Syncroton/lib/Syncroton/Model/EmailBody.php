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
 * class to handle AirSyncBase:Body
 *
 * @package    Model
 * @property   int     EstimatedDataSize
 * @property   string  Data
 * @property   string  Part
 * @property   string  Preview
 * @property   bool    Truncated
 * @property   string  Type
 */

class Syncroton_Model_EmailBody extends Syncroton_Model_AEntry
{
    const TYPE_PLAINTEXT = 1;
    const TYPE_HTML      = 2;
    const TYPE_RTF       = 3;
    const TYPE_MIME      = 4;
    
    protected $_xmlBaseElement = 'Body';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'type'              => array('type' => 'string'),
            'estimatedDataSize' => array('type' => 'string'),
            'data'              => array('type' => 'string'),
            'truncated'         => array('type' => 'number'),
            'part'              => array('type' => 'number'),
            'preview'           => array('type' => 'string', 'supportedSince' => '14.0'),
        ),
    );
}