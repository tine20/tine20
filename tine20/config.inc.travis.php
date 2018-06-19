<?php return array(
    'database' => array(
        'host'          => 'localhost',
        'dbname'        => 'tine20',
        'username'      => 'root',
        'password'      => '',
        'tableprefix'   => 'tine20_',
        'adapter'       => 'pdo_mysql',
    ),
   'login' => array(
       'username'      => 'travis',
       'password'      => 'travis'
    ),
    'caching' => array (
        'active' => false,
        'path' => '/tmp',
        'lifetime' => 3600,
    ),
    'session' => array (
        'lifetime' => 86400,
        'backend' => 'File',
        'path' => '/tmp',
    ),
    'filesdir'  => '/tmp/',
);
