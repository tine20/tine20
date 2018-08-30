<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Model
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 *
 * @property string                     name
 * @property Tinebase_Scheduler_Task    config
 * @property Tinebase_DateTime          last_run
 * @property int                        last_duration
 * @property string                     lock_id
 * @property Tinebase_DateTime          next_run
 * @property Tinebase_DateTime          last_failure
 * @property int                        failure_count
 * @property Tinebase_DateTime          server_time
 */

class Tinebase_Model_SchedulerTask extends Tinebase_Record_Abstract
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
    protected static $_modelConfiguration = [
        'version'           => 1,
        'recordName'        => 'Scheduler task',
        'recordsName'       => 'Scheduler tasks', // ngettext('Scheduler task', 'Scheduler tasks', n)
        //'containerProperty' => 'container_id',
        'titleProperty'     => 'name',
        //'containerName'     => 'Inventory item list',
        //'containersName'    => 'Inventory item lists', // xnxgettext('Inventory item list', 'Inventory item lists', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => false,
        'hasAttachments'    => false,
        'exposeJsonApi'     => false,

        'createModule'      => false,

        'appName'           => 'Tinebase',
        'modelName'         => 'SchedulerTask',

        'table'             => [
            'name'    => Tinebase_Backend_Scheduler::TABLE_NAME,
            'indexes' => [
                'next_run' => [
                    'columns' => ['next_run']
                ]
            ],
            'uniqueConstraints' => [
                'name' => [
                    'columns' => ['name']
                ]
            ]
        ],

        'fields'            => [
            'name' => [
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Name', // _('Name')
                'queryFilter'   => true
            ],
            'config' => [
                'type'          => 'text',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Configuration', // _('Configuration')
                'converters'    => [Tinebase_Scheduler_TaskConverter::class],
                'inputFilters'  => []
            ],
            'last_run' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Last run', // _('Last run')
                'default'       => null,
                'type'          => 'datetime',
                'nullable'      => true,
            ],
            'last_duration' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Last run duration', // _('Last run duration')
                'default'       => null,
                'type'          => 'integer',
                'nullable'      => true,
            ],
            'lock_id' => [
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Lock id', // _('Lock id')
                'default'       => null,
                'nullable'      => true,
            ],
            'next_run' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Next run', // _('Next run')
                'type'          => 'datetime',
            ],
            'last_failure' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Last failure', // _('Last failure')
                'default'       => null,
                'type'          => 'datetime',
                'nullable'      => true,
            ],
            'failure_count' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Failure count', // _('Failure count')
                'default'       => 0,
                'type'          => 'integer',
            ],
            'server_time' => [
                'type'          => 'virtual',
                'config'        => ['type' => 'datetime']
            ]
        ]
    ];

    /**
     * @return bool
     */
    public function run()
    {
        return $this->config->run();
    }
}