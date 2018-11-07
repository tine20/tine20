<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold ExtraFreeTime data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_ExtraFreeTime extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'version'           => 4,
        'recordName'        => 'Extra free time', // ngettext('Extra free time', 'Extra free times', n)
        'recordsName'       => 'Extra free times',
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'containerProperty' => NULL,
        'createModule'      => FALSE,
        'isDependent'       => TRUE,
        'appName'           => 'HumanResources',
        'modelName'         => 'ExtraFreeTime',

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'account_id' => [
                    'targetEntity' => 'HumanResources_Model_Account',
                    'fieldName' => 'account_id',
                    'joinColumns' => [[
                        'name' => 'account_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        'table'             => array(
            'name'    => 'humanresources_extrafreetime',
            'indexes' => array(
                'account_id' => array(
                    'columns' => array('account_id'),
                ),
            ),
        ),
        
        'fields'            => array(
            'account_id'       => array(
                'label'      => 'Account',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Account',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'type'            => array(
                'label' => 'Type', // _('Type')
                'type'  => 'keyfield',
                'name'  => HumanResources_Config::EXTRA_FREETIME_TYPE,
                'queryFilter' => TRUE,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'description'          => array(
                'label' => 'Description', // _('Description')
                'type'  => 'text',
                'queryFilter' => TRUE,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'days' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label' => 'Days', // _('Days')
                'type'       => 'integer'
            ),
            'expires' => array(
                'label' => 'Expiration date', //_('Expiration date')
                'type'  => 'date',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
        )
    );
}
