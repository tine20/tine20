<?php
/**
 * defines the datatype for one application
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Egwbase_Record_Application extends Egwbase_Record_Abstract
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'      => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'app_id'      => array('Digits', 'presence' => 'required'),
        'app_name'    => array('presence' => 'required'),
        'app_enabled' => array('Digits', 'presence' => 'required'),
        'app_order'   => array('Digits', 'presence' => 'required'),
        'app_tables'  => array('allowEmpty' => true),
        'app_version' => array('presence' => 'required')
    );

   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'app_id';
}