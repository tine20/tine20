<?php
/**
 * class to hold phone owner data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     
 *
 * @todo delete that? is that needed?
 */

/**
 * class to hold phone owner data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_SnomPhoneOwner extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'phone_id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Voipmanager';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'phone_id'    => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'account_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required')
    );
    
}