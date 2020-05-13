<?php
/**
 * class to handle grants
 *
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines Addressbook Event grants for personal containers only
 *
 * @package     Addressbook
 * @subpackage  Model
 *
 */
class Addressbook_Model_ContactGrants extends Tinebase_Model_Grants
{
    /**
     * grant to _access_ records marked as private (GRANT_X = GRANT_X * GRANT_PRIVATE)
     */
    const GRANT_PRIVATE_DATA = 'privateDataGrant';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Addressbook';

    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return array_merge(parent::getAllGrants(), [
            self::GRANT_PRIVATE_DATA,
        ]);
    }
}