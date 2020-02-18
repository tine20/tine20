<?php

function mexit($msg) {
    echo $msg . PHP_EOL;
    exit(1);
}

if (PHP_SAPI !== 'cli') {
    mexit('Not allowed: wrong sapi name!');
}

if (6 !== $argc) {
    mexit('expect 5 parameters: dbhost dbname dbuser dbpwd statusfile');
}

if (!is_file($argv[5])) {
    mexit($argv[5] . ' is not a file');
}

if (!is_writable($argv[5])) {
    mexit($argv[5] . ' is not writeable');
}

if (false === ($oldData = file_get_contents($argv[5]))) {
    mexit('file_get_contents failed');
}

$dsn = 'mysql:dbname=' . $argv[2] . ';host=' . $argv[1];
$user = $argv[3];
$password = $argv[4];

try {
    $pdo1 = new PDO($dsn, $user, $password);
    $status = $pdo1->query('SHOW ENGINE INNODB STATUS')->fetchAll();
} catch (PDOException $e) {
    mexit('pdo failed: ' . $e->getMessage());
}

if (!isset($status[0]['Status'])) mexit('SHOW ENGINE INNODB STATUS didnt work properly');

if (preg_match('/LATEST DETECTED DEADLOCK\s+-+\s+(.*?)-------/s', $status[0]['Status'], $m)) {

    $currentData = substr($m[1], 0, 19);
    if ($oldData !== $currentData) {
        file_put_contents($argv[5], $currentData);
        mexit($m[1]);
    }
}
