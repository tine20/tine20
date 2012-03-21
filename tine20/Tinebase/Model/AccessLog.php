<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for one access log entry
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_AccessLog extends Tinebase_Record_Abstract
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
    protected $_application = 'Tinebase';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'login_name'    => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'            => array('allowEmpty' => true),
        'sessionid'     => array('presence' => 'required'),
        'login_name'    => array('allowEmpty' => true),
        'ip'            => array('presence' => 'required', 'allowEmpty' => true),
        'li'            => array('presence' => 'required', 'allowEmpty' => true),
        'lo'            => array('allowEmpty' => true),
        'result'        => array('allowEmpty' => true),
        'account_id'    => array('allowEmpty' => true),
        'clienttype'    => array('allowEmpty' => true)
    );
    
    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'li',
        'lo',
    );
}
