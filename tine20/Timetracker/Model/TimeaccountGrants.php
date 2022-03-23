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

    const MODEL_NAME_PART = 'TimeaccountGrants';
    
    const READ_OWN = 'readOwnGrant';
    const REQUEST_OWN = 'requestOwnGrant';
    const BOOK_OWN = 'bookOwnGrant';
    const VIEW_ALL = 'viewAllGrant';
    const BOOK_ALL = 'bookAllGrant';
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
            self::GRANT_EXPORT,
            self::GRANT_ADMIN,
        );
    
        return $allGrants;
    }

    public static function getAllGrantsMC(): array
    {
        return [
            self::READ_OWN    => [
                self::LABEL         => 'Read Own', // _('Read Own')
                self::DESCRIPTION   => 'The grant to read own time sheets in this time account.', // _('The grant to read own time sheets in this time account.')
            ],
            self::REQUEST_OWN => [
                self::LABEL         => 'Request Own',  // _('Request Own')
                self::DESCRIPTION   => 'The grant to add own time sheets request in this time account.',  // _('The grant to add own time sheets request in this time account.')
            ],
            self::BOOK_OWN => [
                self::LABEL         => 'Book Own', // _('Book Own')
                self::DESCRIPTION   => 'The grant to add Timesheets to this Timeaccount', // _('The grant to add Timesheets to this Timeaccount')
            ],
            self::VIEW_ALL => [
                self::LABEL         => 'View All', // _('View All')
                self::DESCRIPTION   => 'The grant to view Timesheets of other users', // _('The grant to view Timesheets of other users')
            ],
            self::BOOK_ALL => [
                self::LABEL         => 'Book All', // _('Book All')
                self::DESCRIPTION   => 'The grant to add Timesheets for other users', // _('The grant to add Timesheets for other users')
            ],
            self::MANAGE_BILLABLE => [
                self::LABEL         => 'Manage Clearing', // _('Manage Clearing')
                self::DESCRIPTION   => 'The grant to manage clearing of Timesheets', // _('The grant to manage clearing of Timesheets')
            ],
        ];
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
