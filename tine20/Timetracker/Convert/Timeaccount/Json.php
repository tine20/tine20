<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Timetracker
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface  $_record
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
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

    /**
     * resolves child records before converting the record set to an array
     *
     * @param Tinebase_Record_RecordSet $records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);
        $this->_resolveGrants($records);
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _resolveGrants(Tinebase_Record_RecordSet $_records)
    {
        $manageAllRight = Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE);
        foreach ($_records as $timeaccount) {
            $timeaccountGrantsArray = $timeaccount->account_grants;
            if (! $timeaccountGrantsArray) {
                // grants missing - TODO re-fetch them?
                // Timetracker_Controller_Timeaccount::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::get('currentAccount'));
                $timeaccountGrantsArray = [];
                $modifyGrant = $manageAllRight;
            } else {
                if ($timeaccountGrantsArray instanceof Tinebase_Model_Grants) {
                    $timeaccountGrantsArray = $timeaccountGrantsArray->toArray();
                }
                $modifyGrant = $manageAllRight || $timeaccountGrantsArray[Timetracker_Model_TimeaccountGrants::GRANT_ADMIN];
            }

            $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_READ]   = true;
            $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_EDIT]   = $modifyGrant;
            $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_DELETE] = $modifyGrant;
            $timeaccount->account_grants = $timeaccountGrantsArray;

            // also move the grants into the container_id property, as the clients expects records to
            // be contained in some kind of container where it searches the grants in
            if (is_array($timeaccount->container_id) || is_object($timeaccount->container_id)) {
                $timeaccount->container_id['account_grants'] = $timeaccountGrantsArray;
            }
        }
    }
}
