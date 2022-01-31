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
 * class to hide external invitation calendars
 *
 * @package     Calendar
 * @subpackage  Model
 *
 */
class Calendar_Model_ExternalInvitationGrants extends Tinebase_Model_Grants
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';

    /**
     * we filter shared external invitation calendars here
     *
     * @param Zend_Db_Select $_select
     * @param Tinebase_Model_Application $_application
     * @param string $_accountId
     * @param string|array $_grant
     */
    public static function addCustomGetSharedContainerSQL(
            Zend_Db_Select $_select,
            Tinebase_Model_Application $_application,
            $_accountId,
            $_grant
    ) {
        $_select->where($_select->getAdapter()->quoteIdentifier('container.xprops') . ' NOT LIKE "%' .
            Calendar_Controller::XPROP_EXTERNAL_INVITATION_CALENDAR . '%"');
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
