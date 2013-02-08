<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold FreeDay data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_FreeDay extends Tinebase_Record_Abstract
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
        'recordName'        => 'Free Day', // _('Free Day')
        'recordsName'       => 'Free Days', // _('Free Days')
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => FALSE,
        'isDependent'       => TRUE,
        'createModule'      => FALSE,
        'containerProperty' => NULL,
    
        'titleProperty'     => NULL,
        'appName'           => 'HumanResources',
        'modelName'         => 'FreeDay',
    
        'fields'            => array(
            'freetime_id'       => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'FreeTime',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'date'   => array(
                'label' => NULL,
                'type'  => 'date'
            ),
            'duration' => array(
                'label' => NULL,
                'type' => 'float'
            ),
        ),
    );
}