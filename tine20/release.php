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
global $tine20path;
$tine20path = dirname(__FILE__);

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
    
    foreach (scandir("$tine20path/Tinebase/js") as $file) {
        if (substr($file, -7) == '-all.js') {
            unlink("$tine20path/Tinebase/js/$file");
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

    $verbose = $opts->v ? ' --verbose ' : '';
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
    $verbose = $opts->v ? ' --verbose ' : '';
    
    system("java -jar $yuiCompressorPath $verbose --charset utf-8 -o $tine20path/Tinebase/js/tine-all.js $tine20path/Tinebase/js/tine-all-debug.js");
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
        'images/empty_photo.png',
        'images/oxygen/16x16/actions/knewstuff.png'
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
    
    exec("cd $tine20path; find ExtJS/resources/images/ -type f -name *.gif", $extImages);
    $files = array_merge($files, $extImages);
    exec("cd $tine20path; find ExtJS/resources/images/ -type f -name *.png", $extImages);
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
        
        unifyTranslations(new Zend_Locale($locale));
    }
    unifyTranslations(new Zend_Locale('en'));
}

// build zend translation lists only on demand
if ( $opts->z ) {
    // dump one langfile for every locale
    $localelist = Zend_Locale::getLocaleList();
    //$localelist = array ( "en_US" => 1 ); 
    foreach ($localelist as $locale => $something) {        
        $js = createJsTranslationLists($locale);
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

/**
 * unifies / concats all translation sources into one file
 *
 * @param unknown_type $localeString
 */
function unifyTranslations($localeString)
{
    global $tine20path;
    global $yuiCompressorPath;
    
    $output = '';
    
    // compress ext translation
    switch ($localeString) {
        case 'zh_HANS':
            $extlocaleName = 'zh_CN';
            break;
        case 'zh_HANT':
            $extlocaleName = 'zh_TW';
            break;
        default:
            $extlocaleName = $localeString;
            break;
    }
    $extTranslationFile = "$tine20path/" . Tinebase_Translation::getJsTranslationFile($extlocaleName, 'ext');
    if (file_exists($extTranslationFile)) {
        system("java -jar $yuiCompressorPath --charset utf-8 -o $tine20path/Tinebase/js/Locale/build/$localeString-ext-min.js $extTranslationFile");
    }
    
    // unify translations
    $files = array ( 
        "$tine20path/" . "Tinebase/js/Locale/build/$localeString-ext-min.js",
        "$tine20path/" . Tinebase_Translation::getJsTranslationFile($localeString, 'generic'),
        "$tine20path/" . Tinebase_Translation::getJsTranslationFile($localeString, 'tine')
    );
    
    // don't include tine en as it does not exist!
    if ($localeString == 'en') {
        unset($files[2]);
    }
    
    foreach ($files as $file) {
        if(file_exists($file)) {
            $output .= file_get_contents($file);
        }
    }
    
    file_put_contents("$tine20path/Tinebase/js/$localeString-all.js", $output);
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
        'Date', 
        'Time', 
        'DateTime', 
        'Month', 
        'Day', 
        'Symbols', 
        'Question', 
        'Language', 
        'Territory',
        'CityToTimezone',
    );
    
    $zendLocale = new Zend_Locale($_locale);
            
    foreach ( $types as $type ) {
        $list = $zendLocale->getTranslationList($type);
        //print_r ( $list );

        if ( is_array($list) ) {
            $jsContent .= "\n\t$type: {";                
                
            foreach ( $list as $key => $value ) {    
                // convert ISO -> PHP for date formats
                if ( in_array($type, array('Date', 'Time', 'DateTime')) ) {
                    $value = self::convertIsoToPhpFormat($value);
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
