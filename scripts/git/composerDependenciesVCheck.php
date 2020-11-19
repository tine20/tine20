<?php declare(strict_types=1);
/**
 * script to check that composer dependencies between two branches have the same version (if they should have)
 *
 * @package     Scripts
 * @subpackage  Git
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

if (!isset($argv[2])) {
    exit('usage: php ' . __FILE__ . ' branch1 branch2' . PHP_EOL);
}

$branchLow = $argv[1];//'origin/2019.11';
$branchHigh = $argv[2];//'origin/2020.11';
$descriptorspec = [
    0 => ['pipe', 'r'], // stdin
    1 => ['pipe', 'w'], // stdout
    2 => ['pipe', 'r'], // stderr
];

$process = proc_open('git show ' . $branchLow . ':../tine20/composer.json | cat', $descriptorspec, $pipes);
if (!is_resource($process)) {
    exit('could not proc_open' . PHP_EOL);
}
$stdIn = stream_get_contents($pipes[1]);
if ($stdErr = stream_get_contents($pipes[2])) {
    exit('stdErr: ' . $stdErr . PHP_EOL);
}
fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
proc_close($process);
$compJsonLow = json_decode($stdIn, true);


$process = proc_open('git show ' . $branchHigh . ':../tine20/composer.json | cat', $descriptorspec, $pipes);
if (!is_resource($process)) {
    exit('could not proc_open' . PHP_EOL);
}
$stdIn = stream_get_contents($pipes[1]);
if ($stdErr = stream_get_contents($pipes[2])) {
    exit('stdErr: ' . $stdErr . PHP_EOL);
}
fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
proc_close($process);
$compJsonHigh = json_decode($stdIn, true);


$process = proc_open('git show ' . $branchLow . ':../tine20/composer.lock | cat', $descriptorspec, $pipes);
if (!is_resource($process)) {
    exit('could not proc_open' . PHP_EOL);
}
$stdIn = stream_get_contents($pipes[1]);
if ($stdErr = stream_get_contents($pipes[2])) {
    exit('stdErr: ' . $stdErr . PHP_EOL);
}
fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
proc_close($process);
$compLockLow = json_decode($stdIn, true);


$process = proc_open('git show ' . $branchHigh . ':../tine20/composer.lock | cat', $descriptorspec, $pipes);
if (!is_resource($process)) {
    exit('could not proc_open' . PHP_EOL);
}
$stdIn = stream_get_contents($pipes[1]);
if ($stdErr = stream_get_contents($pipes[2])) {
    exit('stdErr: ' . $stdErr . PHP_EOL);
}
fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
proc_close($process);
$compLockHigh = json_decode($stdIn, true);



$requireLow = array_filter($compJsonLow['require'], function($val, $key) {
    return stripos($key, 'php') !== 0 && stripos($key, 'ext-') !== 0;
}, ARRAY_FILTER_USE_BOTH);
$requireHigh = array_filter($compJsonHigh['require'], function($val, $key) {
    return stripos($key, 'php') !== 0 && stripos($key, 'ext-') !== 0;
}, ARRAY_FILTER_USE_BOTH);
$requireOfInterest = array_filter($requireHigh, function($val, $key) use($requireLow) {
    return isset($requireLow[$key]) && $val === $requireLow[$key];
}, ARRAY_FILTER_USE_BOTH);

$packagesLow = [];
foreach ($compLockLow['packages'] as $package) {
    if (isset($requireOfInterest[$package['name']])) {
        $packagesLow[$package['name']] = $package;
    }
}
$packagesHigh = [];
foreach ($compLockHigh['packages'] as $package) {
    if (isset($requireOfInterest[$package['name']])) {
        $packagesHigh[$package['name']] = $package;
    }
}
foreach (array_keys($requireOfInterest) as $packageName) {
    if (!isset($packagesLow[$packageName])) {
        echo $packageName . ' not found in lower branch' . PHP_EOL;
        continue;
    }
    $lowPack = $packagesLow[$packageName];
    if (!isset($packagesHigh[$packageName])) {
        echo $packageName . ' not found in higher branch' . PHP_EOL;
        continue;
    }
    $highPack = $packagesHigh[$packageName];
    if (!isset($lowPack['source']) && isset($highPack['source'])) {
        echo $packageName . ' has source in high branch, but not in low' . PHP_EOL;
        continue;
    }
    if (isset($lowPack['source']) && !isset($highPack['source'])) {
        echo $packageName . ' has source in low branch, but not in high' . PHP_EOL;
        continue;
    }
    if (isset($lowPack['source']) && $lowPack['source']['reference'] !== $highPack['source']['reference']) {
        echo $packageName . ' source reference difference found' . PHP_EOL;
        continue;
    }
    if (!isset($lowPack['dist']) && isset($highPack['dist'])) {
        echo $packageName . ' has dist in high branch, but not in low' . PHP_EOL;
        continue;
    }
    if (isset($lowPack['dist']) && !isset($highPack['dist'])) {
        echo $packageName . ' has dist in low branch, but not in high' . PHP_EOL;
        continue;
    }
    if (isset($lowPack['dist']) && $lowPack['dist']['reference'] !== $highPack['dist']['reference']) {
        echo $packageName . ' dist reference difference found' . PHP_EOL;
        continue;
    }
}
