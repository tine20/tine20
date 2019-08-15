<?php
/**
 * grants model of a account
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * grants model
 *
 * @package     Tinebase
 * @subpackage  Record
 * @property string         id
 * @property string         record_id
 * @property string         account_grant
 * @property string         account_id
 * @property string         account_type
 */
class Felamimail_Model_AccountGrants extends Tinebase_Model_Grants
{
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        $allGrants = array(
            self::GRANT_READ,
            self::GRANT_EDIT,
            self::GRANT_DELETE,
            self::GRANT_ADMIN,
        );

        return $allGrants;
    }

    /**
     * return default grants with read for user group, write/admin for current user and write/admin for admin group
     *
     * @param array $_additionalGrants
     * @param array $_additionalAdminGrants
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Grants
     */
    public static function getDefaultGrants($_additionalGrants = array(), $_additionalAdminGrants = array())
    {
        $groupsBackend = Tinebase_Group::getInstance();
        $adminGrants = array_merge(array_merge([
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_ADD => true,
            Tinebase_Model_Grants::GRANT_EDIT => true,
            Tinebase_Model_Grants::GRANT_DELETE => true,
            Tinebase_Model_Grants::GRANT_ADMIN => true,
            Tinebase_Model_Grants::GRANT_EXPORT => true,
            Tinebase_Model_Grants::GRANT_SYNC => true,
        ], $_additionalGrants), $_additionalAdminGrants);
        $grants = [
            array_merge([
                'account_id' => $groupsBackend->getDefaultGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
            ], $_additionalGrants),
            array_merge([
                'account_id' => $groupsBackend->getDefaultAdminGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            ], $adminGrants),
        ];

        if (is_object(Tinebase_Core::getUser())) {
            $grants[] = array_merge([
                'account_id' => Tinebase_Core::getUser()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            ], $adminGrants);
        }

        return new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $grants,true);
    }

    /**
     * return personal grants for given account
     *
     * @param string|Tinebase_Model_User          $_accountId
     * @param array $_additionalGrants
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Grants
     */
    public static function getPersonalGrants($_accountId, $_additionalGrants = array())
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $grants = array(Tinebase_Model_Grants::GRANT_READ      => true,
            Tinebase_Model_Grants::GRANT_ADD       => true,
            Tinebase_Model_Grants::GRANT_EDIT      => true,
            Tinebase_Model_Grants::GRANT_DELETE    => true,
            Tinebase_Model_Grants::GRANT_EXPORT    => true,
            Tinebase_Model_Grants::GRANT_SYNC      => true,
            Tinebase_Model_Grants::GRANT_ADMIN     => true,
        );
        $grants = array_merge($grants, $_additionalGrants);
        return new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array_merge(array(
            'account_id'     => $accountId,
            'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
        ), $grants)));
    }

    /**
     * @return bool
     */
    public static function doSetGrantFailsafeCheck()
    {
        return true;
    }
}
