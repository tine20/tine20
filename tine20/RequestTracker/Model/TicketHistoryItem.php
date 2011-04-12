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
 * Model of a ticket history item
 * 
 * @package RequestTracker
 */
class RequestTracker_Model_TicketHistoryItem extends Tinebase_Record_Abstract
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
    protected $_application = 'RequestTracker';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        // tine record fields
        'id'                => array('allowEmpty' => true,         ),
        'Ticket'            => array('allowEmpty' => true,         ),
        'TimeTaken'         => array('allowEmpty' => true,         ),
        'Type'              => array('allowEmpty' => true,         ),
        'Description'       => array('allowEmpty' => true,         ),
        'Content'           => array('allowEmpty' => true,         ),
        'Creator'           => array('allowEmpty' => true,         ),
        'Created'           => array('allowEmpty' => true,         ),
        'Field'             => array('allowEmpty' => true,         ),
        'OldValue'          => array('allowEmpty' => true,         ),
        'NewValue'          => array('allowEmpty' => true,         ),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'Created',
    );
}
