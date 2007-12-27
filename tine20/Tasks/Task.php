<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
 *
 */

/**
 * Task-Record Class
 * @package Tasks
 */
class Tasks_Task extends Egwbase_Record_Abstract
{
    protected $_identifier = 'identifier';
    
    protected $_validators = array(
        // egw record fields
        'container'            => array(),
        'created_by'           => array(),
        'creation_time'        => array(),
        'last_modified_by'     => array(),
        'last_modified_time'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'is_deleted'           => array(),
        'deleted_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'deleted_by'           => array(),
        // task only fields
        'identifier'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'percent'              => array(),
        'completed'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        // ical common fields
        'class'                => array(),
        'description'          => array(),
        'geo'                  => array(),
        'location'             => array(),
        'organizer'            => array(),
        'priority'             => array(),
        'status'               => array(),
        'summaray'             => array(),
        'url'                  => array(),
        // ical common fields with multiple appearance
        'attach'                => array(),
        'attendee'              => array(),
        'tags'                  => array(), //originally categories
        'comment'               => array(),
        'contact'               => array(),
        'related'               => array(),
        'resources'             => array(),
        'rstatus'               => array(),
        // scheduleable interface fields
        'dtstart'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'duration'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'recurid'               => array(),
        // scheduleable interface fields with multiple appearance
        'exdate'                => array(),
        'exrule'                => array(),
        'rdate'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'rrule'                 => array(),
    );
    
    protected $_datetimeFields = array(
        'creation_time', 'last_modified_time', 'deleted_time', 'completed', 
        'dtstart', 'duration', 'exdate', 'rdate'
        
    );
}