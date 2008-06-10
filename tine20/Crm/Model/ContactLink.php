<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        replace by Relation Model
 */

/**
 * class to hold ContactLink data
 * 
 * @package     Crm
 */
class Crm_Model_ContactLink extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'link_id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Crm';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'                     => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'link_id' 	   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'link_app1'    => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'link_id1'     => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'link_app2'    => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'link_id2'     => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),        
        'link_remark'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),        
        'link_lastmod' => array(Zend_Filter_Input::ALLOW_EMPTY => true),        
        'link_owner'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL)
    );
}