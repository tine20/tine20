<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Timetracker
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Timetracker
 * @subpackage  Convert
 */
class Timetracker_Convert_Timeaccount_Json extends Tinebase_Convert_Json
{
    /**
     * resolve multiple record fields (Tinebase_ModelConfiguration._recordsFields)
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     */
    protected function _resolveMultipleRecordFields(Tinebase_Record_RecordSet $_records, $modelConfiguration = NULL)
    {
        // grants cannnot be resolved the default way, other records fields must not be resolved
    }

    /**
     * converts Tinebase_Record_Abstract to external format
     *
     * @param  Tinebase_Record_Abstract  $_record
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        $recordArray = parent::fromTine20Model($_record);

        // When editing a single TA we send _ALL_ grants to the client
        $recordArray['grants'] = Timetracker_Controller_Timeaccount::getInstance()->getRecordGrants($_record)->toArray();
        foreach ($recordArray['grants'] as &$value) {
            switch ($value['account_type']) {
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                    $value['account_name'] = Tinebase_User::getInstance()->getUserById($value['account_id'])->toArray();
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    $value['account_name'] = Tinebase_Group::getInstance()->getGroupById($value['account_id'])->toArray();
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                    $value['account_name'] = array('accountDisplayName' => 'Anyone');
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                    $value['account_name'] = Tinebase_Acl_Roles::getInstance()->getRoleById($value['account_id'])->toArray();
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument('Unsupported accountType.');
                    break;
            }
        }

        return $recordArray;
    }

}
