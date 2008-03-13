<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo 		add more functions
 */

/**
 * defines the datatype for simple registration object
 * 
 * @package     Tinebase
 * @subpackage  Account
 */
class Tinebase_Account_Model_Registration extends Tinebase_Record_Abstract
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var 	array
     * @todo 	fill with other fields
     */
    protected $_filters = array(
        'registrationId'             => 'Digits',
        /*
        'accountDisplayName'    => 'StringTrim',
        'accountLastName'       => 'StringTrim',
        'accountFirstName'      => 'StringTrim',
        'accountFullName'       => 'StringTrim',
		*/
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var 	array
     * @todo 	fill with other fields
     */
    protected $_validators = array(
        'registrationId'             => array('Digits', 'presence' => 'required'),
        /*
        'accountDisplayName'    => array('presence' => 'required'),
        'accountLastName'       => array('presence' => 'required'),
        'accountFirstName'      => array('allowEmpty' => true),
        'accountFullName'       => array('presence' => 'required'),
    	*/
    );
    
   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var 	string
     */    
    protected $_identifier = 'registrationId';
    
}
