<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
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
     * 
     * @todo 	add more filters
     */
    protected $_filters = array(
        'registrationId'         => 'Digits',
        'registrationLoginName'  => 'StringTrim',
        'registrationHash'       => 'StringTrim',
        'registrationEmail'      => 'StringTrim',
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var 	array
     */
    protected $_validators = array(
        'registrationId'        => array('allowEmpty' => true),
        'registrationLoginName' => array('presence' => 'required'),
        'registrationHash'      => array('presence' => 'required'),
        'registrationEmail'     => array('presence' => 'required'),
    	'registrationDate'		=> array('allowEmpty' => true),
    	'registrationExpires'	=> array('allowEmpty' => true),
    	'registrationStatus'	=> array('allowEmpty' => true),
    	'registrationEmailSent' => array('allowEmpty' => true),
    );
    
   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var 	string
     */    
    protected $_identifier = 'registrationId';

    
    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'registrationDate',
        'registrationExpires',
    );    
}
