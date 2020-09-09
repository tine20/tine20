<?php
/**
 * class to hold call data
 * 
 * @package     Phone
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold lead data
 * 
 * @package     Phone
 * @subpackage    Model
 */
class Phone_Model_Call extends Tinebase_Record_Abstract
{
    /**
     * Type of call
     */
    const TYPE_INCOMING = 'in';
    
    /**
     * Type of call
     */
    const TYPE_OUTGOING = 'out';
    
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
        'recordName'        => 'Call', // _('Call')
        'recordsName'       => 'Calls', // _('Calls')
        'hasRelations'      => TRUE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => FALSE,
        'containerProperty' => FALSE,
        'createModule'      => TRUE,
        'isDependent'       => FALSE,
        'idProperty'        => 'id',
        
        'appName'           => 'Phone',
        'modelName'         => 'Call',
        
        'fields'            => array(
            'line_id'               => array(
                'label'      => 'Line', // _('Line')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Voipmanager',
                    'modelName'   => 'Snom_Line',
                    'idProperty'  => 'id',
                )
             ),
            'phone_id'              => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Phone',
                    'modelName'   => 'MyPhone',
                    'idProperty'  => 'id',
                )
            ),
            'callerid'              => array(
                'label'      => 'Caller Id', // _('Caller Id')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            ),
            'start'                 => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Start', // _('Start')
                'type'       => 'datetime',
            ),
            'connected'             => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Connected', // _('Connected')
                'type'       => 'datetime',
            ),
            'disconnected'          => array(
                'label'      => 'Disconnected', // _('Disconnected')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'       => 'datetime',
            ),
            'duration'              => array(
                'label'      => 'Duration', // _('Duration')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Int', Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'       => 'integer',
                'specialType' => 'seconds'
            ),
            'ringing'               => array(
                'label'       => 'Ringing', // _('Ringing')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Int', Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'        => 'integer',
            ),
            'direction'             => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, array('InArray', array(self::TYPE_INCOMING, self::TYPE_OUTGOING))),
                'label'      => 'Direction', // _('Direction')
            ),
            'source'                => array(
                'label'      => 'Source', // _('Source')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            ),
            'destination'           => array(
                'label'      => 'Destination', // _('Destination')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            ),
            'resolved_destination'           => array(
                'label'      => 'resolved destination', // _('resolved destination')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            ),
            'contact_id'              => array(
                'label'      => 'Contact',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                )
            ),
        )
    );
}
