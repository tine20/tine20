<?php

/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as TMCC;


class GDPR_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId();
        $cfCfg = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME,
            Addressbook_Model_Contact::class, true);

        $cfCfg->definition = [
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
            ];
        Tinebase_CustomField::getInstance()->updateCustomField($cfCfg);

        $this->addApplicationUpdate(GDPR_Config::APP_NAME, '12.1', self::RELEASE012_UPDATE001);
    }
}
