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
 * class to handle ActiveSync task
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */
class Syncroton_Model_Task extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'body'                   => array('type' => 'container', 'class' => 'Syncroton_Model_EmailBody')
        ),
        'Tasks' => array(
            'categories'              => array('type' => 'container', 'childElement' => 'category'),
            'complete'                => array('type' => 'number'),
            'dateCompleted'           => array('type' => 'datetime'),
            'dueDate'                 => array('type' => 'datetime'),
            'importance'              => array('type' => 'number'),
            'recurrence'              => array('type' => 'container'),
            'reminderSet'             => array('type' => 'number'),
            'reminderTime'            => array('type' => 'datetime'),
            'sensitivity'             => array('type' => 'number'),
            'startDate'               => array('type' => 'datetime'),
            'subject'                 => array('type' => 'string'),
            'utcDueDate'              => array('type' => 'datetime'),
            'utcStartDate'            => array('type' => 'datetime'),
        )
    );
}