<?php
/**
 * class to handle grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * defines Timeaccount grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 *  */
class Timetracker_Model_TimeaccountGrants extends Tinebase_Model_Grants
{
    const READ_OWN = 'readOwnGrant';
    const REQUEST_OWN = 'requestOwnGrant';

    /**
     * constant for book own TS grant
     *
     */
    const BOOK_OWN = 'bookOwnGrant';

    /**
     * constant for view all TS 
     *
     */
    const VIEW_ALL = 'viewAllGrant';

    /**
     * constant for book TS for all users
     *
     */
    const BOOK_ALL = 'bookAllGrant';

    /**
     * constant for manage billable in all bookable TS
     *
     */
    const MANAGE_BILLABLE = 'manageBillableGrant';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Timetracker';
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        $allGrants = array(
            self::READ_OWN,
            self::REQUEST_OWN,
            self::BOOK_OWN,
            self::VIEW_ALL,
            self::BOOK_ALL,
            self::MANAGE_BILLABLE,
            self::GRANT_EDIT,
            Tinebase_Model_Grants::GRANT_EXPORT,
            Tinebase_Model_Grants::GRANT_ADMIN,
        );
    
        return $allGrants;
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
            self::READ_OWN => true,
            self::REQUEST_OWN => true,
            Timetracker_Model_TimeaccountGrants::BOOK_OWN => TRUE,
            Timetracker_Model_TimeaccountGrants::VIEW_ALL => TRUE,
            Timetracker_Model_TimeaccountGrants::BOOK_ALL => TRUE,
            Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE => TRUE,
            Tinebase_Model_Grants::GRANT_EXPORT => TRUE,
            Tinebase_Model_Grants::GRANT_ADMIN => TRUE,
        );
        $grants = array_merge($grants, $_additionalGrants);
        return new Tinebase_Record_RecordSet(self::class, array(array_merge(array(
            'account_id'     => $accountId,
            'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
        ), $grants)));
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
