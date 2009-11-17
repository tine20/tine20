<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Model of an freebusy information
 *
 * @package Calendar
 */
class Calendar_Model_FreeBusy extends Tinebase_Record_Abstract
{
    /**
     * supported freebusy types
     */
    const FREEBUSY_FREE             = 'FREE';
    const FREEBUSY_BUSY             = 'BUSY';
    const FREEBUSY_BUSY_UNAVAILABLE = 'BUSY_UNAVAILABLE';
    const FREEBUSY_BUSY_TENTATIVE   = 'BUSY_TENTATIVE';
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * NOTE: _Must_ be set by the derived classes!
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,         ), // not used
        'user_type'            => array('allowEmpty' => true,  'InArray' => array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUP, Calendar_Model_Attender::USERTYPE_GROUPMEMBER, Calendar_Model_Attender::USERTYPE_RESOURCE)),
        'user_id'              => array('allowEmpty' => true,         ),
        'dtstart'              => array('allowEmpty' => true,         ),
        'dtend'                => array('allowEmpty' => true,         ),
        'event'                => array('allowEmpty' => true,         ),
        'type'                 => array('allowEmpty' => true,  'InArray' => array(self::FREEBUSY_FREE, self::FREEBUSY_BUSY, self::FREEBUSY_BUSY_UNAVAILABLE, self::FREEBUSY_BUSY_TENTATIVE)),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'dtstart', 
        'dtend', 
    );
} 