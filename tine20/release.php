#!/usr/bin/env php
<?php
/**
 * build helper for js and css
 *
 * @package     HelperScripts
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

if (php_sapi_name() != 'cli') {
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
$yuiCompressorPath = dirname(__FILE__) . '/../yuicompressor-2.3.6/build/yuicompressor-2.3.6.jar';

$jslintPath = dirname(__FILE__) . '/../../jslint4java-1.1/jslint4java-1.1+rhino.jar';

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
        'lint'            => 'JSLint',
        'css|s'           => 'Build CSS Files',
        'manifest|m'      => 'Build offline manifest',
        'all|a'           => 'Build all (default)',
        'zend|z'          => 'Build Zend Translation Lists',
        'help'            => 'Display this help Message',
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
   echo $e->getUsageMessage();
   exit;
}

if (count($opts->toArray()) === 0 || $opts->h) {
    echo $opts->getUsageMessage();
    exit;
}

$build = trim(`whoami`) . ' '. Zend_Date::now()->getIso();

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
    if ($opts->v) {
        $verbose = ' --verbose ';
    }
    system("java -jar $yuiCompressorPath $verbose --charset utf-8 -o $tine20path/Tinebase/css/tine-all.css $tine20path/Tinebase/css/tine-all-debug.css");
}

if ($opts->a || $opts->j) {
    $jsDebug = fopen($tine20path . '/Tinebase/js/tine-all-debug.js', 'w+');
    foreach ($includeFiles['js'] as $file) {
        list($filename) = explode('?', $file);
        if (file_exists("$tine20path/$filename")) {
            fwrite($jsDebug, '// file: ' . "$tine20path/$filename" . "\n");
            $jsContent = file_get_contents("$tine20path/$filename");
            $jsContent = preg_replace('/\$.*Build:.*\$/i', $build, $jsContent);
            fwrite($jsDebug, $jsContent . "\n");
        }
    }
    fclose($jsDebug);
    if ($opts->v) {
        $verbose = ' --verbose ';
    }
    system("java -jar $yuiCompressorPath $verbose --charset utf-8 -o $tine20path/Tinebase/js/tine-all.js $tine20path/Tinebase/js/tine-all-debug.js");
}

/*
 * code to build the html5 manifest
 *  
if ($opts->m) {
    $defaultFiles = "CACHE MANIFEST                                          
# Build by $build                                                   
CACHE:                                                  
Tinebase/css/tine-all.css                               
Tinebase/js/tine-all.js                                 

ExtJS/ext-all.js
ExtJS/adapter/ext/ext-base.js       
ExtJS/resources/css/ext-all.css
ExtJS/resources/css/xtheme-gray.css 
    ";
    
    $manifest = fopen($tine20path . '/tine20.manifest', 'w+');
    fwrite($manifest, $defaultFiles . "\n");
    
    $tineCSS = file_get_contents($tine20path . '/Tinebase/css/tine-all.css');
    preg_match_all('/url\(..\/..\/(images.*)\)/U', $tineCSS, $matches);
    $oxygenImages = array_unique($matches[1]);
    foreach($oxygenImages as $oxygenImage) {
        fwrite($manifest, $oxygenImage . "\n");
    }
    
    exec("cd $tine20path; find ExtJS/resources/images/ -type f", $extImages);
    foreach($extImages as $extImage) {
        fwrite($manifest, $extImage . "\n");
    }
    fclose($manifest);
}*/

if ($opts->a || $opts->m) {
    $files = array(
        'Tinebase/css/tine-all.css',                               
        'Tinebase/js/tine-all.js',                                 
        'ExtJS/ext-all.js',
        'ExtJS/adapter/ext/ext-base.js',   
        'ExtJS/resources/css/ext-all.css',
        'ExtJS/resources/css/xtheme-gray.css',
        'images/empty_photo.png'
    );
    
    $tineCSS = file_get_contents($tine20path . '/Tinebase/css/tine-all.css');
    preg_match_all('/url\(..\/..\/(images.*)\)/U', $tineCSS, $matches);
    $files = array_merge($files, $matches[1]);
        
    $tineJs = file_get_contents($tine20path . '/Tinebase/js/tine-all-debug.js');
    preg_match_all('/labelIcon: [\'|"](.*png)/U', $tineJs, $matches);
    $files = array_merge($files, $matches[1]);
    
    $tineJs = file_get_contents($tine20path . '/Tinebase/js/tine-all-debug.js');
    preg_match_all('/labelIcon: [\'|"](.*gif)/U', $tineJs, $matches);
    $files = array_merge($files, $matches[1]);
    
    $tineJs = file_get_contents($tine20path . '/Tinebase/js/tine-all-debug.js');
    preg_match_all('/src=[\'|"](.*png)/U', $tineJs, $matches);
    $files = array_merge($files, $matches[1]);
    
    $tineJs = file_get_contents($tine20path . '/Tinebase/js/tine-all-debug.js');
    preg_match_all('/src=[\'|"](.*gif)/U', $tineJs, $matches);
    $files = array_merge($files, $matches[1]);
    
    exec("cd $tine20path; find ExtJS/resources/images/ -type f", $extImages);
    $files = array_merge($files, $extImages);
    
    $manifest = array(
        'betaManifestVersion'   => 1,
        'version'               => $build,
        'entries'               => array()
    );

    $files = array_unique($files);
    foreach($files as $file) {
        $manifest['entries'][] = array(
            'url'           => '../../' . $file,
            #'ignoreQuery'   => true
        );
    }
    
    $jsonManifest = json_encode($manifest);
    $jsonManifest = str_replace('\/', '/', $jsonManifest);
    # enable to make manifest file more readable
    $jsonManifest = str_replace('},{', "},\n{", $jsonManifest);
    
    $fd = fopen($tine20path . '/Tinebase/js/tine20-manifest.js', 'w+');
    fwrite($fd, $jsonManifest);
    fclose($fd);
}

if ($opts->lint) {
    foreach ($includeFiles['js'] as $file) {
        list($filename) = explode('?', $file);
        if (file_exists("$tine20path/$filename")) {
            $lint = `java -jar $jslintPath --laxbreak $tine20path/$filename`;
            if ($lint) {
                echo "$tine20path/$filename: \n";
                echo "------------------------------------------------------------------\n";
                echo $lint;
                echo "\n\n";
            }
                
        }
    }
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