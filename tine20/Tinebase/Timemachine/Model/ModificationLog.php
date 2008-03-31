<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Timemachine 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Model of an logbook entry
 * 
 * NOTE: record_type is a free-form field, which could be used by the application
 * to distinguish different tables, mask multible keys and so on.
 * 
 * @package Tinebase
 * @subpackage Timemachine
 */
class Tinebase_Timemachine_Model_ModificationLog extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * Defintion of properties. All properties of record _must_ be declared here!
     * This validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array list of zend validator
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,  'Alnum' ),
        'application_id'       => array('allowEmpty' => false          ),
        'record_id'            => array('allowEmpty' => false, 'Alnum' ),
        'record_type'          => array('allowEmpty' => true           ),
        'record_backend'       => array('allowEmpty' => false          ),
        'modification_time'    => array('allowEmpty' => false          ),
        'modification_account' => array('allowEmpty' => false, 'Int'   ),
        'modified_attribute'   => array('allowEmpty' => false          ),
        'old_value'            => array('allowEmpty' => true           ),
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'modification_time'
    );
}
