<?php
/**
 * class to hold Event data
 * 
 * @package     Events
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Event data
 * 
 * @package     Events
 * @subpackage  Model
 */
class Events_Model_Event extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        // do freebusy check when creating/updating related events
        array(
            'relatedApp' => 'Calendar',
            'relatedModel' => 'Event',
            'config' => array(),
            'default' => array('type' => 'MAIN', 'related_degree' => 'parent'),
            // needed to activate $_checkBusyConflicts when creating/updating related events
            'createUpdateCheck' => true,
        ),
    );

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Event',
        'recordsName'       => 'Events', // ngettext('Event', 'Events', n)
        'containerProperty' => 'container_id',
        'titleProperty'     => 'title',
        'containerName'     => 'Event list',
        'containersName'    => 'Event lists', // ngettext('Event list', 'Event lists', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,

        'createModule'    => TRUE,

        'appName'         => 'Events',
        'modelName'       => 'Event',
        
        'fields'          => array(
            'title' => array(
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Title', // _('Title')
                'queryFilter' => TRUE
            ),
            'action' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Action', // _('Action')
                'type'       => 'keyfield',
                'name'       => 'actionType'
            ),
            'project_type' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Project type', // _('Project type')
                    'type'       => 'keyfield',
                    'name'       => 'projectType'
            ),
            'target_group' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Target group', // _('Target group'),
                    'type'       => 'keyfield',
                    'name'       => 'targetGroups'
            ),
            'department' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Department', // _('Department')
                    'type'       => 'record',
                    'config' => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'List',
                    'idProperty'  => 'id',
                    ),
            ),
            'contact' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Contact', // _('Contact')
                    'type'       => 'record',
                    'config' => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                    ),
            ),
            'location' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Location', // _('Location')
            ),
            'subregion' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Subregion', // _('Subregion')
            ),
            'planned' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Planned', // _('Planned')
                    'type'       => 'keyfield',
                    'name'       => 'plannedStatus'
            ),
            'event_dtstart' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Event start', // _('Event start')
                'hidden'     => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'type'       => 'datetime'
            ),
            'event_dtend' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Event end', // _('Event end')
                    'hidden'     => TRUE,
                    'inputFilters' => array('Zend_Filter_Empty' => NULL),
                    'type'       => 'datetime'
            ),
            'organizer' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Organizer', // _('Organizer')
            ),
            'guests' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Guests', // _('Guests')
            ),
            'optional_date' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                    'label'      => 'Optional date', // _('Optional date')
                    'hidden'     => TRUE,
                    'inputFilters' => array('Zend_Filter_Empty' => NULL),
                    'type'       => 'date'
            ),
            'description' => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE)
            )
        )
     );
}
