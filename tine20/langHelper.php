#!/usr/bin/env php
<?php
/**
 * lang helper
 *
 * @package     HelperScripts
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add filter for applications
 */

if (isset($_SERVER['HTTP_HOST'])) {
    die('not allowed!');
}

$paths = array(
    realpath(dirname(__FILE__)),
    realpath(dirname(__FILE__) . '/library'),
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

require_once 'Tinebase/Helper.php';

/**
 * path to tine 2.0 checkout
 */
global $tine20path;
$tine20path = dirname(__FILE__);

/**
 * options
 */
try {
    $opts = new Zend_Console_Getopt(
    array(
        'verbose|v'       => 'Output messages',
        'clean|c'         => 'Cleanup all tmp files',
        'wipe|w'          => 'wipe all local translations',
        'update|u'        => 'Update lang files (shortcut for --pot --potmerge --mo --clean)',
        'package=s'       => 'Create a translation package',
        'app=s'           => 'Work only on this Application',
        'pot'             => '(re) generate xgettext po template files',
        'potmerge'        => 'merge pot contents into po files',
        'statistics'      => 'generate lang statistics',
        'contribute=s'    => 'merge contributed translations of <path to archive> (implies --update)',
        'language=s'      => 'contributed language or language to handle',
        'mo'              => 'Build mo files',
        'newlang=s'       => 'Add new language',
        'overwrite'       => '  overwrite existing lang files',
        'git'             => 'Add new/updated lang files to git',
        'lp:merge=s'      => 'merge langpackage from launchpad',
        'help|h'          => 'Display this help Message',
        
        //'filter=s'        => 'Filter for applications'
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
   echo $e->getUsageMessage();
   exit;
}

// Check app Parameter
if(!empty($opts->app)) {
    if(!array_key_exists($opts->app, Tinebase_Translation::getTranslationDirs())) {
        echo chr(10);
        echo 'Application "' . $opts->app . '" not found!'. chr(10);
        echo chr(10);
        exit;
    } else {
        echo 'Working on Application "' . $opts->app . '"...'. chr(10) ;
    }

}

if ($opts->wipe) {
    foreach (Tinebase_Translation::getTranslationDirs() as $appName => $translationPath) {
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        if ($_verbose) {
            echo "Processing $appName po files \n";
        }
        
        `cd "$translationPath" 
        rm *`;
    }
}

if (count($opts->toArray()) === 0  || $opts->h) {
    echo $opts->getUsageMessage();
    exit;
}

if ($opts->u || $opts->contribute || $opts->{'lp:merge'}) {
    $opts->pot = $opts->potmerge = $opts->mo = $opts->c = true;
}

if ($opts->pot) {
    generatePOTFiles($opts->v);
}

if ($opts->potmerge) {
    potmerge($opts->v);
}

if ($opts->newlang) {
    generateNewTranslationFiles($opts->newlang, $opts->v, $opts->overwrite);
    potmerge($opts->v);
    msgfmt($opts->v);
    if($opts->git) {
        gitAdd($opts->newlang);
    }
    $opts->c = true;
}

if($opts->contribute) {
    $_verbose = $opts->v;
    if (!isset ($opts->language)) {
        echo "Error: you need to specify the contributed language (--language) \n";
        exit;
    }
    if (! isset ($opts->contribute)) {
        echo "You need to specify an archive of the lang updates!  \n";
        exit;
    }
    if (! is_file($opts->contribute)) {
        echo "Archive file '" . $opts->contribute . "' could not be found! \n";
        exit;
    }
    contributorsMerge($opts->v, $opts->language, $opts->contribute);
    echo "merging completed :-) \n";
}

if ($opts->{'lp:merge'}) {
    if (! isset ($opts->{'lp:merge'})) {
        echo "You need to specify an archive of the lang updates!  \n";
        exit;
    }
    if (! is_readable($opts->{'lp:merge'})) {
        echo "Archive file '" . $opts->{'lp:merge'} . "' could not be found! \n";
        exit;
    }
    
    launchpadMerge($opts->v, $opts->{'lp:merge'}, $opts->git);
}

if ($opts->mo) {
    msgfmt($opts->v);
}

if ($opts->c || $opts->package) {
    // remove translation backups of msgmerge
    `cd "$tine20path"
    find . -type f -iname "*.po~" -exec rm {} \;`;
}
if ($opts->statistics) {
    statistics($opts->v);
}

if ($opts->package) {
    buildpackage($opts->v, $opts->{'package'} ?: NULL);
}

/**
 * returns list of existing langugages
 * (those, having a correspoinding Tinebase po file)
 *
 * @return array 
 */
function getExistingLanguages($_verbose)
{
    global $tine20path;
    
    $langs = array();
    foreach (scandir("$tine20path/Tinebase/translations") as $poFile) {
        if (substr($poFile, -3) == '.po') {
            $langCode = substr($poFile, 0, -3);
            if ($_verbose) {
                echo "found language '$langCode'\n";
            }
            
            $langs[] = $langCode;
        }
    }
    
    return $langs;
}

/**
 * Checks if Application is needed
 * @param bool $verbose should a message appear on returning false 
 * @param string $appName
 */
function checkAppName($appName, $verbose) {
    
    global $opts;
    
    if(!empty($opts->app)) {
        $ret =  strtolower($appName) == strtolower($opts->app);
    } else {
        $ret = true;
    }
    
    if ($verbose && ! $ret) {
        echo 'Skipping Application ' . $appName .chr(10);
    }
    
    return $ret;
}

/**
 * checks if language parameter is set and verifies if translation
 * of the language defined by the langCode should be created
 * @param string $langCode
 * @param bool $verbose should a message appear on returning false
 * @return bool
 */
function checkLang($langCode, $verbose) {
    global $opts;
    
    if(! empty($opts->language)) {
        $ret = ($langCode == $opts->language);
    } else {
        $ret = true;
    }
    
    if ($verbose && ! $ret) {
        echo 'Skipping Language ' . $langCode .chr(10);
    }
    
    return $ret;
}

/**
 * checks wether a translation exists or not
 * 
 * @param  string $_locale
 * @return bool
 */
function translationExists($_locale)
{
    foreach (Tinebase_Translation::getTranslationDirs() as $dir) {
        if (file_exists("$dir/$_locale.po")) {
            return true;
        }
    }
    return false;
}

/**
 * (re) generates po template files
 */
function generatePOTFiles($_verbose)
{
    global $tine20path;
    if (file_exists("$tine20path/Tinebase/js/tine-all.js")) {
        die("You need to remove tine-all.js before updating lang files! \n");
    }
    
    foreach (Tinebase_Translation::getTranslationDirs() as $appName => $translationPath) {
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        if ($_verbose) {
            echo "Creating $appName template \n";
        }
        $appPath = "$translationPath/../";
        
        generateNewTranslationFile('en', 'GB', $appName, getPluralForm('English'), "$translationPath/template.pot",  $_verbose);
        
        `cd "$appPath" 
        find . -type f -iname "*.php" -or -type f -iname "*.js" -or -type f -iname "*.xml" | xgettext --force-po --omit-header -j -o translations/template.pot -L Python --from-code=utf-8 -k=_ -f - 2> /dev/null`;
        
    }
}

/**
 * potmerge
 */
function potmerge($_verbose)
{
    
    $langs = getExistingLanguages($_verbose);
    $msgDebug = $_verbose ? '' : '2> /dev/null';
    
    foreach (Tinebase_Translation::getTranslationDirs() as $appName => $translationPath) {
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        if ($_verbose) {
            echo "Processing $appName po files \n";
        }
        
        if ($_verbose) {
           echo "creating en.po from template.po\n";
        }
        generateNewTranslationFile('en', 'GB', $appName, getPluralForm('English'), "$translationPath/en.po",  $_verbose);
        $enHeader = file_get_contents("$translationPath/en.po");
        `cd "$translationPath"
         msgen template.pot > en.po $msgDebug`;
         
        foreach ($langs as $langCode) {
            
            if (! checkLang($langCode, $_verbose)) continue;
            
            $poFile = "$translationPath/$langCode.po";
            
            if (! is_file($poFile)) {
                if ($_verbose) {
                    echo "Adding non exising translation $langCode for $appName\n";
                }
                
                if (strpos($langCode, '_') !== FALSE) {
                    list ($language, $region) = explode('_', $langCode);
                } else {
                    $language = $langCode;
                    $region = '';
                }
    
                $locale = new Zend_Locale('en');
                $languageName = $locale->getTranslation($language, 'language');
                $regionName = ($region) ? $locale->getTranslation($region, 'country') : '';
                $pluralForm = getPluralForm($languageName);
                
                generateNewTranslationFile($languageName, $regionName, $appName, $pluralForm, $poFile, $_verbose);
            }

            if ($_verbose) {
               echo $poFile . ": ";
            }
            `cd "$translationPath"
             msgmerge --no-fuzzy-matching --no-wrap $poFile template.pot $msgDebug -o $poFile`;
        }
    }
}

/**
 * contributorsMerge
 *
 * @param bool   $_verbose
 * @param string $_language
 * @param string $_archive
 */
function contributorsMerge($_verbose, $_language, $_archive)
{
    global $tine20path;
    $tmpdir = '/tmp/tinetranslations/';
    `rm -Rf $tmpdir`;
    `mkdir $tmpdir`;
    //`cp $archive $tmpdir`;
    switch (substr($_archive, -4)) {
        case '.zip':
            `unzip -d $tmpdir '$_archive'`;
            break;
        default:
            echo "Error: Only zip archives are supported \n";
            exit;
            break;
    }
    
    $basePath = $tmpdir;
    while (true) {
        $contents = scandir($basePath);
        if (count($contents ) == 3) {
            $basePath .= $contents[2] . '/';
            if (! is_dir($basePath)) {
                echo "Error: Could not find translations! \n";
                exit;
            }
        } elseif ($contents[2] == '__MACOSX') {
            // max os places a hiddes __MACOSX in the archives
            $basePath .= $contents[3] . '/';
            if (! is_dir($basePath)) {
                echo "Error: Could not find translations! \n";
                exit;
            }
        } else {
            break;
        }
    }
    
    foreach ($contents as $appName) {
        if ($appName{0} == '.' || $appName{0} == '_') continue;
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        if ($_verbose) {
            echo "Processing translation updates for $appName \n";
        }
        
        $tinePoFile        = "$tine20path/$appName/translations/$_language.po";
        $contributedPoFile = "$basePath/$appName/translations/$_language.po";
        
        if (! is_file($tinePoFile)) {
            echo "Error: could not find langfile $_language.po in Tine 2.0's $appName \n";
            continue;
            exit;
        }
        if (! is_file($contributedPoFile)) {
            //check leggacy
            $contributedPoFile = "$basePath/$appName/$_language.po";
            if (! is_file($contributedPoFile)) {
                echo "Warning: could not find langfile $_language.po in contributor's $appName \n";
                continue;
            }
        }
        // do the actual merging
        $output = '2> /dev/null';
        if ($_verbose) {
           echo $_language . ".po : ";
           $output = '';
        }
        `msgmerge --no-fuzzy-matching --update '$contributedPoFile'  $tinePoFile $output`;
        `cp '$contributedPoFile' $tinePoFile`;
    }
}
/**
 * lanuchpad merge
 *
 * @TODO add auto git support
 * 
 * @param bool   $_verbose
 * @param string $_archive
 * @param bool   $_git
 */
function launchpadMerge($_verbose, $_archive, $_git)
{
    global $tine20path;
    
    if (is_dir($_archive)) {
        $contributionDir = $_archive;
        
        if (is_dir("$contributionDir/.bzr")) {
            `cd $contributionDir
            bzr merge --pull`;
        }
    } else {
        $contributionDir = '/tmp/tinetranslations/';
        `rm -Rf $contributionDir`;
        `mkdir $contributionDir`;
        
        switch (substr($_archive, -7)) {
            case '.tar.gz':
                `tar -xz -C $contributionDir -f $_archive`;
                break;
            default:
                echo "Error: Only tar.gz archives are supported \n";
            exit;
            break;
        }
    }
    
    foreach (scandir($contributionDir) as $appName) {
        if ($appName{0} == '.' || $appName{0} == '_') continue;
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        if ($_verbose) echo "Processing translation updates for $appName \n";
        
        foreach (scandir("$contributionDir/$appName") as $poFile) {
            if (substr($poFile, -3) != '.po') continue;
            
            $lang = str_ireplace(array("$appName-", '.po'), '', $poFile);
            if ($_verbose) echo "Processing language '$lang' \n";
            
            $tinePoFile        = "$tine20path/$appName/translations/$lang.po";
            $contributedPoFile = "$contributionDir/$appName/$poFile";
            
            if (! is_file($tinePoFile)) {
                echo "Error: could not find Tine 2.0's langfile $tinePoFile\n";
                continue;
                exit;
            }
            
            // do the actual merging
            $output = $_verbose ? '' : '2> /dev/null';
            `msgmerge --no-fuzzy-matching --update '$contributedPoFile'  $tinePoFile $output`;
            `cp '$contributedPoFile' $tinePoFile`;
        }
    }
}

/**
 * msgfmt
 */
function msgfmt ($_verbose)
{
    foreach (Tinebase_Translation::getTranslationDirs() as $appName => $translationPath) {
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        if ($_verbose) {
            echo "Entering $appName \n";
        }
        foreach (scandir($translationPath) as $poFile) {
            if (substr($poFile, -3) == '.po') {
                $langName = substr($poFile, 0, -3);
                if ($_verbose) {
                    echo "Processing $appName/$poFile \n";
                }
                // create mo file
                `cd "$translationPath"
                msgfmt -o $langName.mo $poFile`;
            }
        }
    }
}

/**
 * create package file for translators
 * 
 * @param boolean $_verbose
 * @param string $_archive file or directory
 */
function buildpackage($_verbose, $_archive)
{
    $destDir = __DIR__;
    $tmpdir = '/tmp/tinetranslations/';
    `rm -Rf $tmpdir`;
    `mkdir $tmpdir`;
    
    foreach (Tinebase_Translation::getTranslationDirs() as $appName => $translationPath) {
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        `mkdir $tmpdir/$appName`;
        generateNewTranslationFile('en', 'GB', $appName, getPluralForm('English'), "$tmpdir/$appName/$appName.pot",  $_verbose);
        `cat $translationPath/template.pot >> $tmpdir/$appName/$appName.pot`;
        `cp $translationPath/*.po $tmpdir/$appName/`;
    }
    
    if ($_archive && is_dir($_archive)) {
        `cp -r $tmpdir/* $_archive`;
        
        if (is_dir("$_archive/.bzr")) {
            `cd $_archive
            bzr add *
            bzr commit -m 'Tine 2.0 Translations'
            bzr push`;
        }
    } else {
        $filename = ($_archive && strpos($_archive, 'tar.gz') !== FALSE) ? $_archive : 'lp-lang-package.tar.gz';
        `cd "$tmpdir"
         tar -czf $filename *`;
        `mv $tmpdir/$filename {$destDir}`;
    }
}

/**
 * generate statistics
 *
 * @param  bool $_verbose
 * @return void
 */
function statistics($_verbose)
{
    global $tine20path;
    $statsFile = "$tine20path/langstatistics.json";
    $locale = new Zend_Locale('en');
    
    $langStats       = array();
    $poFilesStats    = array();
    
    foreach (Tinebase_Translation::getTranslationDirs() as $appName => $translationPath) {
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        if ($_verbose) {
            echo "Entering $appName \n";
        }
        $appStats[$appName] = array();
        foreach (scandir($translationPath) as $poFile) {
            if (substr($poFile, -3) == '.po') {
                if ($_verbose) {
                    echo "Processing $appName/$poFile \n";
                }
                
                $langCode = substr($poFile, 0, -3);
                $langLocale = new Zend_Locale($langCode);
                
                $statsOutput = `msgfmt --statistics $translationPath/$poFile 2>&1`;
                $statsParts = explode(',', $statsOutput);
                $statsParts = preg_replace('/^\s*(\d+).*/i', '$1', $statsParts);

                $translated = $fuzzy = $untranslated = $total = 0;
                switch (count($statsParts)) {
                    case 1:
                        $translated     = $statsParts[0];
                        break;
                    case 2:
                        $translated     = $statsParts[0];
                        $untranslated   = $statsParts[1];
                        break;
                    case 3:
                        $translated     = $statsParts[0];
                        $fuzzy          = $statsParts[1];
                        $untranslated   = $statsParts[2];
                        break;
                    default:
                        echo "Unexpected statistic return \n";
                        exit;
                }
                $total = array_sum($statsParts);
                
                $poFileStats = array(
                    'locale'       => $langCode,
                    'language'     => $locale->getTranslation($langLocale->getLanguage(), 'language'),
                    'region'       => $locale->getTranslation($langLocale->getRegion(), 'country'),
                    'appname'      => $appName,
                    'translated'   => (int)$translated,
                    'fuzzy'        => (int)$fuzzy,
                    'untranslated' => (int)$untranslated,
                    'total'        => array_sum($statsParts),
                );
                $poFilesStats[] = $poFileStats;
                
                // sum up lang statistics
                $langStats[$langCode] = array_key_exists($langCode,$langStats) ? $langStats[$langCode] : array(
                    'locale'       => '',
                    'language'     => '',
                    'region'       => $locale->getTranslation($langLocale->getRegion(), 'country'),
                    'translated'   => 0,
                    'fuzzy'        => 0,
                    'untranslated' => 0,
                    'total'        => 0
                );
                
                $langStats[$langCode]['locale']        = $langCode;
                $langStats[$langCode]['language']      = $locale->getTranslation($langLocale->getLanguage(), 'language');
                $langStats[$langCode]['region']        = $locale->getTranslation($langLocale->getRegion(), 'country');
                $langStats[$langCode]['appname']       = 'all';
                $langStats[$langCode]['translated']   += $poFileStats['translated'];
                $langStats[$langCode]['fuzzy']        += $poFileStats['fuzzy'];
                $langStats[$langCode]['untranslated'] += $poFileStats['untranslated'];
                $langStats[$langCode]['total']        += $poFileStats['total'];
            }
        }
    }
    
    // clean up unwanted messages.mo
    `rm messages.mo`;
    
    $results = array(
        'version'      => getDevelopmentRevision(),
        'langStats'    => array_values($langStats),
        'poFilesStats' => $poFilesStats
    );
    
    file_put_contents($statsFile, Zend_Json::encode($results));
}

/**
 * generates po file with appropriate header
 *
 * @param  string $_languageName
 * @param  string $_regionName
 * @param  string $_appName
 * @param  bool   $_verbose
 * @return void
 */
function generateNewTranslationFile($_languageName, $_regionName, $_appName, $_pluralForm, $_file, $_verbose=false)
{
    global $tine20path;

    $poHeader = 
'msgid ""
msgstr ""
"Project-Id-Version: Tine 2.0 - ' . $_appName . '\n"
"POT-Creation-Date: 2008-05-17 22:12+0100\n"
"PO-Revision-Date: 2008-07-29 21:14+0100\n"
"Last-Translator: Cornelius Weiss <c.weiss@metaways.de>\n"
"Language-Team: Tine 2.0 Translators\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Poedit-Language: ' . $_languageName . '\n"
"X-Poedit-Country: ' . strtoupper($_regionName) . '\n"
"X-Poedit-SourceCharset: utf-8\n"
"Plural-Forms: ' . $_pluralForm . '\n"

';
            
    if ($_verbose) {
        echo "  Writing $_languageName po header for $_appName \n";
    }
    file_put_contents($_file, $poHeader);
}


/**
 * generates po files with appropriate header for a given locale and all apps
 * 
 * @param  string $_locale
 * @return void
 */
function generateNewTranslationFiles($_locale, $_verbose=false, $_overwrite=false)
{
    list ($language, $region) = explode('_', $_locale);
    
    $locale = new Zend_Locale('en');
    $languageName = $locale->getTranslation($language, 'language');
    $regionName = $locale->getTranslation($region, 'country');
    
    if (!$languageName) {
        die("Language '$language' is not valid / known \n");
    }
    if ($region && ! $regionName) {
        die("Region '$region' is not valid / known \n");
    }
    $regionName = $region ? $regionName : 'Not Specified / Any';
    
    if (translationExists($_locale)) {
        if ($_overwrite) {
            if ($_verbose) echo "Overwriting existing lang files for $_locale \n";
        } else {
            die("Translations for $_locale already exist \n");
        }
    }
    
    if ($_verbose) {
        echo "Generation new lang files for \n";
        echo "  Language: $languageName \n";
        echo "  Region: $regionName \n";
    }
    
    $pluralForm = getPluralForm($languageName);
    
    foreach (Tinebase_Translation::getTranslationDirs() as $appName => $translationPath) {
        
        if( ! checkAppName($appName, $_verbose)) {
            continue;
        }
        
        $file = "$translationPath/$_locale.po";
        generateNewTranslationFile($languageName, $regionName, $appName, $pluralForm, $file, $_verbose);
    }
    
    
}

/**
 * returns plural form of given language
 * 
 * @link http://www.gnu.org/software/automake/manual/gettext/Plural-forms.html
 * @param  string $_languageName
 * @return string 
 */
function getPluralForm($_languageName)
{
    switch ($_languageName) {
        // Asian family
        case 'Japanese' :
        case 'Korean' :
        case 'Vietnamese' :
        case 'Chinese' :
        // Turkic/Altaic family
        case 'Turkish' :
            return 'nplurals=1; plural=0;';
            
        // Germanic family
        case 'Danish' :
        case 'Dutch' :
        case 'English' :
        case 'Faroese' :
        case 'German' :
        case 'Norwegian' :
        case 'Norwegian Bokmål' :
        case 'Swedish' :
        // Finno-Ugric family
        case 'Estonian' :
        case 'Finnish' :
        // Latin/Greek family
        case 'Greek' :
        // Semitic family
        case 'Hebrew' :
        // Romanic family
        case 'Italian' :
        case 'Portuguese' :
        case 'Spanish' :
        case 'Catalan' :
        // Artificial
        case 'Esperanto' :
        // Finno-Ugric family
        case 'Hungarian' :
        // ?
        case 'Bulgarian' :
            $pluralForm = 'nplurals=2; plural=n != 1;';
            break;
            
        // Romanic family
        case 'French' :
            $pluralForm = 'nplurals=2; plural=n>1;';
            break;
        case 'Brazilian Portuguese' :
            $pluralForm = 'nplurals=2; plural=n != 1;';
            break;
            
        // Baltic family
        case 'Latvian' :
            $pluralForm = 'nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2;';
            break;
            
        // Celtic
        case 'Gaeilge' :
            $pluralForm = 'nplurals=3; plural=n==1 ? 0 : n==2 ? 1 : 2;';
            break;
            
        // Romanic family
        case 'Romanian' :
            $pluralForm = 'nplurals=3; plural=n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2;';
            break;
            
        // Baltic family
        case 'Lithuanian' :
            $pluralForm = 'nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2;';
            break;
            
        // Slavic family
        case 'Croatian' :
        case 'Serbian' :
        case 'Russian' :
        case 'Ukrainian' :
            $pluralForm = 'nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;';
            break;
            
        // Slavic family
        case 'Slovak' :
        case 'Czech' :
            $pluralForm = 'nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;';
            break;
            
        // Slavic family
        case 'Polish' :
            $pluralForm = 'nplurals=3; plural=n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;';
            break;
        
        // Slavic family
        case 'Slovenian' :
            $pluralForm = 'nplurals=4; plural=n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3;';
            break;
            
        default :
            die ("Error: Plural form of $_languageName is not defined! \n");
            
    }
    return $pluralForm;
}

function gitAdd($_locale)
{
    foreach (Tinebase_Translation::getTranslationDirs() as $dir) {
        if (file_exists("$dir/$_locale.po")) {
            `cd "$dir"
            git add "$dir/$_locale.po"`;
        }
        if (file_exists("$dir/$_locale.mo")) {
            `cd "$dir"
            git add "$dir/$_locale.mo"`;
        }
    }
}