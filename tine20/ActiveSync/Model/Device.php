<?php
/**
 * Tine 2.0
 * 
 * @package     ActiveSync
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold device data
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
class ActiveSync_Model_Device extends Tinebase_Record_Abstract implements Syncroton_Model_IDevice
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
        'deviceid'              => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'devicetype'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'owner_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'policy_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'policykey'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'acsversion'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'useragent'             => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'model'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'imei'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'friendlyname'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'os'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'oslanguage'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'phonenumber'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pinglifetime'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pingfolder'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'remotewipe'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'calendarfilter_id'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contactsfilter_id'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'emailfilter_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tasksfilter_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * Returns major firmware version of this device
     * 
     * @return int/string
     */
    public function getMajorVersion()
    {
        switch ($this->devicetype) {
            case Syncroton_Model_Device::TYPE_IPHONE:
                if (preg_match('/(.+)\/(\d+)\.(\d+)/', $this->useragent, $matches)) {
                    list(, $name, $majorVersion, $minorVersion) = $matches;
                    return $majorVersion;
                }
                break;
            default:
                break;
        }
        
        return 0;
    }
}
