<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for a full user account
 * 
 * this datatype contains all information about an account
 * the usage of this datatype should be restricted to administrative tasks only
 * 
 * @package     Tinebase
 * @subpackage  Account
 */
class Tinebase_Account_Model_FullAccount extends Tinebase_Account_Model_Account
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        //'accountId'             => 'Digits',
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
        'accountId'             => array('Digits', 'allowEmpty' => true),
        'accountLoginName'      => array('presence' => 'required'),
        'accountLastLogin'      => array('allowEmpty' => true),
        'accountLastLoginfrom'  => array('allowEmpty' => true),
        'accountLastPasswordChange' => array('allowEmpty' => true),
        'accountStatus'         => array('presence' => 'required'),
        'accountExpires'        => array('allowEmpty' => true),
        'accountPrimaryGroup'   => array('presence' => 'required'),
        'accountDisplayName'    => array('presence' => 'required'),
        'accountLastName'       => array('presence' => 'required'),
        'accountFirstName'      => array('allowEmpty' => true),
        'accountFullName'       => array('presence' => 'required'),
        //'accountPassword'       => array('allowEmpty' => true),
        'accountEmailAddress'   => array('allowEmpty' => true)
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
    
    /**
     * return the public informations of this account only
     *
     * @return Tinebase_Account_Model_Account
     */
    public function getPublicAccount()
    {
        $result = new Tinebase_Account_Model_Account($this->toArray(), true);
        
        return $result;
    }
    
}