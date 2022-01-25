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
    public const MANAGE_DIVISIONS = 'manage_divisions';

    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        return array_merge(parent::getAllApplicationRights(), [
            self::MANAGE_DIVISIONS,
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
            self::MANAGE_DIVISIONS => array(
                'text'          => $translate->_('edit division data'),
                'description'   => $translate->_('Add, edit and delete division data'),
            ),
            self::MANAGE_EMPLOYEE => array(
                'text'          => $translate->_('edit employee data'),
                'description'   => $translate->_('Show and edit employee data and free time management'),
            ),
            self::MANAGE_STREAMS => array(
                'text'          => $translate->_('edit stream data'),
                'description'   => $translate->_('Show and edit working streams'),
            ),
            self::MANAGE_WORKINGTIME => array(
                'text'          => $translate->_('edit working times'),
                'description'   => $translate->_('Show and edit working time reports'),
            ),
        );

        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
