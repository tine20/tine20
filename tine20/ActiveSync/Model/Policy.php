<?php
/**
 * Tine 2.0
 * 
 * @package     ActiveSync
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold policy data
 * 
 * @package     ActiveSync
 * @subpackage  Model
 * @property  string  $acsversion         activesync protocoll version
 * @property  string  $calendarfilter_id  the calendar filter id
 * @property  string  $contactsfilter_id  the contacts filter id
 * @property  string  $emailfilter_id     the email filter id
 * @property  string  $id                 the id
 * @property  string  $policy_id          the current policy_id
 * @property  string  $policykey          the current policykey
 * @property  string  $tasksfilter_id     the tasks filter id
 */
class ActiveSync_Model_Policy extends Tinebase_Record_Abstract
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
    protected $_application = 'ActiveSync';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'devicetype'             => 'StringToLower',
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'policy_key'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );    
}
