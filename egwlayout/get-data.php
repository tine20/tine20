<?php

//print_r($_POST);
$totalCount = 165;
$start = (int)$_REQUEST['start'];
$limit = ((int)$_REQUEST['limit'] ? (int)$_REQUEST['limit'] : 50);
$addressbook = ($_REQUEST['addressbook'] ? $_REQUEST['addressbook'] : 'personal');

$result = array('totalcount' => $totalCount);

for($i=$start; $i<($start+$limit) && $i <= $totalCount; $i++) {
	$result['results'][] = array (
		'userid' => $i,
		'lastname' => 'lastname '.$i,
		'firstname' => 'firstname '.$i,
		'street' => 'street '.$i,
		'zip' => '01234',
		'city' => 'havanna',
		'birthday' => '13.08.1926',
		'addressbook' => $addressbook
	);
}
echo json_encode($result);

?>