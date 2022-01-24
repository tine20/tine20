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
 * defines Calendar Event grants for personal containers only
 *
 * @package     Calendar
 * @subpackage  Model
 *
 */
class Calendar_Model_EventPersonalGrants extends Tinebase_Model_Grants
{
    /**
     * grant to _access_ records marked as private (GRANT_X = GRANT_X * GRANT_PRIVATE)
     */
    const GRANT_PRIVATE = 'privateGrant';
    /**
     * grant to see freebusy info in calendar app
     * @todo move to Calendar_Model_Grant once we are able to cope with app specific grant classes
     */
    const GRANT_FREEBUSY = 'freebusyGrant';

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
            self::GRANT_FREEBUSY,
            self::GRANT_PRIVATE,
        ]);
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
