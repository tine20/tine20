<?php
/**
 * class to filter the DataProvenance attribute on records
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to filter the DataProvenance attribute on records
 * for example on Addressbook_Model_Contact::{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME}
 * it will apply the default value if value is empty and configuration provides a default
 *
 * @package     GDPR
 * @subpackage  Model
 */
class GDPR_Model_Filter_DataProvenance extends Tinebase_Record_Filter_RecordId
{
    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        if (empty($value) && GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT ===
                GDPR_Config::getInstance()->{GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY}) {
            $value = GDPR_Config::getInstance()->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE};
        }

        return parent::filter($value);
    }
}