<?php return array(
    'database' => array(
        'host'          => 'localhost',
        'dbname'        => 'tine20',
        'username'      => 'root',
        'password'      => '',
        'tableprefix'   => 'tine20_',
        'adapter'       => 'pdo_mysql',
    ),
    'setupuser' => array(
        'username'      => 'vagrant',
        'password'      => 'vagrant'
    ),
   'login' => array(
       'username'      => 'vagrant',
       'password'      => 'vagrant'
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

/*    'logger' => array (
        'active' => true,
        'filename' => '/var/log/tine20/tine20.log',
        'priority' => '7',
    ),*/
    'filesdir'  => '/tmp/',
    // 'tmpdir' => '/tmp',
);