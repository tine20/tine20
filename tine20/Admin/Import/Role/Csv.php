<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Admin
 *
 * @package     Admin
 * @subpackage  Import
 *
 */
class Admin_Import_Role_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
    );
    /**
     * @var string
     */
    protected $_members;

    /**
     * @var string
     */
    protected $_groups;


    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        try {
            $role = Tinebase_Role::getInstance()->getRoleByName($_record['name']);
            $this->_inspectAfterImport($role);
            $this->_importResult['duplicatecount']++;
        } catch (Tinebase_Exception $e) {
            return parent::_importRecord($_record, $_resolveStrategy, $_recordData);
        }

    }

    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        $this->_members = $result['members'];
        $this->_groups = $result['groups'];

        return $result;
    }

    /**
     *   add role-account for user and group
     * @param $importedRecord
     */
    protected function _inspectAfterImport($importedRecord)
    {
        $role_id = $importedRecord['id'];
        $ids = array();
        if (!empty($this->_members)) {
            $members_list = explode(';', $this->_members);
            foreach ($members_list as $member) {
                $member_id = Tinebase_FullUser::getInstance()->getFullUserByLoginName($member)['accountId'];
                $ids['type'] = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
                $ids['id'] = $member_id;
                Tinebase_Role::getInstance()->addRoleMember($role_id, $ids);
            }
        }
        if (!empty($this->_groups)) {
            $groups = explode(';', $this->_groups);
            foreach ($groups as $group) {
                $group_id = Tinebase_Group::getInstance()->getGroupByName($group)['id'];
                $ids['type'] = Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP;
                $ids['id'] = $group_id;
                Tinebase_Role::getInstance()->addRoleMember($role_id, $ids);
            }
        }
        // @ToDo Rights for the Roles
        // Tinebase_Role::getInstance()->addRoleRight($role_id,'671a772cb6e802a579d61ed76fcd45e3cdcc87ec','run');
    }
}
