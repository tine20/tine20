<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2016-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_List_Json extends Tinebase_Convert_Json
{
    /**
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        $result = parent::fromTine20Model($_record);

        if (isset($result['members']) && is_array($result['members']) && !empty($result['members'])) {
            $contactCtrl = Addressbook_Controller_Contact::getInstance();
            $allVisibleMemberIds = array_flip($contactCtrl->search(new Addressbook_Model_ContactFilter([[
                    'field' => 'id',
                    'operator' => 'in',
                    'value' => $result['members']
                ]]), null, false, true));
            
            $oldValue = $contactCtrl->doContainerACLChecks(false);
            $raii = new Tinebase_RAII(function() use ($oldValue, $contactCtrl) {
                $contactCtrl->doContainerACLChecks($oldValue);
            });

            $result['members'] = $contactCtrl->search(new Addressbook_Model_ContactFilter([[
                    'field' => 'id',
                    'operator' => 'in',
                    'value' => $result['members']
                ]]))->toArray();

            foreach($result['members'] as &$member) {
                if (!isset($allVisibleMemberIds[$member['id']])) {
                    $member = [
                        'id'    => $member['id'],
                        'email' => $member['email'],
                        'n_fn'  => $member['n_fn'],
                    ];
                }
            }

            // only for unused variable check
            unset($raii);
        }

        return $result;
    }

    /**
     * parent converts Tinebase_Record_RecordSet to external format
     * this resolves Image Paths
     *
     * @param Tinebase_Record_RecordSet  $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return mixed
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        $this->_appendRecordPaths($_records, $_filter);

        return parent::fromTine20RecordSet($_records, $_filter, $_pagination);
    }

    /**
     * append record paths (if path filter is set)
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     *
     * TODO move to generic json converter
     */
    protected function _appendRecordPaths($_records, $_filter)
    {
        if ($_filter && $_filter->getFilter('path', /* $_getAll = */ false, /* $_recursive = */ true) !== null &&
                true === Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            $pathController = Tinebase_Record_Path::getInstance();
            foreach ($_records as $record) {
                $record->paths = $pathController->getPathsForRecord($record);
                $pathController->cutTailAfterRecord($record, $record->paths);
            }
        }
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
        $this->_resolveMemberroles($records);

        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);
    }

    /**
     * resolve memberroles
     *
     * @param $records
     */
    protected function _resolveMemberroles($records)
    {
        $listRoles = Addressbook_Controller_ListRole::getInstance()->getAll();
        $contactIds = array();
        $contacts = null;
        foreach ($records as $record) {
            if (isset($record->memberroles) && is_object($record->memberroles)) {
                $contactIds = array_merge($contactIds, $record->memberroles->contact_id);
            }
        }
        if (count($contactIds) > 0) {
            $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($contactIds);
        }
        foreach ($records as $list) {
            if (isset($list->memberroles) && is_object($list->memberroles)) {
                foreach ($list->memberroles as $memberrole) {
                    if ($contacts !== null) {
                        $contact = $contacts->getById(is_array($memberrole) ? $memberrole['contact_id'] : $memberrole->contact_id);
                        if ($contact) {
                            $memberrole->contact_id = $contact;
                        }
                    }

                    $listRole = $listRoles->getById($memberrole->list_role_id);
                    if ($listRole) {
                        $memberrole->list_role_id = $listRole;
                    }
                }
            }
        }
    }
}
