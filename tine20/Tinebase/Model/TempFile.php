<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_Model_TempFile
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @property    string  name
 * @property    string  path
 */
class Tinebase_Model_TempFile extends Tinebase_Record_Abstract 
{
    /**
     * key in $_validators/$_properties array for the field which 
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
     * Defintion of properties.
     * This validators get used when validating user generated content with Zend_Input_Filter
     * 
     * @var array list of zend validator
     */
    protected $_validators = array(
        'id'         => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'session_id' => array('allowEmpty' => false, 'Alnum' ),
        'time'       => array('allowEmpty' => false),
        'path'       => array('allowEmpty' => false),
        'name'       => array('allowEmpty' => false),
        'type'       => array('allowEmpty' => false),
        'error'      => array('presence' => 'required', 'allowEmpty' => TRUE, 'Int'),
        'size'       => array('allowEmpty' => true)
    );
    
    /**
     * name of fields containing datetime or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'time'
    );
}
