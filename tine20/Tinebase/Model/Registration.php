<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * defines the datatype for simple registration object
 * 
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_Model_Registration extends Tinebase_Record_Abstract
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var     array
     * 
     * @todo     add more filters
     */
    protected $_filters = array(
        'id'         => 'Digits',
        'login_name' => 'StringTrim',
        'login_hash' => 'StringTrim',
        'email'      => 'StringTrim',
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var     array
     */
    protected $_validators = array(
        'id'            => array('allowEmpty' => true),
        'login_name'    => array('presence' => 'required'),
        'login_hash'    => array('presence' => 'required'),
        'email'         => array('presence' => 'required'),
        'date'            => array('allowEmpty' => true),
        'expires_at'    => array('allowEmpty' => true),
        'status'        => array('allowEmpty' => true),
        'email_sent'    => array('allowEmpty' => true),
    );
    
   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var     string
     */    
    protected $_identifier = 'id';

    
    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'date',
        'expires_at',
    );

 
}
