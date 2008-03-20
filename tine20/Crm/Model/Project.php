<?php
/**
 * class to hold project data
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold project data
 * 
 * @package     CRM
 */
class Crm_Model_Project extends Tinebase_Record_Abstract
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
    protected $_application = 'Crm';
    
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
        'id'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'lead_name'           => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'leadstate_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'leadtype_id'    => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'leadsource_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'container'          => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        #'leadmodifier'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'start'          => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        #'leadmodified'      => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        #'leadcreated'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'end'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'turnover'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'probability'    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'end_scheduled'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'contacts'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL)        
        #'leadlastread'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        #'leadlastreader'    => array(Zend_Filter_Input::ALLOW_EMPTY => true)  
    );

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'start',
        'end',
        'end_scheduled'
    );
        
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    public function setContactData($_contactData)
    {
        $_key = $this->_properties['id'];
        $_contact = $_contactData[$_key];
        $this->_properties['contacts'] = $_contact;
    }
}