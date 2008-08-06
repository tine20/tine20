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

/**
 * options
 */
try {
    $opts = new Zend_Console_Getopt(
    array(
        'verbose|v'       => 'Output messages',
        'clean|c'         => 'Cleanup all build files',
        'translations|t'  => 'Build tranlations',
        'js|j'            => 'Build Java Script',
        'css|s'           => 'Build CSS Files',
        'all|a'           => 'Build all (default)',
        'zend|z'          => 'Build Zend Translation Lists',
        'pot'             => 'Build xgettext po template files',
        'help'            => 'Display this help Message',
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
   echo $e->getUsageMessage();
   exit;
}

if ($opts->help || !($opts->a || $opts->c || $opts->t || $opts->j || $opts->s || $opts->z || $opts->pot)) {
    echo $opts->getUsageMessage();
    exit;
}

/**
 * --clean 
 */
if ($opts->clean) {
    if ($opts->v) {
        echo "Cleaning old build...\n";
    }
    $files = array(
        'Tinebase/js/tine-all-debug.js',
        'Tinebase/js/tine-all.js',
        'Tinebase/css/tine-all-debug.css',
        'Tinebase/css/tine-all.css',
    );
    foreach ($files as $file) {
        if (file_exists("$tine20path/$file")) {
            if ($opts->v) echo "    removing file $tine20path/$file \n";
            unlink("$tine20path/$file");
        }
    }
    
    $buildDir = "$tine20path/Tinebase/js/Locale/build";
    if (is_dir($buildDir)) {
        $buildfiles = scandir($buildDir);
        foreach ($buildfiles as $file) {
            if (is_file("$buildDir/$file")) {
                if ($opts->v) echo "    removing file $buildDir/$file \n";
                unlink("$buildDir/$file");
            }
        }
    }
}


$includeFiles = Tinebase_Http::getAllIncludeFiles(array(
    'Asterisk',
    'Felamimail',
    'Calendar'
));

if ($opts->a || $opts->s) {
    $cssDebug = fopen($tine20path . '/Tinebase/css/tine-all-debug.css', 'w+');
    foreach ($includeFiles['css'] as $file) {
        list($filename) = explode('?', $file);
        if (file_exists("$tine20path/$filename")) {
            $cssContent = file_get_contents("$tine20path/$filename");
            $cssContent = preg_replace('/(\.\.\/){3,}images/i', '../../images', $cssContent);
            fwrite($cssDebug, $cssContent . "\n");
        }
    }
    fclose($cssDebug);
    system("java -jar $yuiCompressorPath --charset utf-8 -o $tine20path/Tinebase/css/tine-all.css $tine20path/Tinebase/css/tine-all-debug.css");
}

if ($opts->a || $opts->j) {
    $jsDebug = fopen($tine20path . '/Tinebase/js/tine-all-debug.js', 'w+');
    foreach ($includeFiles['js'] as $file) {
        list($filename) = explode('?', $file);
        if (file_exists("$tine20path/$filename")) {
            fwrite($jsDebug, '// file: ' . "$tine20path/$filename" . "\n");
            fwrite($jsDebug, file_get_contents("$tine20path/$filename") . "\n");
        }
    }
    fclose($jsDebug);
    system("java -jar $yuiCompressorPath --charset utf-8 -o $tine20path/Tinebase/js/tine-all.js $tine20path/Tinebase/js/tine-all-debug.js");
}

if ($opts->a || $opts->t) {
    $translations = array();
    
    // collect translations
    $d = dir($tine20path);
    while (false !== ($appName = $d->read())) {
        $appPath = "$tine20path/$appName";
        if (is_dir($appPath) && $appName{0} != '.') {
            $translationPath = "$appPath/translations";
            if (is_dir($translationPath)) {
                $files = scandir($translationPath);
                foreach ($files as $file) {
                    $filePath = "$translationPath/$file";
                    if (is_file($filePath) && substr($file , -3) == '.po') {
                        list($locale) = explode('.', $file);
                        $poObject = Tinebase_Translation::po2jsObject($filePath);
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
        file_put_contents("$tine20path/Tinebase/js/Locale/build/$locale-debug.js", $js);
        if ( $opts->v ) {
            echo "compressing file $locale.js\n";
        }
        system("java -jar $yuiCompressorPath --charset utf-8 -o $tine20path/Tinebase/js/Locale/build/$locale.js $tine20path/Tinebase/js/Locale/build/$locale-debug.js");
    }    
}

// build zend translation lists only on demand
if ( $opts->z ) {
    // dump one langfile for every locale
    $localelist = Zend_Locale::getLocaleList();
    //$localelist = array ( "en_US" => 1 ); 
    foreach ($localelist as $locale => $something) {        
        $js = Tinebase_Translation::createJsTranslationLists($locale);
        file_put_contents("$tine20path/Tinebase/js/Locale/static/generic-$locale-debug.js", $js);
        if ( $opts->v ) {
            echo "compressing file generic-$locale.js\n";
        }
        system("java -jar $yuiCompressorPath --charset utf-8 -o $tine20path/Tinebase/js/Locale/static/generic-$locale.js $tine20path/Tinebase/js/Locale/static/generic-$locale-debug.js");
    }
}

if($opts->pot) {
    $d = dir($tine20path);
    while (false !== ($appName = $d->read())) {
        $appPath = "$tine20path/$appName";
        if (is_dir($appPath) && $appName{0} != '.') {
            $translationPath = "$appPath/translations";
            if (is_dir($translationPath)) {
                // generate new pot template
                if ( $opts->v ) {
                    echo "Creating $appName template \n";
                }
                `cd $appPath 
                touch translations/template.pot 
                find . -type f -iname "*.php" -or -type f -iname "*.js"  | xgettext --force-po --omit-header -o translations/template.pot -L Python --from-code=utf-8 -k=_ -f - 2> /dev/null`;
                
                // merge template into translation po files
                foreach (scandir($translationPath) as $poFile) {
                    if (substr($poFile, -3) == '.po') {
                        $output = '2> /dev/null';
                        if ( $opts->v ) {
                	       echo $poFile . ": ";
                	       $output = '';
                        }
                	    `cd $translationPath
                	     msgmerge --no-fuzzy-matching --update $poFile template.pot $output`;
                    }
                }
            }
        }
    }
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
    return "Locale.Gettext.prototype._msgs['./LC_MESSAGES/$appName'] = new Locale.Gettext.PO($poObject);";
}

?>