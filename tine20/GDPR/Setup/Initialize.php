<?php
/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * GDPR setup initialize class
 *
 * @package     GDPR
 * @subpackage  Setup
 */
class GDPR_Setup_Initialize extends Setup_Initialize
{
    protected function _initializeCustomFields()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $appId = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId();

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME,
            'application_id' => $appId,
            'model' => Addressbook_Model_Contact::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'GDPR Intended Purpose',
                    TMCC::TYPE              => TMCC::TYPE_RECORDS,
                    TMCC::CONFIG            => [
                        TMCC::APP_NAME          => GDPR_Config::APPNAME,
                        TMCC::MODEL_NAME        => GDPR_Model_DataIntendedPurposeRecord::MODEL_NAME_PART,
                        TMCC::REF_ID_FIELD      => 'record',
                        TMCC::DEPENDENT_RECORDS => true,
                        TMCC::FILTER_OPTIONS    => [
                            GDPR_Model_DataIntendedPurposeRecordFilter::OPTIONS_SHOW_WITHDRAWN => true,
                            'doJoin'                => true,
                        ],
                    ],
                ],
            ]
        ], true));


        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME,
            'application_id' => $appId,
            'model' => Addressbook_Model_Contact::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'GDPR Blacklist',
                    TMCC::TYPE              => TMCC::TYPE_BOOLEAN,
                    TMCC::DEFAULT_VAL       => 0,
                    TMCC::VALIDATORS        => [
                        Zend_Filter_Input::ALLOW_EMPTY      => true,
                        Zend_Filter_Input::DEFAULT_VALUE    => 0,
                    ],
                    TMCC::INPUT_FILTERS     => [
                        Zend_Filter_Empty::class            => 0,
                    ],
                ],
                Tinebase_Model_CustomField_Config::CONTROLLER_HOOKS => [
                    TMCC::CONTROLLER_HOOK_BEFORE_UPDATE => [
                        [GDPR_Controller_DataIntendedPurposeRecord::class, 'adbContactBeforeUpdateHook'],
                    ],
                ],
            ]
        ], true));


        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME,
            'application_id' => $appId,
            'model' => Addressbook_Model_Contact::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'GDPR Data Provenance',
                    TMCC::TYPE              => TMCC::TYPE_RECORD,
                    TMCC::IS_VIRTUAL        => true,
                    TMCC::CONFIG            => [
                        TMCC::APP_NAME          => GDPR_Config::APPNAME,
                        TMCC::MODEL_NAME        => GDPR_Model_DataProvenance::MODEL_NAME_PART,
                    ],
                    TMCC::INPUT_FILTERS     => [
                        GDPR_Model_Filter_DataProvenance::class,
                    ],
                    TMCC::VALIDATORS        => [
                        GDPR_Model_Validator_DataProvenance::class,
                        Zend_Filter_Input::DEFAULT_VALUE => '',
                    ],
                ],
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [GDPR_Controller_DataProvenance::class, 'modelConfigHook'],
                ],
                /*'uiconfig' => [
                    'order' => '',//'0/0/0/0/0/5',
                    'group' => '',
                    'tab'   => '', //'0',
                ],*/
            ]
        ], true));


        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => GDPR_Controller_DataProvenance::ADB_CONTACT_REASON_CUSTOM_FIELD_NAME,
            'application_id' => $appId,
            'model' => Addressbook_Model_Contact::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'GDPR Data Provenance Reason',
                    TMCC::TYPE              => TMCC::TYPE_STRING,
                    TMCC::IS_VIRTUAL        => true,
                ],/*
                'uiconfig' => [
                    'order' => '',//'0/0/0/0/0/5',
                    'group' => '',
                    'tab'   => '', //'0',
                ],*/
            ]
        ], true));

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_EXPIRY_CUSTOM_FIELD_NAME,
            'application_id' => $appId,
            'model' => Addressbook_Model_Contact::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'GDPR Data Expiry Date',
                    TMCC::TYPE              => TMCC::TYPE_DATE,
                    TMCC::NULLABLE          => true,
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                ],
            ]
        ], true));
    }

    protected function _initializeDefaultDataProvenance()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $dP = GDPR_Controller_DataProvenance::getInstance()->create(new GDPR_Model_DataProvenance([
            'name'          => 'Tine2.0',
        ]));

        GDPR_Config::getInstance()->set(GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE, $dP->getId());
    }

    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        GDPR_Scheduler_Task::addDeleteExpiredDataTask($scheduler);
    }
}
