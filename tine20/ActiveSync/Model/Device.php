<?php
/**
 * class to hold device data
 * 
 * @package     ActiveSyncActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 */

/**
 * class to hold SyncState data
 * 
 * @package     ActiveSyncActiveSync
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
        'owner_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'policy_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'policykey'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'acsversion'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'useragent'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pinglifetime'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pingfolder'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'remotewipe'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    );

}
