<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one access log entry
 */
class Egwbase_Model_AccessLog extends Egwbase_Record_Abstract
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
        'sessionid'  => array('presence' => 'required'),
        'loginid'    => array('presence' => 'required'),
        'ip'         => array('presence' => 'required'),
        'li'         => array('presence' => 'required'),
        'lo'         => array('presence' => 'required'),
        'log_id'     => array('presence' => 'required'),
        'result'     => array('presence' => 'required'),
        'account_id' => array('presence' => 'required')
    );
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'log_id';
}