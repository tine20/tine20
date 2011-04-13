<?php
/**
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Model of a ticket
 * 
 * @package RequestTracker
 */
class RequestTracker_Model_Ticket extends Tinebase_Record_Abstract
{
    /**
     * supported status for a ticket
     *
     * @var array
     */
    public static $status = array(
        'new', 'open', 'stalled', 'waiting', 'pending', 'resolved', 'rejected', 'deleted'
    );
    
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
    protected $_application = 'RequestTracker';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,         ),
        'Queue'                => array('allowEmpty' => true,         ),
        'Owner'                => array('allowEmpty' => true,         ),
        'Creator'              => array('allowEmpty' => true,         ),
        'Subject'              => array('allowEmpty' => true,         ),
        'Status'               => array('allowEmpty' => true,         ),
        'Priority'             => array('allowEmpty' => true,         ),
        'InitialPriority'      => array('allowEmpty' => true,         ),
        'FinalPriority'        => array('allowEmpty' => true,         ),
        'Requestors'           => array('allowEmpty' => true,         ),
        'Cc'                   => array('allowEmpty' => true,         ),
        'AdminCc'              => array('allowEmpty' => true,         ),
        'Created'              => array('allowEmpty' => true,         ),
        'Starts'               => array('allowEmpty' => true,         ),
        'Started'              => array('allowEmpty' => true,         ),
        'Due'                  => array('allowEmpty' => true,         ),
        'Resolved'             => array('allowEmpty' => true,         ),
        'Told'                 => array('allowEmpty' => true,         ),
        'LastUpdated'          => array('allowEmpty' => true,         ),
        'TimeEstimated'        => array('allowEmpty' => true,         ),
        'TimeWorked'           => array('allowEmpty' => true,         ),

        'History'              => array('allowEmpty' => true,         ),
        
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'Created',
        'Starts',
        'Started',
        'Due',
        'Resolved',
        'Told',
        'LastUpdated'
    );
}
