<?php
/**
 * defines the datatype for one container
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Application.php 308 2007-11-20 16:06:07Z lkneschke $
 *
 */

class Egwbase_Record_Container extends Egwbase_Record_Abstract
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
        'container_id'      => array('Digits', 'presence' => 'required'),
        'container_name'    => array('presence' => 'required'),
        'container_type'    => array('presence' => 'required'),
        'container_backend' => array('presence' => 'required'),
        'application_id'    => array('Digits', 'presence' => 'required'),
        'account_grants'    => array('Digits', 'presence' => 'required')
    );

   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'container_id';
}