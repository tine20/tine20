<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Model
 *
 * @package     Admin
 * @subpackage  Scheduler
 *
 * @property string                     $name
 * @property Tinebase_Scheduler_Task    $config
 * @property Tinebase_DateTime          $last_run
 * @property int                        $last_duration
 * @property string                     $lock_id
 * @property Tinebase_DateTime          $next_run
 * @property Tinebase_DateTime          $last_failure
 * @property int                        $failure_count
 * @property Tinebase_DateTime          $server_time
 */

class Admin_Model_SchedulerTask extends Tinebase_Model_SchedulerTask
{
    public const MODEL_NAME_PART = 'SchedulerTask';

    public const FLD_CONFIG_CLASS = 'config_class';
    public const FLD_CRON = 'cron';

    public static function inheritModelConfigHook(array &$_definition)
    {
        $_definition[self::APP_NAME] = Admin_Config::APP_NAME;
        unset($_definition[self::VERSION]);
        unset($_definition[self::TABLE]);
        $_definition[self::EXPOSE_JSON_API] = true;
        $_definition[self::CREATE_MODULE] = true;

        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self:: FLD_NAME, [
            self::FLD_CRON => [
                self::TYPE          => self::TYPE_STRING,
                self::LABEL         => 'Period', // _('Period')
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ]
        ]);

        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_CRON, [
            self::FLD_CONFIG_CLASS => [
                self::TYPE      => self::TYPE_MODEL,
                self::LABEL     => 'Task type', // _('Task type')
                self::CONFIG    => [
                    self::AVAILABLE_MODELS => [
                        Admin_Model_SchedulerTask_Import::class,
                    ],
                ],
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ]
        ]);

        $_definition[self::FIELDS][self::FLD_CONFIG] = [
            self::TYPE      => self::TYPE_DYNAMIC_RECORD,
            self::LABEL     => 'Task config', // _('Task config')
            self::CONFIG    => [
                self::REF_MODEL_FIELD       => self::FLD_CONFIG_CLASS,
                self::PERSISTENT            => true,
            ],
            self::VALIDATORS    => [
                Zend_Filter_Input::ALLOW_EMPTY => false,
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
            ],
        ];

        $_definition[self::FIELDS][self::FLD_NEXT_RUN][self::VALIDATORS][Zend_Filter_Input::ALLOW_EMPTY] = true;
        $_definition[self::FIELDS][self::FLD_NEXT_RUN][self::VALIDATORS][Zend_Filter_Input::DEFAULT_VALUE] = '1970-01-01 00:00:00';
        $_definition[self::FIELDS][self::FLD_NEXT_RUN][self::INPUT_FILTERS] = [
            Zend_Filter_Empty::class => '1970-01-01 00:00:00',
        ];
    }

    public function hydrateFromBackend(array &$data)
    {
        $config = json_decode($data[self::FLD_CONFIG], true);
        $data[self::FLD_CRON] = $config[self::FLD_CRON];
        if (isset($config['config'])) {
            $data[self::FLD_CONFIG] = $config['config'];
        }
        if (isset($config['config_class'])) {
            $data[self::FLD_CONFIG_CLASS] = $config['config_class'];
        }

        parent::hydrateFromBackend($data);
    }

    public function run()
    {
        throw new Tinebase_Exception_NotImplemented('must not be called');
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
