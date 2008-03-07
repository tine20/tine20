<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Implementation of an event
 * 
 * @package     Calendar
 */
class Calendar_Event extends Tinebase_Record_Abstract
{
	/**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */ 
    protected $_identifier = 'cal_id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';
    
    protected $_validators = array(
        'cal_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'cal_uid'             => array(),
        'cal_owner'           => array(),
        'cal_category'        => array(),
        'cal_modified'        => array(),
        'cal_priority'        => array(),
        'cal_public'          => array(),
        'cal_title'           => array(),
        'cal_description'     => array(),
        'cal_location'        => array(),
        'cal_reference'       => array(),
        'cal_modifier'        => array(),
        'cal_non_blocking'    => array(),
        'cal_special'         => array(),
        'cal_start'           => array(),
        'cal_end'             => array(),
    	'cal_recur_type'      => array(),
        'cal_recur_enddate'   => array(),
        'cal_recur_interval'  => array(),
        'cal_recur_data'      => array(),
        'cal_recur_exception' => array(),
        'cal_recur_date'      => array(),
        'cal_alarm'           => array(),

        'cal_participants'    => array(),

    );
    
    public function __set($_name, $_value) {
        switch ($_name) {
            case 'cal_participants' :
                return $this->setParticipants();
            default :
                return parent::__set($_name, $_value);
        }
    }
    
    public function setParticipant( $_type, $_id, $_status, $_quantity)
    {
        $this->_properties['cal_participants'][$_type.$_id] = array(
            'cal_user_type' => $_type,
            'cal_user_id' => $_id,
            'cal_status' => $_status,
            'cal_quantity' => $_quantity
        );
    }
}