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

set_include_path('.' . PATH_SEPARATOR . dirname(__FILE__) . '/library' . PATH_SEPARATOR . get_include_path());

require_once 'Tinebase/Helper.php';
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

/**
 * path to tine 2.0 checkout
 */
global $tine20path;
$tine20path = dirname(__FILE__);

/**
 * define buildtype
 *
 */
define('TINE20_BUILDTYPE', 'RELEASE');

/**
 * define title
 *
 */
define('TINE20_TITLE', 'Tine 2.0');

/**
 * path to yui compressor
 */
global $yuiCompressorPath;
$yuiCompressorPath = dirname(__FILE__) . '/../../yuicompressor-2.3.6/build/yuicompressor-2.3.6.jar';

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
        'yui|y=s'         => 'Path to yuicompressor.jar',
        'help|h'          => 'Display this help Message',
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

$build = trim(`whoami`) . ' '. Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG);

if ($opts->yui) {
    $yuiCompressorPath = $opts->yui;
}

if (!file_exists($yuiCompressorPath)) {
   echo "WARNING yuicompressor.jar ($yuiCompressorPath) not found.\n Don't compress files but only copy them.\n";
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
        'Setup/js/setup-all-debug.js',
        'Setup/js/setup-all.js',
        'Setup/css/setup-all-debug.css',
        'Setup/css/setup-all.css',
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
    
    foreach (scandir("$tine20path/Tinebase/js") as $file) {
        if (substr($file, -7) == '-all.js') {
            unlink("$tine20path/Tinebase/js/$file");
        }
    }
}

$includeFiles = Tinebase_Frontend_Http::getAllIncludeFiles();

$setupIncludeFiles = Setup_Frontend_Http::getAllIncludeFiles();

if ($opts->a || $opts->s) {
    // tine 2.0 main css files
    concatCss($includeFiles['css'], 'Tinebase/css/tine-all-debug.css');
    compress('Tinebase/css/tine-all-debug.css', 'Tinebase/css/tine-all.css');
    // setup css files
    concatCss($setupIncludeFiles['css'], 'Setup/css/setup-all-debug.css');
    compress('Setup/css/setup-all-debug.css', 'Setup/css/setup-all.css');
}

if ($opts->a || $opts->j) {
    // tine 2.0 main css files
    concatJs($includeFiles['js'], 'Tinebase/js/tine-all-debug.js');
    compress('Tinebase/js/tine-all-debug.js', 'Tinebase/js/tine-all.js');
    // setup css files
    concatJs($setupIncludeFiles['js'], 'Setup/js/setup-all-debug.js');
    compress('Setup/js/setup-all-debug.js', 'Setup/js/setup-all.js');
}

/**
 * concat css files into one big css file
 *
 * @param  array    $_files
 * @param  string   $_filename
 * @return void
 */
function concatCss(array $_files, $_filename)
{
    global $tine20path;
    
    $cssDebug = fopen("$tine20path/$_filename", 'w+');
    
    foreach ($_files as $file) {
        list($filename) = explode('?', $file);
        if (file_exists("$tine20path/$filename")) {
            $cssContent = file_get_contents("$tine20path/$filename");
            $cssContent = preg_replace('/(\.\.\/){3,}images/i', '../../images', $cssContent);
            $cssContent = preg_replace('/(\.\.\/){3,}library/i', '../../library', $cssContent);
            fwrite($cssDebug, $cssContent . "\n");
        }
    }
    
    fclose($cssDebug);
    
}

/**
 * concat js files into one big css file
 *
 * @param  array    $_files
 * @param  string   $_filename
 * @return void
 */
