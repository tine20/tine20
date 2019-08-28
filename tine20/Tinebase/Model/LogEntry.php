<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Tinebase_Model_LogEntry extends Tinebase_Record_Abstract
{
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
    protected static $_modelConfiguration = array(
        'version' => 1,
        'recordName' => 'LogEntry', // _('LogEntry') ngettext('LogEntry', 'LogEntries', n)
        'recordsName' => 'LogEntries', // _('LogEntries')
        'titleProperty' => 'transaction_id',
        'hasRelations' => false,
        'hasCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => false,
        'hasAttachments' => false,

        'createModule' => false,

        'exposeHttpApi' => true,
        'exposeJsonApi' => true,

        'appName' => 'Tinebase',
        'modelName' => 'LogEntry',

        'table' => array(
            'name' => 'logentries',
        ),

        'fields' => array(
            'transaction_id' => array(
                'type' => 'string',
                'length' => 255,
                'nullable' => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label' => 'Clinent Requestid', // _('Clinent Requestid')
            ),
            'request_id' => array(
                'type' => 'string',
                'length' => 255,
                'nullable' => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label' => 'Server Requestid', // _('Server Requestid')
            ),
            'user' => array(
                'label' => 'User', //_('User')
                'type' => 'user',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'timestamp' => array(
                'label' => 'Logtime', // _('Logtime')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type' => 'datetime',
            ),
            'logdifftime' => array(
                'label' => 'Difftime', // _('Difftime')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type' => 'string',
            ),
            'logruntime' => array(
                'label' => 'Runtime', // _('Difftime')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type' => 'string',
            ),
            'priority' => array(
                'type' => 'integer',
                'nullable' => false,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => True),
            ),
            'priorityName' => array(
                'type' => 'string',
                'nullable' => false,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => True),
                'label' => 'Loglevel', // _('Loglevel'),
                'queryFilter'       => true,
            ),
            'message' => array(
                'type' => 'text',
                'nullable' => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label' => 'Logstring',
                'queryFilter'       => true,
            ),
        )
    );
}
