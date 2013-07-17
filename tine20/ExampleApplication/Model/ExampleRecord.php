<?php
/**
 * class to hold ExampleRecord data
 * 
 * @package     ExampleApplication
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold ExampleRecord data
 * 
 * @package     ExampleApplication
 * @subpackage  Model
 */
class ExampleApplication_Model_ExampleRecord extends Tinebase_Record_Abstract
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'ExampleApplication';
    
    /**
     * array with meta information about the model (like models.js)
     * @var array
     */
    protected static $_meta = array(
        'idProperty'        => 'id',
        'titleProperty'     => 'name',
        'recordName'        => 'example record', // _('example record')
        'recordsName'       => 'example records', // _('example records')
        'containerProperty' => 'container_id',
        'containerName'     => 'example record list', // _('example record list')
        'containersName'    => 'example record lists', // _('example record lists')
        'defaultFilter'     => 'query',
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
    );
    
    /**
     * fields for auto start
     * @var array
     */
    protected static $_fields = array(
        'id'     => array(
            'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
            'label' => NULL
        ),
        'name'   => array(
            'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
            'label' => 'Name',    // _('Name')
            ),
        'status' => array(
            'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
            'label' => 'Status',    // _('Status')
            'type' => 'keyfield',
            'name' => 'exampleStatus'
            )
        );
}
