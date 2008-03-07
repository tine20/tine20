<?php
/**
 * class to hold product data
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold product data
 * 
 * @package     CRM
 */
class Crm_Model_Product extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'lead_id';
    
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
        'lead_id' 				    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'lead_lead_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false),
		'lead_product_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false),
		'lead_product_desc'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
		'lead_product_price'          => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

}