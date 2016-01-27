<?php
/**
 * @package     HelperScripts
 * @subpackage  Composer
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

if ( $_SERVER['argc'] < 2) {
    usage();
}

$file = $_SERVER['argv'][1];
if ( !is_file($file) ) {
    echo $file . ' is no file' . PHP_EOL;
    usage();
}

print_r($_SERVER['argv']);

// second param may contain satis ip address / hostname
$host = (isset($_SERVER['argv'][2])) ? $_SERVER['argv'][2] : '79.99.84.34';

$file1 = dirname($file) . '/composer.json';
if ( !is_file($file1) ) {
    echo $file1 . ' is no file' . PHP_EOL;
    usage();
}

$filecontents = file_get_contents($file1);
if (false === $filecontents) {
    echo 'could not get file content of ' . $file1 . PHP_EOL;
    usage();
}

$jsonComposer = json_decode($filecontents);
if (null === $jsonComposer) {
    echo 'failed to json decode file ' . $file1 . PHP_EOL;
    exit(2);
}

$var = (object)array('type' => 'composer', 'url' => 'http://' . $host);
$var1 = (object)array('packagist' => false);

$jsonComposer->repositories = array($var, $var1);
// we need to replace "_empty_" with the empty string as this breaks autoloading
$jsonEncodedComposerJson = str_replace('_empty_', '', json_encode($jsonComposer, JSON_PRETTY_PRINT));
file_put_contents($file1, $jsonEncodedComposerJson);
unset($jsonComposer);
unset($file1);


$filecontents = file_get_contents($file);
if (false === $filecontents) {
    echo 'could not get file content of ' . $file . PHP_EOL;
    usage();
}


$jsonComposerLock = json_decode($filecontents);
if (null === $jsonComposerLock) {
    echo 'failed to json decode file ' . $file . PHP_EOL;
    exit(2);
}


$filecontents = file_get_contents('http://' . $host . '/packages.json');
if (false === $filecontents) {
    echo 'could not get remote content of http://' . $host . '/packages.json' . PHP_EOL;
    exit(3);
}


$jsonSatis = json_decode($filecontents, true);
if (null === $jsonSatis) {
    echo 'failed to json decode remote content of http://' . $host . '/packages.json' . PHP_EOL;
    exit(4);
}


if (isset($jsonSatis['includes']) && is_array($jsonSatis['includes'])) {
    $tmp = '';
    $tmp1 = $jsonSatis;
    foreach ($jsonSatis['includes'] as $key => $val) {
        $filecontents = file_get_contents('http://' . $host . '/' . $key);
        if (false === $filecontents) {
            echo 'could not get remote content of http://' . $host . '/' . $key . PHP_EOL;
            exit(5);
        }
        $tmp = json_decode($filecontents, true);
        if (null === $tmp) {
            echo 'failed to json decode remote content of http://' . $host . '/' . $key . PHP_EOL;
            exit(6);
        }
        $tmp1 = array_merge_recursive($tmp1, $tmp);
    }
    unset($tmp);
    $jsonSatis = $tmp1;
    unset($tmp1);
}
unset($filecontents);

loopArray($jsonComposerLock->packages);
loopArray($jsonComposerLock->{'packages-dev'});

function loopArray(array &$a)
{
    global $jsonSatis;
    foreach ($a as $package) {
        if (!isset($jsonSatis['packages'][$package->name])) {
            echo 'could not find package ' . $package->name . PHP_EOL;
            continue;
        }
        if (!isset($jsonSatis['packages'][$package->name][$package->version])) {
            echo 'could not find version ' . $package->version . '  for package ' . $package->name . PHP_EOL;
            continue;
        }
        echo 'found version ' . $package->version . '  for package ' . $package->name . PHP_EOL;

        $package->dist->type = $jsonSatis['packages'][$package->name][$package->version]['dist']['type'];
        $package->dist->url = $jsonSatis['packages'][$package->name][$package->version]['dist']['url'];
        $package->dist->reference = $jsonSatis['packages'][$package->name][$package->version]['dist']['reference'];
        $package->dist->shasum = $jsonSatis['packages'][$package->name][$package->version]['dist']['shasum'];
    }
}

file_put_contents($file, json_encode($jsonComposerLock, JSON_PRETTY_PRINT));


function usage()
{
    echo __FILE__ . ' path/to/composer.lock [172.123.13.171]' . PHP_EOL;
    exit(1);
}