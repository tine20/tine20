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
        'version'           => 3,
        'recordName'        => 'Free Day', // ngettext('Free Day', 'Free Days', n)
        'recordsName'       => 'Free Days', // gettext('GENDER_Free Day')
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'isDependent'       => TRUE,
        'createModule'      => FALSE,
        'containerProperty' => NULL,

        'titleProperty'     => NULL,
        'appName'           => 'HumanResources',
        'modelName'         => 'FreeDay',
        'requiredRight'     => HumanResources_Acl_Rights::MANAGE_WORKINGTIME,
        self::DELEGATED_ACL_FIELD => 'freetime_id',

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'freetime_id' => [
                    'targetEntity' => 'HumanResources_Model_FreeTime',
                    'fieldName' => 'freetime_id',
                    'joinColumns' => [[
                        'name' => 'freetime_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        'table'             => array(
            'name'    => 'humanresources_freeday',
            'indexes' => array(
                'freetime_id' => array(
                    'columns' => array('freetime_id'),
                ),
            ),
        ),

        'fields'            => array(
            'freetime_id'       => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'doctrineIgnore'        => true, // already defined as association
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
                'type' => 'float',
                'default' => 1
            ),
        ),
    );
}
