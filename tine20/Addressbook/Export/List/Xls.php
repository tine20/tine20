<?php
/**
 * Addressbook List Xls generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook List Xls generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 *
 */
class Addressbook_Export_List_Xls extends Tinebase_Export_Xls
{
    protected $_defaultExportname = 'adb_list_xls';

    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        parent::_resolveRecords($_records);

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
}
