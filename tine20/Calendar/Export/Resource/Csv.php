<?php
/**
 * Calendar Resource CSV generation class
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schhüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Calendar_Export_Resource_Csv extends Tinebase_Export_CsvNew
{
    protected function _endRow()
    {
        // write admins at the end
        if ($this->_currentRowType === self::ROW_TYPE_GENERIC_HEADER) {
            // TODO translate
            $this->_writeValue('admin users');
        } else {
            $this->_writeValue($this->_getAdminUsers());
        }

        parent::_endRow();
    }

    protected function _getAdminUsers()
    {
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_currentRecord->container_id, true);
        $admins = [];
        foreach ($grants as $grant) {
            if ($grant->resourceAdminGrant && $grant->account_type === 'user') {
                $user = Tinebase_User::getInstance()->getFullUserById($grant->account_id);
                $admins[] = $user->accountLoginName;
            }
        }
        return implode(',', $admins);
    }
}
