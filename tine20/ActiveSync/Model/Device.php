<?php
/**
 * Tine 2.0
 * 
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * class to hold device data
 * 
 * @package     ActiveSync
 * @property    string  $id the id
 */
class ActiveSync_Model_Device extends Tinebase_Record_Abstract
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
        'policy_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'policykey'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'acsversion'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'useragent'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
        'contactfilter_id'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'emailfilter_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'taskfilter_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

}
