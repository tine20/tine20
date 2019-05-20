<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * defines the datatype for one note
 * 
 * @package     Tinebase
 * @subpackage  Notes
 *
 * @property    string      $id
 * @property    string      $note_type_id
 * @property    string      $note
 * @property    string      $record_id
 * @property    string      $record_model
 * @property    string      $record_backend
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
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'recordName'        => 'Note',
        'recordsName'       => 'Notes', // ngettext('Note', 'Notes', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'hasXProps'         => false,
        // this will add a notes property which we shouldn't have...
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,

        'appName'           => 'Tinebase',
        'modelName'         => 'Note',
        'idProperty'        => 'id',

        'filterModel'       => [],

        'fields'            => [
            'note_type_id'                  => [
                'validators'                    => [
                    'presence' => 'required',
                    Zend_Filter_Input::ALLOW_EMPTY => false
                ],
            ],
            'note'                          => [
                'type'                          => 'string',
                'validators'                    => [
                    'presence' => 'required',
                    Zend_Filter_Input::ALLOW_EMPTY => false
                ],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'record_id'                     => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'record_model'                  => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'record_backend'                => [
                'type'                          => 'string',
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Sql'
                ],
            ],
        ],
    ];
    
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