function concatJs(array $_files, $_filename)
{
    global $tine20path, $build;
    $revisionInfo = getDevelopmentRevision();
    
    $jsDebug = fopen("$tine20path/$_filename", 'w+');
    
    foreach ($_files as $file) {
        list($filename) = explode('?', $file);
        if (file_exists("$tine20path/$filename")) {
            fwrite($jsDebug, '// file: ' . "$tine20path/$filename" . "\n");
            $jsContent = file_get_contents("$tine20path/$filename");
            $jsContent = preg_replace('/Tine\.clientVersion\.codeName.*;/i',      "Tine.clientVersion.codeName = '$revisionInfo';", $jsContent);
            $jsContent = preg_replace('/Tine\.clientVersion\.buildType.*;/i',     "Tine.clientVersion.buildType = 'DEBUG';", $jsContent);
            $jsContent = preg_replace('/Tine\.clientVersion\.buildDate.*;/i',     "Tine.clientVersion.buildDate = '" . Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG) . "';", $jsContent);
            $jsContent = preg_replace('/Tine\.clientVersion\.packageString.*;/i', "Tine.clientVersion.packageString = 'none';", $jsContent);
            $jsContent = preg_replace('/Tine\.title = \'Tine 2\.0\';/i', "Tine.title = '" . TINE20_TITLE . "';", $jsContent);
            //$jsContent = preg_replace('/\$.*Build:.*\$/i', $build, $jsContent);
            fwrite($jsDebug, $jsContent . "\n");
        }
    }
    
    fclose($jsDebug);
    
}

/**
 * compress file using js/css compressor
 * 
 * @param  string $_infile
 * @param  string $_outfile
 * @return void
 */
function compress($_infile, $_outfile)
{
    global $opts, $tine20path, $yuiCompressorPath;
    
if (file_exists($yuiCompressorPath)) {
        $contents = file_get_contents("$tine20path/$_infile");
        $contents = preg_replace('/Tine\.clientVersion\.buildType.*;/i', "Tine.clientVersion.buildType='RELEASE';", $contents);
        file_put_contents("$tine20path/$_outfile", $contents);
        
        $verbose = $opts->v ? ' --verbose ' : '';
        system("java -jar $yuiCompressorPath $verbose --charset utf-8 -o $tine20path/$_outfile $tine20path/$_outfile");
    } else {
        copy("$tine20path/$_infile","$tine20path/$_outfile");
    }
}

/*
 * code to build the html5 manfest
 *  
if ($opts->m) {
    $defaultFiles = "CACHE MANIFEST                                          
# Build by $build                                                   
CACHE:                                                  
Tinebase/css/tine-all.css                               
Tinebase/js/tine-all.js                                 

library/ExtJS/ext-all.js
library/ExtJS/adapter/ext/ext-base.js       
library/ExtJS/resources/css/ext-all.css
library/ExtJS/resources/css/xtheme-gray.css 
    ";
    
    $manifest = fopen($tine20path . '/tine20.manifest', 'w+');
    fwrite($manifest, $defaultFiles . "\n");
    
    $tineCSS = file_get_contents($tine20path . '/Tinebase/css/tine-all.css');
    preg_match_all('/url\(..\/..\/(images.*)\)/U', $tineCSS, $matches);
    $oxygenImages = array_unique($matches[1]);
    foreach($oxygenImages as $oxygenImage) {
        fwrite($manifest, $oxygenImage . "\n");
    }
    
    exec("cd $tine20path; find library/ExtJS/resources/images/ -type f", $extImages);
    foreach($extImages as $extImage) {
        fwrite($manifest, $extImage . "\n");
    }
    fclose($manifest);
}*/

