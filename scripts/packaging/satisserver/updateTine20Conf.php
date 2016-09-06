<?php

/**
 * script to update the tine20.conf file
 *
 * * git fetch [both remotes]
 * * git branch -a [show all branches]
 * * iterate over all branches newer or equal to 2015.11
 * * get tine20/composer.json from each branch
 * * merge data and write it to tine20.conf
 */


$path = '/home/ubuntu/tine20Repos';
$remotes = array('tine20org', 'tine20com');

foreach($remotes as $r) {
    $fetchResult = myProcOpen('git fetch ' . $r, $path);
    if ($fetchResult[0] !== 0) {
        exit('git fetch failed for ' . $r .': ' . print_r($fetchResult, true));
    }
}
unset($fetchResult);

$branchResult = myProcOpen('git branch -a', $path);
if ($branchResult[0] !== 0) {
    exit('git branch -a failed: ' . print_r($branchResult, true));
}

$branches = explode(PHP_EOL, $branchResult[1]);
unset($branchResult);

$repositories = array();
$require = array();

foreach($branches as $branch) {
    if (preg_match('/(\d\d\d\d\.\d\d)/', $branch, $match) && version_compare($match[1], '2015.11') >= 0) {

        $gitShowResult = myProcOpen('git show ' . $branch . ':tine20/composer.json', $path);
        if ($gitShowResult[0] !== 0) {
            exit('git show failed for branch: ' . $branch . PHP_EOL . print_r($gitShowResult, true));
        }

        if (($composerJson = json_decode($gitShowResult[1], true)) === NULL || !is_array($composerJson)) {
            exit('could not json_decode composer.json from branch: ' . $branch . PHP_EOL . $gitShowResult[1]);
        }

        foreach($composerJson['repositories'] as $repo) {
            if (!isset($repositories[$repo['type']]))
                $repositories[$repo['type']] = array();
            $repositories[$repo['type']][$repo['url']] = true;
        }

        foreach($composerJson['require'] as $req => $version) {
            if (!isset($require[$req]) || version_compare($version, $require[$req]) > 0) {
                $require[$req] = $version;
            }
        }

        foreach($composerJson['require-dev'] as $req => $version) {
            if (!isset($require[$req]) || version_compare($version, $require[$req]) > 0) {
                $require[$req] = $version;
            }
        }
    }
}

$repositories['composer'] = array('https://packagist.org' => true);


$result = '{
        "name": "Tine20 Composer Mirror",
        "homepage": "http://79.99.84.34",

        "archive": {
                "directory": "dist1",
                "format": "zip"
        },

        "repositories": [' . PHP_EOL;

$first = true;
foreach($repositories as $type => $data) {
    foreach($data as $url => $foo) {
        $url = str_replace('http://gerrit.tine20.com', 'https://gerrit.tine20.com', $url);
        if (!$first) {
            $result .= ',';
        } else {
            $first = false;
        }

        $result .= '        {
            "type": "' . $type . '",
            "url": "' . $url . '"
        }';
    }
}

$result .= '],
        "require": {' . PHP_EOL;

$first = true;
foreach($require as $name => $version) {
    if (!$first) {
        $result .= ',' . PHP_EOL;
    } else {
        $first = false;
    }

    $result .= '            "' . $name .'": "' . $version .'"';
}

$result .= PHP_EOL . '      },' . PHP_EOL . '       "require-dependencies": true' . PHP_EOL . '}';

echo $result;



function myProcOpen($cmd, $path)
{
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $process = proc_open($cmd, $descriptorspec, $pipes, $path);

    $result = array(
        0 => false,
        1 => '',
        2 => ''
    );

    if (is_resource($process)) {
        fclose($pipes[0]);

        $result[1] = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $result[2] = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $result[0] = proc_close($process);
    }

    return $result;
}