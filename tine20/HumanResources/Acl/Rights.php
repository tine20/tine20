<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * this class handles the rights for the hr application
 * @package     Tinebase
 * @subpackage  Acl
 */
class HumanResources_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to show private data of the employee records
     * @static string
     */
    const MANAGE_PRIVATE = 'manage_private';

    /**
     * the right to show and edit working streams
     * @static string
     */
    const MANAGE_STREAMS = 'manage_streams';

    /**
     * the right to show and edit working times
     * @static string
     */
    const MANAGE_WORKINGTIME = 'manage_workingtime';

    /**
     * the right to show and edit employee and free time data
     * @static string
     */
    const MANAGE_EMPLOYEE = 'manage_employee';

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Acl_Rights
     */
    private static $_instance = NULL;

    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone()
    {
    }

    /**
     * the constructor
     *
     */
    private function __construct()
    {

    }

    /**
     * the singleton pattern
     *
     * @return HumanResources_Acl_Rights
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Acl_Rights;
        }

        return self::$_instance;
    }

    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {

        $allRights = parent::getAllApplicationRights();

        $addRights = array (
            self::MANAGE_PRIVATE,
            self::MANAGE_STREAMS,
            self::MANAGE_WORKINGTIME,
            self::MANAGE_EMPLOYEE
        );
        $allRights = array_merge($allRights, $addRights);

        return $allRights;
    }

    /**
     * get translated right descriptions
     *
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('HumanResources');

        $rightDescriptions = array(
            self::MANAGE_PRIVATE => array(
                'text'          => $translate->_('edit private employee data'),
                'description'   => $translate->_('Edit birthday, account data and other private information of employee records'),
            ),
            self::MANAGE_STREAMS => array(
                'text'          => $translate->_('edit stream data'),
                'description'   => $translate->_('Show and edit working streams'),
            ),
            self::MANAGE_WORKINGTIME => array(
                'text'          => $translate->_('edit working times'),
                'description'   => $translate->_('Show and edit working time reports'),
            ),
            self::MANAGE_EMPLOYEE => array(
                'text'          => $translate->_('edit employee data'),
                'description'   => $translate->_('Show and edit employee data and free time management'),
            )
        );

        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
