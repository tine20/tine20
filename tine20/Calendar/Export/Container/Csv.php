<?php
/**
 * Calendar Container CSV generation class
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schhüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Calendar_Export_Container_Csv extends Tinebase_Export_CsvNew
{
    protected function _endRow()
    {
        // write admins at the end
        if ($this->_currentRowType === self::ROW_TYPE_GENERIC_HEADER) {
            // TODO translate
            $this->_writeValue('admin users');
            $this->_writeValue('read grant users');
            $this->_writeValue('read grant groups');
        } else {
            $this->_writeValue($this->_getAdminUsers());
            $this->_writeValue($this->_getReadGrantUsers());
            $this->_writeValue($this->_getReadGrantGroups());
        }

        // skip resource containers (TODO add option for this)
        if ($this->_currentRecord) {
            if (isset($this->_currentRecord->xprops()['Calendar']['Resource'])) {
                return;
            }
        }

        parent::_endRow();
    }

    protected function _getAdminUsers()
    {
        return $this->_getGrantUsers(Tinebase_Model_Grants::GRANT_ADMIN, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER);
    }

    protected function _getReadGrantUsers()
    {
        return $this->_getGrantUsers(Tinebase_Model_Grants::GRANT_READ, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER);
    }

    protected function _getReadGrantGroups()
    {
        return $this->_getGrantUsers(Tinebase_Model_Grants::GRANT_READ, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP);
    }

    protected function _getGrantUsers($matchGrant, $type)
    {
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_currentRecord->getId(), true);
        $users = [];
        foreach ($grants as $grant) {
            if ($grant->{$matchGrant}) {
                if ($grant->account_type === $type) {
                    try {
                        if ($type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
                            $group = Tinebase_Group::getInstance()->getGroupById($grant->account_id);
                            $users[] = $group->name;
                        } else {
                            $user = Tinebase_User::getInstance()->getFullUserById($grant->account_id);
                            $users[] = $user->accountLoginName;
                        }
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        // no longer valid
                    }
                } else if ($grant->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE && $type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                    $users[] = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
                }
            }
        }
        return implode(',', $users);
    }
}
