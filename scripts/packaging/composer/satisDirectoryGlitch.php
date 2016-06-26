<?php
/**
 * @package     HelperScripts
 * @subpackage  Composer
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

if ( $_SERVER['argc'] < 2)
    usage();

$dir = $_SERVER['argv'][1];
if ( !is_dir($dir) ) {
    echo $dir . ' is no directory' . PHP_EOL;
    usage();
}


iterateDir(new DirectoryIterator($dir));



function iterateDir(DirectoryIterator $iterator)
{
    foreach ($iterator as $dir) {
        if (!$dir->isDir() || strpos($dir->getFilename(), '.') === 0) continue;
        if ('src' === $dir->getFilename()) {
            $realName = basename($dir->getPath());
            $baseDir = dirname($dir->getPath());
            if ( !rename($dir->getPathname(), $baseDir.'/myTMP') ) break;
            exec('rm -rf '.$baseDir.'/'.$realName);
            rename($baseDir.'/myTMP', $baseDir.'/'.$realName);
            break;
        }
        iterateDir(new DirectoryIterator($dir->getPathname()));
    }
}


function usage()
{
    echo __FILE__ . ' path/to/vendordir' . PHP_EOL;
    exit(1);
}