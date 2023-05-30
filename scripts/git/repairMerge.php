<?php
/**
 * @package     buildscripts
 * @subpackage  githelpers
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * script to repair failed merges
 * it will only fix composer.lock fails, any other fail needs to be fixed manually
 */

// check that only tine20/composer.lock is in conflict, no other files
exec('git diff --name-only --diff-filter=U', $out, $ret);
if (0 !== $ret) {
    echo 'failed to execute "git diff --name-only --diff-filter=U" ' . print_r($out, true) . ' ' . $ret . PHP_EOL;
    exit(1);
}
if (1 !== count($out) || 'tine20/composer.lock' !== $out[0]) {
    echo 'this script only fixes fails in tine20/composer.lock' . PHP_EOL;
    exit(1);
} unset($out);


// checkout our version on tine20/composer.lock
exec('git checkout HEAD -- composer.lock', $out, $ret);
if (0 !== $ret) {
    echo 'failed to execute "git checkout HEAD -- composer.lock" ' . print_r($out, true) . ' ' . $ret . PHP_EOL;
    exit(1);
} unset($out);


// temp commit
exec('git commit -m "Merge branch \'' . $argv[1] . '\' into ' . $argv[2] . '"', $out, $ret);
if (0 !== $ret) {
    echo 'failed to execute "git commit -m "Merge branch \'' . $argv[1] . '\' into ' . $argv[2] . '"" ' .
        print_r($out, true) . ' ' . $ret . PHP_EOL;
    exit(1);
} unset($out);


// find out commit id of last merge
exec('git log --grep="Merge branch \'' . $argv[1] . '\' into ' . $argv[2] . '" -2 --pretty=format:"%H"', $out, $ret);
if (0 !== $ret) {
    echo 'failed to execute "git log --grep="Merge branch \'' . $argv[1] . '\' into ' . $argv[2] .
        '" -2 --pretty=format:"%H"" ' . print_r($out, true) . ' ' . $ret . PHP_EOL;
    exit(1);
}
if (2 !== count($out)) {
    echo 'could not find commit id of last merge' . PHP_EOL;
    exit(1);
}
$commitId = $out[1];
unset($out);


// get composer commands from commit message since last merge
exec('git --no-pager log ' . $commitId . '..HEAD --grep="execute composer:" --pretty=format:"%B"', $out, $ret);
if (0 !== $ret) {
    echo 'failed to execute "git --no-pager log ' . $commitId .
        '..HEAD --grep="execute composer:" --pretty=format:"%B" 2>1"' . print_r($out, true) . ' ' . $ret . PHP_EOL;
    exit(1);
}
$composerCmds = [];
$currentCmds = [];
$hookFound = false;
$updates = [];
foreach ($out as $line) {
    if (strpos($line, 'execute composer:') === 0) {
        $hookFound = true;
        if (!empty($currentCmds)) {
            $composerCmds[] = $currentCmds;
            $currentCmds = [];
        }
    } elseif ($hookFound) {
        if (strpos($line, 'composer ') === 0) {
            if (preg_match('/^composer\s+update\s(.*)$/', $line, $m)) {
                foreach (explode(' ', $m[1]) as $val) {
                    if (empty($val)) continue;
                    if (strpos($val, '-') === 0) continue;
                    $updates[] = $val;
                }
            } else {
                $currentCmds[] = $line;
            }
        } else {
            $hookFound = false;
        }
    }
}
if (!empty($currentCmds)) {
    $composerCmds[] = $currentCmds;
}
unset($out);
$composerIgnores = '--ignore-platform-req=ext-sockets --ignore-platform-req=ext-simplexml --ignore-platform-req=ext-bcmath --ignore-platform-req=ext-dom --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl --ignore-platform-req=ext-xml --ignore-platform-req=ext-xml --ignore-platform-req=ext-zip --ignore-platform-req=ext-curl --ignore-platform-req=ext-xmlwriter --ignore-platform-req=ext-xmlreader --ignore-platform-req=ext-mbstring';

if (!empty($updates)) {
    $composerCmds[] = ['composer update ' . $composerIgnores . ' ' . join(' ', $updates)];
}

foreach (array_reverse($composerCmds) as $cmds) {
    foreach ($cmds as $cmd) {
        if (preg_match('/^(composer\s+\S+)(.*?)$/', $cmd, $m)) {
            $cmd = $m[1] . ' ' . $composerIgnores . ($m[2] ?? '');
        }
        // found a composer command to execute
        // conny, 18.01.2019 im tine chat: injections? die community injected ja auch code :-) - wie fügen members bei GH zu bzw. reviewn merge reqeusts - für mich reicht das
        echo $cmd . PHP_EOL;
        exec($cmd, $out2, $ret);
        if (0 !== $ret) {
            echo 'failed to execute "' . $cmd . '"' . print_r($out2, true) . ' ' . $ret . PHP_EOL;
            exit(1);
        } unset($out2);
    }
}

// update composer.lock file
if (empty($composerCmds)) {
    $cmd = 'composer update ' . $composerIgnores . ' nothing';
    echo $cmd . PHP_EOL;
    exec($cmd, $out, $ret);
    if (0 !== $ret) {
        echo 'failed to execute "composer update nothing" ' . print_r($out, true) . ' ' . $ret . PHP_EOL;
        exit(1);
    }
    unset($out);
}

// add composer.lock
exec('git add composer.lock', $out, $ret);
if (0 !== $ret) {
    echo 'failed to execute "git add composer.lock" ' . print_r($out, true) . ' ' . $ret . PHP_EOL;
    exit(1);
} unset($out);


// commit fixed merge
exec('git commit --amend --no-edit', $out, $ret);
if (0 !== $ret) {
    echo 'failed to execute "git commit --amend --no-edit' . print_r($out, true) . ' ' . $ret . PHP_EOL;
    exit(1);
} unset($out);


echo 'successfully repaired tine20/composer.lock conflict' . PHP_EOL;
exit(0);