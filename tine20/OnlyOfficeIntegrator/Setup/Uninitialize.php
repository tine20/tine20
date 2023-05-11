<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 *
 * @package     OnlyOfficeIntegrator
 */
class OnlyOfficeIntegrator_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninitialize customfields
     *
     * @param Tinebase_Model_Application $_applications
     * @param array | null $_options
     * @return void
     */
    protected function _uninitializeCustomFields(Tinebase_Model_Application $_application, $_options = null)
    {
        self::removeCfs();
    }

    protected static function removeCfs()
    {
        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId(),
            OnlyOfficeIntegrator_Config::FM_NODE_EDITING_CFNAME, null, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId(),
            OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME, null, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }
    }

    public static function removeAuxiliaryDataHook()
    {
        self::removeCfs();
    }
}
