<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * select for update backend hook
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_SelectForUpdateHook extends Tinebase_Backend_Sql_SelectHook
{
    public function getKey(): string
    {
        return self::class;
    }

    public function manipulateSelect(Zend_Db_Select $select): void
    {
        $select->forUpdate(true);
    }
}
