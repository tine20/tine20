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
    const EDIT_PRIVATE = 'edit_private';

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
            self::EDIT_PRIVATE,
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
            self::EDIT_PRIVATE  => array(
                'text'          => $translate->_('edit private employee data'),
                'description'   => $translate->_('Edit birthday, account data and other private information of employee records'),
            )
        );

        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
