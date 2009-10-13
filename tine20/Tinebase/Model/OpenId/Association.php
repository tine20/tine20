<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one OpenId association
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_OpenId_Association extends Tinebase_Record_Abstract
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
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'        => array('presence' => 'required'),
        'macfunc'   => array('presence' => 'required'),
        'secret'    => array('presence' => 'required'),
        'expires'   => array('presence' => 'required')
    );
    
    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    #protected $_datetimeFields = array(
    #    'expires',
    #);
    
}