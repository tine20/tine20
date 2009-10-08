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
 * class to hold FolderState data
 * 
 * @package     ActiveSync
 */
class ActiveSync_Model_FolderState extends Tinebase_Record_Abstract
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
        'id'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'device_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'class'             => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'folderid'          => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'creation_time'     => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'lastfiltertype'    => array(Zend_Filter_Input::ALLOW_EMPTY => true, /*'presence'=>'required',*/ 'Digits', Zend_Filter_Input::DEFAULT_VALUE => 0),
    );
    
    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time'
    );    
    
}
