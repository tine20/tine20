<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * this class handles the rights for the hr application
 * @package     Tinebase
 * @subpackage  Acl
 */
class HumanResources_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the right to show and edit working streams
     * @static string
     */
    public const MANAGE_STREAMS = 'manage_streams';

    /**
     * the right to show and edit working times
     * @static string
     */
    public const MANAGE_WORKINGTIME = 'manage_workingtime';

    /**
     * the right to show and edit employee and free time data
     * @static string
     */
    public const MANAGE_EMPLOYEE = 'manage_employee';

    /**
     * the right to add, edit and delete division data
     * @static string
     */
    public const ADD_DIVISIONS = 'add_divisions';

    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        return array_merge(parent::getAllApplicationRights(), [
            self::ADD_DIVISIONS,
            self::MANAGE_EMPLOYEE,
            self::MANAGE_STREAMS,
            self::MANAGE_WORKINGTIME,
        ]);
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
            self::ADD_DIVISIONS => array(
                'text'          => $translate->_('Add Divisions'),
                'description'   => $translate->_('Add new divisions'),
            ),
            self::MANAGE_EMPLOYEE => array(
                'text'          => $translate->_('Manage all Employee'),
                'description'   => $translate->_('Manage all employee regardless configured division grants.'),
            ),
            self::MANAGE_STREAMS => array(
                'text'          => $translate->_('Manage Streams'),
                'description'   => $translate->_('Show and edit working streams'),
            ),
            self::MANAGE_WORKINGTIME => array(
                'text'          => $translate->_('Manage working times'),
                'description'   => $translate->_('Manage all working times regardless configured division grants.'),
            ),
        );

        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
