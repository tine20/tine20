<?php
/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     MeetingManager
 * @subpackage  Setup
 */
class GDPR_Setup_Uninitialize extends Setup_Uninitialize
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
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME, null, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            GDPR_Controller_DataProvenance::ADB_CONTACT_REASON_CUSTOM_FIELD_NAME, null, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, null, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME, null, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }
    }

    /**
     * uninit scheduler tasks
     */
    protected function _uninitializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        GDPR_Scheduler_Task::removeDeleteExpiredDataTask($scheduler);
    }
}
