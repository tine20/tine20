<?php declare(strict_types=1);
/**
 * Tinebase Twig Improved File Cache
 *
 * @package     Tinebase
 * @subpackage  Twig
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * Tinebase Twig Improved File Cache
 *
 * @package     Tinebase
 * @subpackage  Twig
 */
class Tinebase_Twig_ImprovedFileCache extends \Twig\Cache\FilesystemCache
{
    public function write($key, $content)
    {
        try {
            parent::write($key, $content);
        } catch (\RuntimeException $re) {
            if (strpos($re->getMessage(), 'Failed to write cache file') === 0) {
                clearstatcache(true, $key);
                if (is_file($key) && is_readable($key)) {
                    return;
                }
            }
            throw $re;
        }
    }
}
