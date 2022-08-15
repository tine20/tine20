<?php
/**
 * class to handle grants
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines Calendar Resource grants
 *
 * @package     Calendar
 * @subpackage  Model
 *
 */
class Calendar_Model_ResourceGrants extends Tinebase_Model_Grants
{
    const RESOURCE_INVITE   = 'resourceInviteGrant';
    const RESOURCE_STATUS   = 'resourceStatusGrant';
    const RESOURCE_NOTIFICATION    = 'resourceNotificationGrant';
    const RESOURCE_READ     = 'resourceReadGrant';
    const RESOURCE_EDIT     = 'resourceEditGrant';
    const RESOURCE_EXPORT   = 'resourceExportGrant';
    const RESOURCE_SYNC     = 'resourceSyncGrant';
    const RESOURCE_ADMIN    = 'resourceAdminGrant';
    const EVENTS_ADD        = 'eventsAddGrant';
    const EVENTS_READ       = 'eventsReadGrant';
    const EVENTS_EXPORT     = 'eventsExportGrant';
    const EVENTS_SYNC       = 'eventsSyncGrant';
    const EVENTS_FREEBUSY   = 'eventsFreebusyGrant';
    const EVENTS_EDIT       = 'eventsEditGrant';
    const EVENTS_DELETE     = 'eventsDeleteGrant';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';

    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return array_merge(parent::getAllGrants(), [
            self::RESOURCE_INVITE,
            self::RESOURCE_STATUS,
            self::RESOURCE_NOTIFICATION,
            self::RESOURCE_READ,
            self::RESOURCE_EDIT,
            self::RESOURCE_EXPORT,
            self::RESOURCE_SYNC,
            self::RESOURCE_ADMIN,
            self::EVENTS_ADD,
            self::EVENTS_READ,
            self::EVENTS_EXPORT,
            self::EVENTS_SYNC,
            self::EVENTS_FREEBUSY,
            self::EVENTS_EDIT,
            self::EVENTS_DELETE,
        ]);
    }

    /**
     * @return bool
     */
    public static function doSetGrantFailsafeCheck()
    {
        return false;
    }

    /**
     * @param Zend_Db_Select $_select
     * @param Tinebase_Model_Application $_application
     * @param string $_accountId
     * @param string|array $_grant
     */
    public static function addCustomGetSharedContainerSQL(Zend_Db_Select $_select,
        Tinebase_Model_Application $_application, $_accountId, $_grant)
    {
        $grants = is_array($_grant) ? $_grant : array($_grant);
        if (count($grants) > 1 || $grants[0] !== Tinebase_Model_Grants::GRANT_READ) {
            return;
        }

        $db = $_select->getAdapter();
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($_accountId);
        $roleMemberships    = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($_accountId);
        // enforce string for pgsql
        array_walk($roleMemberships, function(&$item) {$item = (string)$item;});
        $quotedActId   = $db->quoteIdentifier('container_acl.account_id');
        $quotedActType = $db->quoteIdentifier('container_acl.account_type');
        $anyoneSelect = '';
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
            $anyoneSelect = ' OR ' . $quotedActType . $db->quoteInto(' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        }

        $where = join(' ', $_select->getPart(Zend_Db_Select::WHERE));
        $_select->reset(Zend_Db_Select::WHERE);
        $_select->where($where);
        $_select->orWhere(
            $db->quoteInto($db->quoteIdentifier('container.application_id') .' = ?', $_application->getId()) . ' AND ' .
            $db->quoteInto($db->quoteIdentifier('container.type') . ' = ?', Tinebase_Model_Container::TYPE_SHARED) . ' AND ' .
            $db->quoteIdentifier('container.is_deleted') . ' = 0 AND ' .
            $db->quoteIdentifier('container.xprops') . $db->quoteInto(' LIKE ?', '%"Resource":{"resource_id":"%') . ' AND ((' .
            $db->quoteInto("{$quotedActId} = ? AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_USER), $_accountId) . ' ) OR (' .
            $db->quoteInto("{$quotedActId} IN (?) AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP), empty($groupMemberships) ? ' ' : $groupMemberships) . ' ) OR (' .
            $db->quoteInto("{$quotedActId} IN (?) AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE), empty($roleMemberships) ? ' ' : $roleMemberships) . ' )' .
            $anyoneSelect . ' ) AND ' . $db->quoteIdentifier('container_acl.account_grant') . $db->quoteInto(' LIKE ?', Calendar_Model_ResourceGrants::EVENTS_FREEBUSY)
        );
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
