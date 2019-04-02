<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for HumanResources uninitialization
 *
 * @package     Sales
 */
class HumanResources_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninit scheduler tasks
     */
    protected function _uninitializeCORSystemCustomField()
    {
        try {
            $appId = Tinebase_Application::getInstance()->getApplicationByName(Timetracker_Config::APP_NAME)->getId();

            Tinebase_CustomField::getInstance()->deleteCustomField(
                Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                    HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON, null, true)
            );
        } catch (Tinebase_Exception_NotFound $tenf) {}
    }
}