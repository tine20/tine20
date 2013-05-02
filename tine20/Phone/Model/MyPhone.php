<?php
/**
 * class to hold myPhone data
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold myPhone data
 * 
 * @package     Phone
 */
class Phone_Model_MyPhone extends Tinebase_Record_Abstract
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
        'recordName'        => 'Phone', // _('Phone')
        'recordsName'       => 'Phones', // _('Phones')
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => FALSE,
        'containerProperty' => NULL,
        'createModule'      => FALSE,
        'isDependent'       => FALSE,
        'idProperty'        => 'id',
        'titleProperty'     => 'description',
        'appName'           => 'Phone',
        'modelName'         => 'MyPhone',
        
        'fields'            => array(
            'template_id'               => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Voipmanager',
                    'modelName'   => 'Snom_Template',
                    'idProperty'  => 'id',
                )
             ),
             // TODO: resolve them the modern way, now still done in fe-json
             'settings' => array(
                 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                 'label'      => 'Settings', // _('Settings')
             ),
             // TODO: resolve them the modern way, now still done in fe-json
             'lines' => array(
                 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                 'label'      => 'Lines', // _('Lines')
             ),
            'description'           => array(
                'label'      => 'Description', // _('Description')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            )
        )
    );
}
