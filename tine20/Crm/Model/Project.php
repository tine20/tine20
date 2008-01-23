<?php
/**
 * class to hold project data
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        'lead_name'                     => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'lead_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'lead_name'           => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'lead_leadstate_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lead_leadtype_id'    => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'lead_leadsource_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'lead_container'          => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        #'lead_modifier'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lead_start'          => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        #'lead_modified'      => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        #'lead_created'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lead_description'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lead_end'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'lead_turnover'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lead_probability'    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'lead_end_scheduled'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'contacts'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL)        
        #'lead_lastread'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        #'lead_lastreader'    => array(Zend_Filter_Input::ALLOW_EMPTY => true)  
    );

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'lead_start',
        'lead_end',
        'lead_end_scheduled'
    );
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'lead_id';    
    
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    public function setContactData($_contactData)
    {
        $_key = $this->_properties['lead_id'];
        $_contact = $_contactData[$_key];
        $this->_properties['contacts'] = $_contact;
    }
}