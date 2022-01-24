<?php
/**
 * class to handle grants
 * 
 * @package     HumanResources
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines Division grants
 * 
 * @package     HumanResources
 * @subpackage  Record
 *  */
class HumanResources_Model_DivisionGrants extends Tinebase_Model_Grants
{
    public const MODEL_NAME_PART    = 'DivisionGrants';

    /**
     * read only access to own: simplyfied employee, hr account, wtr, freetime
     */
    public const ACCESS_OWN_DATA = 'accessOwnData';

    /**
     * read only access to all employee / hr account
     */
    public const ACCESS_EMPLOYEE_DATA = 'accessEmployeeData';

    /**
     * write access to all employee / hr account
     */
    public const UPDATE_EMPLOYEE_DATA = 'updateEmployeeData';

    /**
     * read only access to all hr account / wtr / freetime
     */
    public const ACCESS_TIME_DATA = 'accessTimeData';

    /**
     * write access to all hr account / wtr / freetime
     */
    public const UPDATE_TIME_DATA = 'updateTimeData';

    /**
     * create change request for own user
     */
    public const CREATE_OWN_CHANGE_REQUEST = 'createOwnChangeRequest';

    /**
     * read only access to all change request
     */
    public const ACCESS_CHANGE_REQUEST = 'accessChangeRequest';

    /**
     * create change request for all user
     */
    public const CREATE_CHANGE_REQUEST = 'createChangeRequest';

    /**
     * write access to all change request
     */
    public const UPDATE_CHANGE_REQUEST = 'updateChangeRequest';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = HumanResources_Config::APP_NAME;
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return [
            self::ACCESS_OWN_DATA,
            self::ACCESS_EMPLOYEE_DATA,
            self::UPDATE_EMPLOYEE_DATA,
            self::ACCESS_TIME_DATA,
            self::UPDATE_TIME_DATA,
            self::CREATE_OWN_CHANGE_REQUEST,
            self::ACCESS_CHANGE_REQUEST,
            self::CREATE_CHANGE_REQUEST,
            self::UPDATE_CHANGE_REQUEST,
            self::GRANT_ADMIN,
        ];
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
