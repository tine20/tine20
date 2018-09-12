<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold Employee data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_WorkingTime extends Tinebase_Record_Abstract
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
        'version'           => 3,
        'recordName'        => 'Working time',
        'recordsName'       => 'Working times', // ngettext('Working time', 'Working times', n)
        'hasRelations'    => FALSE,
        'hasCustomFields' => FALSE,
        'hasNotes'        => FALSE,
        'hasTags'         => FALSE,
        'modlogActive'    => TRUE,
        'isDependent'     => TRUE,
        'createModule'    => FALSE,
        'titleProperty'     => 'title',
        'appName'           => 'HumanResources',
        'modelName'         => 'WorkingTime',
        
        'table'             => array(
            'name'    => 'humanresources_workingtime',
        ),


        'fields' => [
            'title' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'json' => [
                'type' => 'text', // json
                'nullable' => true,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'working_hours' => [
                'type' => 'integer',
                'nullable' => true,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters' => ['Zend_Filter_Empty' => null],
                'default' => 1,
            ],
        ]
    );
}
