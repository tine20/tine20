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
 * abstract getSelect() backend hook
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
abstract class Tinebase_Backend_Sql_SelectHook
{
    abstract public function getKey(): string;
    abstract public function manipulateSelect(Zend_Db_Select $select): void;

    public static function getRAII(Tinebase_Backend_Sql_Abstract $backend): Tinebase_RAII
    {
        $instance = new static();
        $backend->addSelectHook($instance);
        return new Tinebase_RAII(function() use($backend, $instance) {
            $backend->removeSelectHook($instance);
        });
    }
}
