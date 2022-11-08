<?php
/**
 * Tine 2.0
 *
 * @package     DFCom
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     DFCom
 * @subpackage  Setup
 */
class DFCom_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * @param Tinebase_Model_Application $_application
     * @param ?array $_options
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _uninitializeCustomFields(Tinebase_Model_Application $_application, $_options = null)
    {
        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName(HumanResources_Config::APP_NAME)->getId(),
            'dfcom_id', HumanResources_Model_Employee::class, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }
    }
}
