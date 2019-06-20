<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * class to alter Setup_Controller for test purpose
 *
 * @package     Setup
 * @subpackage  Controller
 */
class Setup_ControllerMock extends Setup_Controller
{
    /**
     * holds the instance of the singleton
     *
     * @var Setup_Controller
     */
    private static $_instance = null;

    /**
     * the singleton pattern
     *
     * @return Setup_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new Setup_ControllerMock;
        }

        return self::$_instance;
    }

    /**
     * restore
     *
     * @param $options array(
     *      'backupDir'  => string // location of backup to restore
     *      'config'     => bool   // restore config
     *      'db'         => bool   // restore database
     *      'files'      => bool   // restore files
     *    )
     *
     * @param $options
     * @throws Setup_Exception
     */
    public function restore($options)
    {
        parent::restore($options);

        // required for update path to Adb 12.7 ... can be removed once we drop updatability from < 12.7 to 12.7+
        Tinebase_Group_Sql::doJoinXProps(false);
        
        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        if (null === ($oldUser = Tinebase_Core::getUser())) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }

        foreach ($setupUser->getGroupMemberships() as $gId) {
            Tinebase_Group::getInstance()->removeGroupMember($gId, $setupUser->accountId);
        }
        Tinebase_Group::unsetInstance();
        foreach (Tinebase_Acl_Roles::getInstance()->getRoleMemberships($setupUser->accountId) as $rId) {
            Tinebase_Acl_Roles::getInstance()->removeRoleMember($rId, $setupUser->accountId);
        }
        Tinebase_Acl_Roles::unsetInstance();

        if (null === $oldUser) {
            Tinebase_Core::unsetUser();
        }
    }
}