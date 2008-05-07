#!/usr/bin/env php
<?php

if (isset($_SERVER['HTTP_HOST'])) {
    die('not allowed!');
}

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

/**
 * path to tine 2.0 checkout
 */
$tine20path = dirname(__FILE__);

/**
 * path to yui compressor
 */
$yuiCompressorPath = dirname(__FILE__) . '/../yuicompressor-2.3.4/build/yuicompressor-2.3.4.jar';



$includeFiles = Tinebase_Http::getAllIncludeFiles();

$cssDebug = fopen($tine20path . '/Tinebase/css/tine-all-debug.css', 'w+');
foreach ($includeFiles['css'] as $file) {
    list($filename) = explode('?', $file);
    if (file_exists("$tine20path/$filename")) {
        fwrite($cssDebug, file_get_contents("$tine20path/$filename") . "\n");
    }
}
fclose($cssDebug);
system("java -jar $yuiCompressorPath -o $tine20path/Tinebase/css/tine-all.css $tine20path/Tinebase/css/tine-all-debug.css");

$jsDebug = fopen($tine20path . '/Tinebase/js/tine-all-debug.js', 'w+');
foreach ($includeFiles['js'] as $file) {
    list($filename) = explode('?', $file);
    if (file_exists("$tine20path/$filename")) {
        fwrite($jsDebug, file_get_contents("$tine20path/$filename") . "\n");
    }
}
fclose($jsDebug);
system("java -jar $yuiCompressorPath -o $tine20path/Tinebase/js/tine-all.js $tine20path/Tinebase/js/tine-all-debug.js");

?>