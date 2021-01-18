<?php return array(
    'database' => array(
        'host'          => 'localhost',
        'dbname'        => 'tine20',
        'username'      => 'root',
        'password'      => 'root',
        'tableprefix'   => 'tine20_',
        'adapter'       => 'pdo_mysql',
    ),
   'login' => array(
       'username'      => 'github',
       'password'      => 'github'
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
