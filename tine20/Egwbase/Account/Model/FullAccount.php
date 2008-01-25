<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one application
 * 
 * @package     Egwbase
 * @subpackage  Account
 */
class Egwbase_Account_Model_FullAccount extends Egwbase_Account_Model_Account
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'accountId'             => 'Digits',
        'accountLoginName'      => 'StringTrim',
        'account_primary_group' => 'Digits',
        'accountDisplayName'    => 'StringTrim',
        'accountLastName'       => 'StringTrim',
        'accountFirstName'      => 'StringTrim',
        'accountFullName'       => 'StringTrim',
        'accountEmailAddress'   => 'StringTrim',
    ); // _/-\_
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'accountId'             => array('Digits', 'presence' => 'required'),
        'accountLoginName'      => array('presence' => 'required'),
        'accountLastLogin'      => array('allowEmpty' => true),
        'accountLastLoginfrom'  => array('allowEmpty' => true),
        'accountLastPasswordChange' => array('Digits', 'allowEmpty' => true),
        'accountStatus'         => array('presence' => 'required'),
        'accountExpires'        => array('allowEmpty' => true),
        'accountPrimaryGroup'   => array('presence' => 'required'),
        'accountDisplayName'    => array('presence' => 'required'),
        'accountLastName'       => array('presence' => 'required'),
        'accountFirstName'      => array('allowEmpty' => true),
        'accountFullName'       => array('presence' => 'required'),
        'accountEmailAddress' => array('allowEmpty' => true)
    );

    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'accountLastLogin',
        'accountLastPasswordChange',
        'accountExpires'
    );
    
    public function getPublicAccount()
    {
        $result = new Egwbase_Account_Model_Account($this->toArray(), true);
        
        return $result;
    }
    
}