<?php

// usage:
// grep -e 'LogListener\:\:endTest\:' ...../path/file | php __FILE__

$lastStartTime = null;
$lastTest = null;
while ($line = fgets(STDIN)) {
    if (preg_match('#End test: (.*) / Time: ([\d\.]+)#', $line, $m)) {
        if (((float)$m[2]) > 10) {
            echo $m[1] . ': ' . $m[2] . PHP_EOL;
        }
    } else {
        echo 'bogus line: ' . $line . PHP_EOL;
    }
}

echo 'done' . PHP_EOL;