<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * interface to indicate node supports rename
 *
 * @package     Tinebase
 */
interface Tinebase_Frontend_WebDAV_IRenamable
{
    public function rename(string $newPath);
}
