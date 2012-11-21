<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * defines the datatype for one note
 * 
 * @package     Tinebase
 * @subpackage  Notes
 */
class Tinebase_Model_Note extends Tinebase_Record_Abstract
{
    /**
     * system note type: changed
     * 
     * @staticvar string
     */
    const SYSTEM_NOTE_NAME_CREATED = 'created';
    
    /**
     * system note type: changed
     * 
     * @staticvar string
     */
    const SYSTEM_NOTE_NAME_CHANGED = 'changed';
    
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
        'note'              => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                     => array('Alnum', 'allowEmpty' => true),
        'note_type_id'           => array('Alnum', 'allowEmpty' => false),
    
        'note'                   => array('presence' => 'required', 'allowEmpty' => false),
        
        'record_id'              => array('allowEmpty' => true),
        'record_model'           => array('allowEmpty' => true),
        'record_backend'         => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 'Sql'),
    
        'created_by'             => array('allowEmpty' => true),
        'creation_time'          => array('allowEmpty' => true),
        'last_modified_by'       => array('allowEmpty' => true),
        'last_modified_time'     => array('allowEmpty' => true),
        'is_deleted'             => array('allowEmpty' => true),
        'deleted_time'           => array('allowEmpty' => true),
        'deleted_by'             => array('allowEmpty' => true),
        'seq'                    => array('allowEmpty' => true),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
    );
    
    /**
     * returns array with record related properties
     * resolves the creator display name and calls Tinebase_Record_Abstract::toArray() 
     *
     * @param boolean $_recursive
     * @param boolean $_resolveCreator
     * @return array
     */    
    public function toArray($_recursive = TRUE, $_resolveCreator = TRUE)
    {
        $result = parent::toArray($_recursive);
        
        // get creator
        if ($this->created_by && $_resolveCreator) {
            //resolve creator; return default NonExistentUser-Object if creator cannot be resolved =>
            //@todo perhaps we should add a "getNonExistentUserIfNotExists" parameter to Tinebase_User::getUserById 
            try {
                $creator = Tinebase_User::getInstance()->getUserById($this->created_by);
            }
            catch (Tinebase_Exception_NotFound $e) {
                $creator = Tinebase_User::getInstance()->getNonExistentUser();
            }
             
            $result['created_by'] = $creator->accountDisplayName;
        }
        
        return $result;
    }
}
