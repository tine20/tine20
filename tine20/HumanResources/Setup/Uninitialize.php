<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for HumanResources uninitialization
 *
 * @package     HumanResources
 */
class HumanResources_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninit COR system customfields
     */
    protected function _uninitializeCORSystemCustomField()
    {
        try {
            $appId = Tinebase_Application::getInstance()->getApplicationByName(Timetracker_Config::APP_NAME)->getId();
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON, null, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
    }
}
