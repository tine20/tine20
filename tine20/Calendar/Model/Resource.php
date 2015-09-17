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
        'container_id'         => array('allowEmpty' => true,  'Int'  ),
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
        'description'          => array('allowEmpty' => true          ),
        'email'                => array('allowEmpty' => true          ),
        'is_location'          => array('allowEmpty' => true          ),
        'status'               => array('allowEmpty' => true          ),
        'suppress_notification'=> array('allowEmpty' => true          ),
        'tags'                 => array('allowEmpty' => true          ),
        'notes'                => array('allowEmpty' => true          ),
        'grants'               => array('allowEmpty' => true          ),
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
}
