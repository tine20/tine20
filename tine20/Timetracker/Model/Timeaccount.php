<?php
/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        update validators (default values, mandatory fields)
 * @todo        add setFromJson with relation handling
 */

/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 */
class Timetracker_Model_Timeaccount extends Tinebase_Record_Abstract
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
     * relation type: contract
     *
     */
    const RELATION_TYPE_CONTRACT = 'CONTRACT';
    
    /**
     * deadline type: none
     * = no deadline for timesheets
     */
    const DEADLINE_NONE = 'none';
    
    /**
     * deadline type: last week
     * = booking timesheets allowed until monday midnight for the last week
     */
    const DEADLINE_LASTWEEK = 'lastweek';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_grants'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'title'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'number'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'budget'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'budget_unit'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'hours'),
        'price'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'price_unit'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'hours'),
        'is_open'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'is_billable'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'billed_in'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'status'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'not yet billed'),    
    // how long can users book timesheets for this timeaccount 
        'deadline'              => array(
            Zend_Filter_Input::ALLOW_EMPTY      => true, 
            Zend_Filter_Input::DEFAULT_VALUE    => self::DEADLINE_NONE,
            'InArray'                           => array(
                self::DEADLINE_NONE, 
                self::DEADLINE_LASTWEEK,
            )
        ),    
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // relations (linked Timetracker_Model_Timeaccount records) and other metadata
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'grants'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
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
        $this->_filters['budget']  = array('Digits', new Zend_Filter_Empty(NULL));
        $this->_filters['price'] = array(new Zend_Filter_PregReplace('/,/', '.'), new Zend_Filter_Empty(NULL));
        $this->_filters['is_open'] = new Zend_Filter_Empty(0);
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }

    /**
     * set from array data
     *
     * @param array $_data
     * @return void
     */
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data);
        
        if (isset($_data['grants']) && !empty($_data['grants'])) {
            $this->grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', $_data['grants']);
        }  else {
            $this->grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants');
        }
    }
}
