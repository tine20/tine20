<?php

/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class GDPR_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('GDPR', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $appId = Tinebase_Application::getInstance()->getApplicationByName(Addressbook_Config::APP_NAME)->getId();
        $cfCfg = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME,
            Addressbook_Model_Contact::class, true);

        $cfCfg->definition = [
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
        ];
        Tinebase_CustomField::getInstance()->updateCustomField($cfCfg);

        $this->addApplicationUpdate(GDPR_Config::APPNAME, '15.1', self::RELEASE015_UPDATE001);
    }
}
