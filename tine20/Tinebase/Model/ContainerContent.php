<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * defines the datatype for one container content record
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @property    string record_id
 * @property    string container_id
 * @property    string content_seq
 * @property    string action
 * @property    Tinebase_DateTime  time
 */
class Tinebase_Model_ContainerContent extends Tinebase_Record_Abstract
{
    /**
     * create action
     */
    const ACTION_CREATE = 'create';
    
    /**
     * update action
     */
    const ACTION_UPDATE = 'update';
    
    /**
     * delete action
     */
    const ACTION_DELETE = 'delete';
    
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
        'id'              => array('allowEmpty' => true),
        'action'          => array(array('InArray', array(self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE))),
        'time'            => array('allowEmpty' => true),
        'record_id'       => array('allowEmpty' => true),
        'container_id'    => array('allowEmpty' => true),
        'content_seq'     => array('allowEmpty' => true),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'time',
    );
}
