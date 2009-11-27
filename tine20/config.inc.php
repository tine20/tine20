<?php
return array (
  'database' => 
  array (
    'host' => 'localhost',
    #'dbname' => 'sirona',
    'dbname' => 'tine20',
    #'dbname' => 'tinetest',
    'username' => 'root',
    'password' => 'Thoocoo8',
    'adapter' => 'pdo_mysql',
    #'tableprefix' => 'sironanew_',
    'tableprefix' => 'tine20_'
  ),
  'login' => 
  array (
    'username' => 'tine20admin',
    #'username' => 'lkneschke',
    #'password' => 'lars,
  ),
  #'pagetitlepostfix' => ' - sirona',
  'pagetitlepostfix' => ' - tine20',
  'logger' => 
  array (
    'active' => true,
    'filename' => '/var/log/tine20/tine20.log',
    #'priority' => '7',
    'priority' => '6',
    #'priority' => '5',
  ),
  'setupuser' => 
  array (
    'username' => 'admin',
    'password' => '21232f297a57a5a743894a0e4a801fc3',
  ),
  'caching' => 
  array (
    'active' => true,
    'lifetime' => 3600,
    'backend' => 'File',
    'path' => '/tmp',
  ),
  'imap' => 
  array (
    'host' => 'mail.metaways.net',
    'user' => 'unittest@tine20.org',
    'password' => 'picola124',
    'port' => 143,
    'useAsDefault' => true,
    'name' => 'default',
    'secure_connection' => 'tls',
    'dbmail' => 
    array (
      'host' => 'localhost',
      'dbname' => 'dbmail',
      'username' => 'root',
      'password' => 'Thoocoo8',
    ),
  ),
  'smtp' => 
  array (
    'hostname' => 'mail.metaways.net',
    'username' => 'unittest@tine20.org',
    'password' => 'picola124',
    'ssl' => 'tls',
    'port' => 25,
    'auth' => 'login',
    'domain' => 'tine20.org',
    'postfix' => 
    array (
      'host' => 'localhost',
      'dbname' => 'postfix',
      'username' => 'root',
      'password' => 'Thoocoo8',
    ),
  ),
  'courses' => 
  array (
    'course_types' => 
    array (
      'id' => 1,
      'name' => 'xyz',
    ),
  ),
);
