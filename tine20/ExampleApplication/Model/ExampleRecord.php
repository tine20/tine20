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
    protected static $_modelConfiguration = array(
        'recordName'        => 'example record', // _('example record') ngettext('example record', 'example records', n)
        'recordsName'       => 'example records', // _('example records')
        'containerProperty' => 'container_id',
        'titleProperty'     => 'name',
        'containerName'     => 'example record list', // _('example record list')
        'containersName'    => 'example record lists', // _('example record lists')
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,

        'createModule'    => TRUE,

        'appName'         => 'ExampleApplication',
        'modelName'       => 'ExampleRecord',
        
        'fields'          => array(
            'name' => array(
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Name', // _('Name')
                'queryFilter' => TRUE
            ),
            'status' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label' => 'Status', // _('Status')
                'type' => 'keyfield',
                'name' => 'exampleStatus'
            ),
            'reason' => array(
                'reason' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label' => 'Reason', // _('Reason')
                'type' => 'keyfield',
                'name' => 'exampleReason'
            ),
        )
    );
}
