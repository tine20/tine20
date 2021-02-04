<?php

$mimes = [];
foreach(explode("\n", file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types')) as $line) {
    if (strlen($line) === 0 || $line[0] === '#') continue;
    $data = array_values(array_filter(explode("\t", $line)));
    if (strlen($data[0]) === 0 || !isset($data[1])) continue;
    foreach (array_filter(explode(' ', $data[1])) as $extension)
        $mimes[$extension] = $data[0];
}
echo '<' . '?php
return [
';
foreach ($mimes as $key => $value) echo '\'' . $key . '\' => \'' . $value . '\',
';
echo '];';
?>
