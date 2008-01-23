<?php
/**
 * class to hold ContactLink data
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Crm_Model_ContactLink extends Egwbase_Record_Abstract
{
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
        'link_app1'    => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'link_id1'     => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'link_app2'    => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'link_id2'     => array(Zend_Filter_Input::ALLOW_EMPTY => false),        
        'link_remark'  => array(Zend_Filter_Input::ALLOW_EMPTY => false),        
        'link_lastmod' => array(Zend_Filter_Input::ALLOW_EMPTY => true),        
        'link_owner'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL)
    );
    
   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'link_id';
}