if ($opts->a || $opts->m) {
    $files = array(
        'Tinebase/css/tine-all.css',                               
        'Tinebase/js/tine-all.js',
        'styles/tine20.css',                             
        'library/ExtJS/ext-all.js',
        'library/ExtJS/adapter/ext/ext-base.js',   
        'library/ExtJS/resources/css/ext-all.css',
        'images/oxygen/16x16/actions/knewstuff.png' // ???
    );
    
    // no subdirs! => solaris does not know find -maxdeps 1
    exec("cd $tine20path; ls images/* | grep images/ | egrep '\.png|\.gif|\.jpg'", $baseImages);
    $files = array_merge($files, $baseImages);
    
    $tineCSS = file_get_contents($tine20path . '/Tinebase/css/tine-all-debug.css');
    preg_match_all('/url\(..\/..\/(images.*)\)/U', $tineCSS, $matches);
    $files = array_merge($files, $matches[1]);
    
    $tineCSS = file_get_contents($tine20path . '/Tinebase/css/tine-all-debug.css');
    preg_match_all('/url\(..\/..\/(library.*)\)/U', $tineCSS, $matches);
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
    
    exec("cd $tine20path; find library/ExtJS/resources/images -type f -name *.gif", $extImages);
    $files = array_merge($files, $extImages);
    exec("cd $tine20path; find library/ExtJS/resources/images -type f -name *.png", $extImages);
    $files = array_merge($files, $extImages);
    
    $manifest = array(
        'betaManifestVersion'   => 1,
        'version'               => $build,
        'entries'               => array()
    );

    $files = array_unique($files);
    foreach($files as $file) {
        if (! is_file("$tine20path/$file")) {
            echo "WARNING $file not found, removing it from manifest.\n";
        } else if (substr(basename($file), 0, 1) == '.' || ! preg_match('/(js|css|gif|png|jpg)$/', $file))  {
            echo "INFO $file is unwanted, removing it from manifest.\n";
        } else {
            $manifest['entries'][] = array(
                'url'           => '../../' . $file,
                #'ignoreQuery'   => true
            );
            
        }
    }
    
    $jsonManifest = json_encode($manifest);
    $jsonManifest = str_replace('\/', '/', $jsonManifest);
    # enable to make manifest file more readable
    #$jsonManifest = str_replace('},{', "},\n{", $jsonManifest);
    
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

// translations
if ($opts->a || $opts->t) {
    $availableTranslations = Tinebase_Translation::getAvailableTranslations();
    
    foreach ($availableTranslations as $translation) {
        $localeString = $translation['locale'];
        $locale = new Zend_Locale($localeString);
        
        if ( $opts->v ) {
            echo "building language '$localeString'\n";
        }
        
        $jsTranslation = Tinebase_Translation::getJsTranslations($locale);
        file_put_contents("$tine20path/Tinebase/js/Locale/build/$locale-all-debug.js", $jsTranslation);

        if (file_exists($yuiCompressorPath)) {
            system("java -jar $yuiCompressorPath --charset utf-8 -o $tine20path/Tinebase/js/Locale/build/$locale-all.js $tine20path/Tinebase/js/Locale/build/$locale-all-debug.js");
        } else {
            copy("$tine20path/Tinebase/js/Locale/build/$locale-all-debug.js","$tine20path/Tinebase/js/Locale/build/$locale-all.js");
        }

    }
}

// build zend translation lists only on demand
if ( $opts->z ) {
    // dump one langfile for every locale
    $localelist = Zend_Locale::getLocaleList();
    //$localelist = array ( "de" => 1 ); 
    foreach ($localelist as $locale => $something) {
    	try {   
	        $js = createJsTranslationLists($locale);
	        file_put_contents("$tine20path/Tinebase/js/Locale/static/generic-$locale-debug.js", $js);
	        if (file_exists($yuiCompressorPath)) {
	            if ( $opts->v ) {
	                echo "compressing file generic-$locale.js\n";
	            }
	            system("java -jar $yuiCompressorPath --charset utf-8 -o $tine20path/Tinebase/js/Locale/static/generic-$locale.js $tine20path/Tinebase/js/Locale/static/generic-$locale-debug.js");
	        } else {
	            copy("$tine20path/Tinebase/js/Locale/static/generic-$locale-debug.js","$tine20path/Tinebase/js/Locale/static/generic-$locale.js");
	        }
    	} catch (Exception $e) {
    		echo "WARNING: could not create translation file for '$locale': '{$e->getMessage()}'\n";
    	}

    }
}

/**
 * creates translation lists js files for locale with js object
 *
 * @param   string $_locale
 * @return  string the file contents
 */
function createJsTranslationLists($_locale)
{
    $jsContent = "Locale.prototype.TranslationLists = {\n";

    $types = array(
        'Date'           => array('path' => 'Date'),
        'Time'           => array('path' => 'Time'),
        'DateTime'       => array('path' => 'DateTime'),
        'Month'          => array('path' => 'Month'),
        'Day'            => array('path' => 'Day'),
        'Symbols'        => array('path' => 'Symbols'),
        'Question'       => array('path' => 'Question'),
        'Language'       => array('path' => 'Language'),
        'CountryList'    => array('path' => 'Territory', 'value' => 2),
        'Territory'      => array('path' => 'Territory', 'value' => 1),
        'CityToTimezone' => array('path' => 'CityToTimezone'),
    );
    
    $zendLocale = new Zend_Locale($_locale);
    
    foreach ( $types as $name => $path) {
        $list = $zendLocale->getTranslationList($path['path'], $_locale, array_key_exists('value', $path) ? $path['value'] : false);
        //print_r ( $list );
        
        if ( is_array($list) ) {
            $jsContent .= "\n\t$name: {";                
                
            foreach ( $list as $key => $value ) {    
                // convert ISO -> PHP for date formats
                if ( in_array($name, array('Date', 'Time', 'DateTime')) ) {
                    $value = convertIsoToPhpFormat($value);
                }
                $value = preg_replace("/\"/", '\"', $value);        
                $jsContent .= "\n\t\t'$key': \"$value\",";
            }
            // remove last comma
            $jsContent = chop($jsContent, ",");
                    
            $jsContent .= "\n\t},";
        }
    }    
    $jsContent = chop($jsContent, ",");
    
    $jsContent .= "\n};\n";
    return $jsContent;
}

/**
 * Converts a format string from ISO to PHP format
 * reverse the functionality of Zend's convertPhpToIsoFormat()
 * 
 * @param  string  $format  Format string in PHP's date format
 * @return string           Format string in ISO format
 */
function convertIsoToPhpFormat($format)
{        
    $convert = array(
        'c' => '/yyyy-MM-ddTHH:mm:ssZZZZ/',
        '$1j$2' => '/([^d])d([^d])/',
        'j$1' => '/^d([^d])/', 
        '$1j' => '/([^d])d$/', 
        't' => '/ddd/', 
        'd' => '/dd/', 
        'l' => '/EEEE/', 
        'D' => '/EEE/', 
        'S' => '/SS/',
        'w' => '/eee/', 
        'N' => '/e/', 
        'z' => '/D/', 
        'W' => '/w/', 
        '$1n$2' => '/([^M])M([^M])/', 
        'n$1' => '/^M([^M])/', 
        '$1n' => '/([^M])M$/', 
        'F' => '/MMMM/', 
        'M' => '/MMM/',
        'm' => '/MM/', 
        'L' => '/l/', 
        'o' => '/YYYY/', 
        'Y' => '/yyyy/', 
        'y' => '/yy/',
        'a' => '/a/', 
        'A' => '/a/', 
        'B' => '/B/', 
        'h' => '/hh/',
        'g' => '/h/', 
        '$1G$2' => '/([^H])H([^H])/', 
        'G$1' => '/^H([^H])/', 
        '$1G' => '/([^H])H$/', 
        'H' => '/HH/', 
        'i' => '/mm/', 
        's' => '/ss/', 
        'e' => '/zzzz/', 
        'I' => '/I/', 
        'P' => '/ZZZZ/', 
        'O' => '/Z/',
        'T' => '/z/', 
        'Z' => '/X/', 
        'r' => '/r/', 
        'U' => '/U/',
    );
    
    //echo "pre:".$format."\n";
    
    $patterns = array_values($convert);
    $replacements = array_keys($convert);
    $format = preg_replace($patterns, $replacements, $format);
    
    //echo "post:".$format."\n";
    //echo "---\n";
    
    return $format;
}
