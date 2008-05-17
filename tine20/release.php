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


$translations = array();

// collect translations
$d = dir($tine20path);
while (false !== ($appName = $d->read())) {
    $appPath = "$tine20path/$appName";
    if (is_dir($appPath) && $appName{0} != '.') {
        $translationPath = "$appPath/translations";
        if(is_dir($translationPath)) {
            $files = scandir($translationPath);
            foreach ($files as $file) {
                $filePath = "$translationPath/$file";
                if (is_file($filePath) && substr($file , -3) == '.po') {
                    list($locale) = explode('.', $file);
                    $poObject = po2jsObject($filePath);
                    $translations[$locale][] = getJs($locale, $appName, $poObject);
                }
            }
        }
    }
   
}
$d->close();

// dump one langfile for each locale
foreach ($translations as $locale => $domains) {
    $js = '';
    foreach ($domains as $domain) {
        $js = $js . $domain;
    }
    file_put_contents("$tine20path/Tinebase/js/$locale-debug.js", $js);
    system("java -jar $yuiCompressorPath -o $tine20path/Tinebase/js/$locale.js $tine20path/Tinebase/js/$locale-debug.js");
}

/**
 * returns key of translations object in Locale.Gettext
 *
 * @param string $locale
 * @param string $appName
 * @return string
 */
function getJs($locale, $appName, $poObject)
{
    return "Locale.Gettext.prototype._msgs['./$locale/LC_MESSAGES/$appName'] = new Locale.Gettext.PO($poObject);";
}

/**
 * convertes po file to js object
 *
 * @param string $filePath
 * @return string
 */
function po2jsObject($filePath)
{
    $po = file_get_contents($filePath);
    
    global $first, $plural;
    $first = true; 
    $plural = false;
    
    $po = preg_replace('/\r?\n/', "\n", $po);
    $po = preg_replace('/#.*\n/', '', $po);
    $po = preg_replace('/"(\s+)"/', '', $po);
    $po = preg_replace('/msgid "(.*?)"\nmsgid_plural "(.*?)"/', 'msgid "$1, $2"', $po);
    $po = preg_replace_callback('/msg(\S+) /', create_function('$matches','
        global $first, $plural;
        switch ($matches[1]) {
            case "id":
                if ($first) {
                    $first = false;
                    return "";
                }
                if ($plural) {
                    $plural = false;
                    return "]\n, ";
                }
                return ", ";
            case "str":
                return ": ";
            case "str[0]":
                $plural = true;
                return ": [\n  ";
            default:
                return " ,";
        }
    '), $po);
    $po = "({\n" . (string)$po . ($plural ? "]\n})" : "\n})");
    return $po;
    //$js = "Locale.Gettext.prototype._msgs['./de/LC_MESSAGES/Addressbook'] = new Locale.Gettext.PO($po);";
}

?>