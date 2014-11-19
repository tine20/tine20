<?php
/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 */
class Timetracker_Model_Timesheet extends Tinebase_Record_Abstract implements Sales_Model_Billable_Interface
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
    protected $_application = 'Timetracker';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // record fields
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'timeaccount_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'start_date'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'start_time'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'duration'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'is_billable'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'is_billable_combined'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'billed_in'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'invoice_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_cleared'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'is_cleared_combined'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // custom fields array
        'customfields'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),    
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // other related data
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
    );
    
    /**
     * name of fields containing time information
     *
     * @var array list of time fields
     */
    protected $_timeFields = array(
        'start_time'
    );
    
    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Sales_Model_Invoice' => array('invoice_id'),
    );
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // strip time information from datetime string
        $this->_filters['start_date'] = new Zend_Filter_PregReplace('/(\d{4}-\d{2}-\d{2}).*/', '$1');
        // set start_time to NULL if not set
        $this->_filters['start_time'] = new Zend_Filter_Empty(NULL);
        
        $this->_filters['invoice_id'] = new Zend_Filter_Empty(NULL);
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * returns the interval of this billable
     *
     * @return array
     */
    public function getInterval()
    {
        $startDate = clone new Tinebase_DateTime($this->start_date);
        $startDate->setTimezone(Tinebase_Core::getUserTimezone());
        $startDate->setDate($startDate->format('Y'), $startDate->format('n'), 1);
        $startDate->setTime(0,0,0);
        
        $endDate = clone $startDate;
        $endDate->addMonth(1)->subSecond(1);
        
        return array($startDate, $endDate);
    }
    
    /**
     * returns the quantity of this billable
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->duration / 60;
    }
    
    /**
     * returns the unit of this billable
     *
     * @return string
     */
    public function getUnit()
    {
        return 'hour'; // _('hour')
    }
}
