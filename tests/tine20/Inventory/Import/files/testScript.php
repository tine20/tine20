#!/usr/bin/php
<?php
$data = json_decode($argv[1], true);

if ($data['inventory_id'] == "") {
    $data['inventory_id'] = 12345;
    
    $secondMappingSet = array(
        'name' => "Tine 2.0 für Tolle Leute - second mapping set",
        'added_date' => "2012-01-11",
        'inventory_id' => "1333431646"
    );
    $data = array($data, $secondMappingSet);
}

print json_encode($data);