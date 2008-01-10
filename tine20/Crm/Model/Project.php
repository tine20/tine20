<?php
/**
 * class to hold project data
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Event.php 200 2007-11-16 10:50:03Z twadewitz $
 *
 */
class Crm_Model_Project extends Egwbase_Record_Abstract
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
        'pj_id' 				    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'pj_name'                   => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'pj_leadstate_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_leadtype_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'pj_leadsource_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'pj_owner'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
   #     'pj_modifier'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_start'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
   #     'pj_modified'               => array(Zend_Filter_Input::ALLOW_EMPTY => false),
   #     'pj_created'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_description'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_end'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_turnover'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_probability'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pj_end_scheduled'          => array(Zend_Filter_Input::ALLOW_EMPTY => true)//,
   #     'pj_lastread'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
   #     'pj_lastreader'             => array(Zend_Filter_Input::ALLOW_EMPTY => true)  
        
    );
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'pj_id';
}