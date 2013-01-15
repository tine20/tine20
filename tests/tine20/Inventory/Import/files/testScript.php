#!/usr/bin/php
<?php
$data = json_decode($argv[1], true);

if ($data['inventory_id'] == "") {
    $data['inventory_id'] = 12345;
}

print json_encode($data);