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
 * class to handle ActiveSync event
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */
class Syncroton_Model_EmailAttachment extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Attachment';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'contentId'               => array('type' => 'string'),
            'contentLocation'         => array('type' => 'string'),
            'displayName'             => array('type' => 'string'),
            'estimatedDataSize'       => array('type' => 'string'),
            'fileReference'           => array('type' => 'string'),
            'isInline'                => array('type' => 'number'),
            'method'                  => array('type' => 'string'),
        ),
        'Email2' => array(
            'umAttDuration'         => array('type' => 'number', 'supportedSince' => '14.0'),
            'umAttOrder'            => array('type' => 'number', 'supportedSince' => '14.0'),
        ),
    );
}