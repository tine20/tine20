<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Model of a resource
 * 
 * @package Calendar
 * @property grants
 */
class Calendar_Model_Resource extends Tinebase_Record_Abstract
{

    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,  'Alnum'),
        'container_id'         => array('allowEmpty' => true,  'Alnum'),
        'created_by'           => array('allowEmpty' => true,         ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
        // resource specific fields
        'name'                 => array('allowEmpty' => true          ),
        'hierarchy'            => array('allowEmpty' => true          ),
        'description'          => array('allowEmpty' => true          ),
        'email'                => array('allowEmpty' => true          ),
        'max_number_of_people' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'type'                 => array('allowEmpty' => false         ),
        // location and site are virtual fields
        'location'             => array('allowEmpty' => true          ),
        'site'                 => array('allowEmpty' => true          ),
        'status'               => array('allowEmpty' => true          ),
        'status_with_grant'       => array('allowEmpty' => true          ),
        'busy_type'            => array('allowEmpty' => true          ),
        'suppress_notification'=> array('allowEmpty' => true          ),
        'tags'                 => array('allowEmpty' => true          ),
        'notes'                => array('allowEmpty' => true          ),
        'grants'               => array('allowEmpty' => true          ),
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'relations'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'customfields'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'color'                => array('allowEmpty' => true),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'creation_time', 
        'last_modified_time', 
        'deleted_time', 
    );

    protected static $_relatableConfig = array(
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'SITE', 'degree' => 'child', 'text' => 'Site', 'max' => '0:0'), // _('Site'),
            array('type' => 'LOCATION', 'degree' => 'child', 'text' => 'Location', 'max' => '0:0'), // _('Location')
        )),
    );
}
