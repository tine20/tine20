<?php

$config = include('config.inc.php');
$config['logger']['filename'] = "php://stdout" ;

return $config;
