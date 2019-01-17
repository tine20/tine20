<?php
/**
 * script to check gits last modified date vs copy right note as in line 8 below
 *
 * @package     Scripts
 * @subpackage  Git
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

$descriptorspec = [
    0 => ['pipe', 'r'], // stdin
    1 => ['pipe', 'w'], // stdout
    2 => ['pipe', 'r'], // stderr
];


$process = proc_open("git ls-tree -r --name-only HEAD | grep '\\.php' | grep -v '/vendor/' | grep -v '/library/'",
    $descriptorspec, $pipes);

if (!is_resource($process)) {
    exit('could not proc_open' . PHP_EOL);
}

$files = [];
while ($name = trim(fgets($pipes[1]))) {
    $files[] = $name;
}

if ($stdErr = stream_get_contents($pipes[2])) {
    exit($stdErr . PHP_EOL);
}

fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
proc_close($process);

$currentYear = date('Y');

foreach($files as $file) {
    exec('git log -1 --format="%ai" -- ' . $file, $out, $retVal);
    if (0 !== $retVal) {
        exit('git log did not succeed "git log -1 --format="%ai" -- ' . $file . '"');
    }
    $year = substr($out[0], 0, 4);
    unset($out);
    if ($year !== $currentYear) continue;

    $fullFilePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $file;
    if (!($fh = fopen($fullFilePath, 'r'))) {
        exit('could not open file ' . $fullFilePath);
    }

    $head = fread($fh, 1024);
    if (!preg_match('/Copyright\s+\(c\)\s+(\d\d\d\d)?(\s*-)?(\s*)(\d\d\d\d)/', $head, $match)) {
        echo $file . ' has no copyright in head' . PHP_EOL;
        continue;
    }

    if ($year === $match[4]) {
        continue;
    }

    $content = file_get_contents($fullFilePath);
    $content = str_replace($match[0], 'Copyright (c) ' . ($match[1]?$match[1]:$match[4]) . '-' . $year, $content);
    file_put_contents($fullFilePath, $content);
    unset($content);
}
