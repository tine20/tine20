<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Task-Record Class
 * @package Tasks
 */
class Tasks_Model_Task extends Egwbase_Record_Abstract
{
    protected $_identifier = 'identifier';
    
    protected $_validators = array(
        // egw record fields
        'container'            => array('presence' => 'required', 'allowEmpty' => false, 'Int' ),
        'created_by'           => array('allowEmpty' => true,  'Int' ),
        'creation_time'        => array('allowEmpty' => true         ),
        'last_modified_by'     => array('allowEmpty' => true         ),
        'last_modified_time'   => array('allowEmpty' => true         ),
        'is_deleted'           => array('allowEmpty' => true         ),
        'deleted_time'         => array('allowEmpty' => true         ),
        'deleted_by'           => array('allowEmpty' => true         ),
        // task only fields
        'identifier'           => array('allowEmpty' => true, 'Int'  ),
        'percent'              => array('allowEmpty' => true         ),
        'completed'            => array('allowEmpty' => true         ),
        'due'                  => array('allowEmpty' => true         ),
        // ical common fields
        'class'                => array('allowEmpty' => true, 'Int'  ),
        'description'          => array('allowEmpty' => true         ),
        'geo'                  => array('allowEmpty' => true         ),
        'location'             => array('allowEmpty' => true         ),
        'organizer'            => array('allowEmpty' => true, 'Int' ),
        'priority'             => array('allowEmpty' => true         ),
        'status'               => array('allowEmpty' => true         ),
        'summaray'             => array('presence' => 'required'     ),
        'url'                  => array('allowEmpty' => true         ),
        // ical common fields with multiple appearance
        'attach'                => array('allowEmpty' => true        ),
        'attendee'              => array('allowEmpty' => true        ),
        'tags'                  => array('allowEmpty' => true        ), //originally categories
        'comment'               => array('allowEmpty' => true        ),
        'contact'               => array('allowEmpty' => true        ),
        'related'               => array('allowEmpty' => true        ),
        'resources'             => array('allowEmpty' => true        ),
        'rstatus'               => array('allowEmpty' => true        ),
        // scheduleable interface fields
        'dtstart'               => array('allowEmpty' => true        ),
        'duration'              => array('allowEmpty' => true        ),
        'recurid'               => array('allowEmpty' => true        ),
        // scheduleable interface fields with multiple appearance
        'exdate'                => array('allowEmpty' => true        ),
        'exrule'                => array('allowEmpty' => true        ),
        'rdate'                 => array('allowEmpty' => true        ),
        'rrule'                 => array('allowEmpty' => true        ),
    );
    
    protected $_datetimeFields = array(
        'creation_time', 
        'last_modified_time', 
        'deleted_time', 
        'completed', 
        'dtstart', 
        'due', 
        'exdate', 
        'rdate'
    );
}