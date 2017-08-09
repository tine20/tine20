<?php
/**
 * Addressbook List Export trait
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

trait Addressbook_Export_List_Trait
{
    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        parent::_resolveRecords($_records);

        if ($_records->getRecordClassName() != Addressbook_Model_List::class) {
            return;
        }

        if ('adb_list_doc' === $this->_config->name) {
            $this->_listDocResolveRecords($_records);
            return;
        }

        /** @var Addressbook_Model_List $record */
        foreach ($_records as $record) {
            $memberRolesBackend = Addressbook_Controller_List::getInstance()->getMemberRolesBackend();
            $filter = new Addressbook_Model_ListMemberRoleFilter(array(
                array('field' => 'list_id',      'operator' => 'equals', 'value' => $record->getId())
            ));

            $members = array();
            $listRoles = array();
            /** @var Addressbook_Model_ListMemberRole $listMemberRole */
            foreach($memberRolesBackend->search($filter) as $listMemberRole)
            {
                if (!isset($members[$listMemberRole->contact_id])) {
                    $members[$listMemberRole->contact_id] = array();
                }
                $members[$listMemberRole->contact_id][] = $listMemberRole->list_role_id;
                $listRoles[$listMemberRole->list_role_id] = true;
            }

            if (count($listRoles) > 0) {
                $listRoles = Addressbook_Controller_ListRole::getInstance()->getMultiple(array_keys($listRoles));
            }

            $memberRecords = array();
            if (is_array($record->members)) {
                $memberRecords = Addressbook_Controller_Contact::getInstance()->getMultiple($record->members);
            }

            $str = '';
            /** @var Addressbook_Model_Contact $contact */
            foreach($memberRecords as $contact) {
                $str .= ($str===''?'':', ') . $contact->n_fn;
                if (isset($members[$contact->getId()])) {
                    $str .= ' (';
                    $first = true;
                    foreach($members[$contact->getId()] as $listRole) {
                        /** @var Addressbook_Model_ListRole $listRole */
                        if (null !== ($listRole = $listRoles->getById($listRole))) {
                            $str .= (true===$first?'':', ') . $listRole->name;
                            $first = false;
                        }
                    }
                    $str .= ')';
                }
            }

            $record->memberroles = $str;
        }
    }

    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _listDocResolveRecords(Tinebase_Record_RecordSet $_records)
    {
        $memberRolesBackend = Addressbook_Controller_List::getInstance()->getMemberRolesBackend();

        /** @var Addressbook_Model_List $record */
        foreach ($_records as $record) {
            $filter = new Addressbook_Model_ListMemberRoleFilter(array(
                array('field' => 'list_id',      'operator' => 'equals', 'value' => $record->getId())
            ));

            $members = array();
            $listRoles = array();
            /** @var Addressbook_Model_ListMemberRole $listMemberRole */
            foreach($memberRolesBackend->search($filter) as $listMemberRole)
            {
                if (!isset($members[$listMemberRole->contact_id])) {
                    $members[$listMemberRole->contact_id] = array();
                }
                $members[$listMemberRole->contact_id][] = $listMemberRole->list_role_id;
                $listRoles[$listMemberRole->list_role_id] = true;
            }

            if (count($listRoles) > 0) {
                $listRolesResultSet = Addressbook_Controller_ListRole::getInstance()->getMultiple(array_keys($listRoles));
            }

            $memberRecords = array();
            if (is_array($record->members)) {
                $memberRecords = Addressbook_Controller_Contact::getInstance()->getMultiple($record->members);
            }
            $newMembers = array();

            /** @var Addressbook_Model_Contact $contact */
            foreach($memberRecords as $contact) {
                $newMembers[$contact->getId()] = $contact->toArray();
                if (isset($members[$contact->getId()])) {
                    $str = '';
                    $first = true;
                    foreach($members[$contact->getId()] as $listRoleId) {
                        /** @var Addressbook_Model_ListRole $listRole */
                        if (null !== ($listRole = $listRolesResultSet->getById($listRoleId))) {
                            $str .= (true===$first?'':', ') . $listRole->name;
                            $first = false;
                            if (true === $listRoles[$listRoleId]) {
                                $listRoles[$listRoleId] = array(
                                    'name'      => $listRole->name,
                                    'members'   => $contact->n_fn
                                );
                            } else {
                                $listRoles[$listRoleId]['members'] .= ', ' . $contact->n_fn;
                            }
                        }
                    }
                    $newMembers[$contact->getId()]['memberroles'] = $str;
                } else {
                    $newMembers[$contact->getId()]['memberroles'] = '';
                }
            }

            $record->members = $newMembers;
            $record->memberroles = $listRoles;
        }
    }
}