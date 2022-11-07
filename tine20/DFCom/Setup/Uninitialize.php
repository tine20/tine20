<?php
/**
 * Tine 2.0
 *
 * @package     DFCom
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * uninitialize customfields
     *
     * @param Tinebase_Model_Application $_applications
     * @param array | null $_options
     * @return void
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
