<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Translation
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * primary class to handle translations
 *
 * @package     Tinebase
 * @subpackage  Translation
 */
class Tinebase_Translation
{
    /**
     * Lazy loading for {@see getCountryList()}
     * 
     * @var array
     */
    protected static $_countryLists = array();
    
    /**
     * cached instances of Zend_Translate
     * 
     * @var array
     */
    protected static $_applicationTranslations = array();
    
    /**
     * returns list of all available translations
     * 
     * NOTE available are those, having a Tinebase translation
     * 
     * @return array list of all available translation
     */
    public static function getAvailableTranslations($appName = 'Tinebase')
    {
        $availableTranslations = array();

        // look for po files in Tinebase 
        $officialTranslationsDir = dirname(__FILE__) . "/../$appName/translations";
        foreach(scandir($officialTranslationsDir) as $poFile) {
            if (substr($poFile, -3) == '.po') {
                list ($localestring, $suffix) = explode('.', $poFile);
                $availableTranslations[$localestring] = array(
                    'path' => "$officialTranslationsDir/$poFile" 
                );
            }
        }
        
        $filesToWatch = array();
        
        // compute information
        foreach ($availableTranslations as $localestring => $info) {
            if (! Zend_Locale::isLocale($localestring, TRUE, FALSE)) {
                unset($availableTranslations[$localestring]);
                continue;
            }
            
            $filesToWatch[] = $info['path'];
        }
        
        if (Tinebase_Config::isReady()) {
            $cache = new Zend_Cache_Frontend_File(array(
                'master_files' => $filesToWatch
            ));
            $tineCache = Tinebase_Core::get(Tinebase_Core::CACHE);
            if ($tineCache) {
                $cache->setBackend($tineCache->getBackend());
            } else {
                $cache = null;
            }
        } else {
            $cache = null;
        }
        
        if ($cache) {
            $cacheId = Tinebase_Helper::convertCacheId(__FUNCTION__ . $appName . sha1(serialize($filesToWatch)));
            $cache = new Zend_Cache_Frontend_File(array(
                'master_files' => $filesToWatch
            ));
            $cache->setBackend(Tinebase_Core::get(Tinebase_Core::CACHE)->getBackend());
            
            if ($cachedTranslations = $cache->load($cacheId)) {
                $cachedTranslations = unserialize($cachedTranslations);
                
                if ($cachedTranslations !== null) {
                    return $cachedTranslations;
                }
            }
        }
        
        // compute information
        foreach ($availableTranslations as $localestring => $info) {
            // fetch header grep for X-Poedit-Language, X-Poedit-Country
            $fh = fopen($info['path'], 'r');
            $header = fread($fh, 1024);
            fclose($fh);
            
            preg_match('/X-Tine20-Language: (.+)(?:\\\\n?)(?:"?)/', $header, $language);
            preg_match('/X-Tine20-Country: (.+)(?:\\\\n?)(?:"?)/', $header, $region);
            
            $locale = new Zend_Locale($localestring);
            $availableTranslations[$localestring]['locale'] = $localestring;
            $availableTranslations[$localestring]['language'] = isset($language[1]) ? 
                $language[1] : Zend_Locale::getTranslation($locale->getLanguage(), 'language', $locale);
            $availableTranslations[$localestring]['region'] = isset($region[1]) ? 
                $region[1] : Zend_Locale::getTranslation($locale->getRegion(), 'country', $locale);
        }

        ksort($availableTranslations);
        
        if ($cache) {
            $cache->save(serialize($availableTranslations), $cacheId, array(), /* 1 day */ 86400);
        }
        
        return $availableTranslations;
    }
    
    /**
     * get list of translated country names
     *
     * @return array list of countrys
     */
    public static function getCountryList()
    {
        $locale = Tinebase_Core::get('locale');
        $language = $locale->getLanguage();
        
        //try lazy loading of translated country list
        if (empty(self::$_countryLists[$language])) {
            $countries = Zend_Locale::getTranslationList('territory', $locale, 2);
            asort($countries);
            foreach($countries as $shortName => $translatedName) {
                $results[] = array(
                    'shortName'         => $shortName, 
                    'translatedName'    => $translatedName
                );
            }
    
            self::$_countryLists[$language] = $results;
        }

        return array('results' => self::$_countryLists[$language]);
    }
    
    /**
     * Get translated country name for a given ISO {@param $_regionCode}
     * 
     * @param String $regionCode [e.g. DE, US etc.]
     * @return String | null [e.g. Germany, United States etc.]
     */
    public static function getCountryNameByRegionCode($_regionCode)
    {
        $countries = self::getCountryList();
        foreach($countries['results'] as $country) {
            if ($country['shortName'] === $_regionCode) {
                return $country['translatedName'];
            }
        } 

        return null;
    }
    
    /**
     * Get translated country name for a given ISO {@param $_regionCode}
     * 
     * @param String $regionCode [e.g. DE, US etc.]
     * @return String | null [e.g. Germany, United States etc.]
     */
    public static function getRegionCodeByCountryName($_countryName)
    {
        $countries = self::getCountryList();
        foreach($countries['results'] as $country) {
            if ($country['translatedName'] === $_countryName) {
                return $country['shortName'];
            }
        } 

        return null;
    }
    
    /**
     * gets a supported locale
     *
     * @param   string $_localeString
     * @return  Zend_Locale
     * @throws  Tinebase_Exception_NotFound
     */
    public static function getLocale($_localeString = 'auto')
    {
        Zend_Locale::$compatibilityMode = false;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " given localeString '$_localeString'");
        try {
            $locale = new Zend_Locale($_localeString);
            
            // check if we support the locale
            $supportedLocales = array();
            // @todo: maybe allow to pass application here?
            $availableTranslations = self::getAvailableTranslations();
            foreach ($availableTranslations as $translation) {
                $supportedLocales[] = $translation['locale'];
            }
            
            if (! in_array($_localeString, $supportedLocales)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " '$locale' is not supported, checking fallback");
                
                // check if we find suitable fallback
                $language = $locale->getLanguage();
                switch ($language) {
                    case 'zh':
                        $locale = new Zend_Locale('zh_CN');
                        break;
                    default: 
                        if (in_array($language, $supportedLocales)) {
                            $locale = new Zend_Locale($language);
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " no suiteable lang fallback found within this locales: " . print_r($supportedLocales, true) );
                            throw new Tinebase_Exception_NotFound('No suiteable lang fallback found.');
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                ' ' . $e->getMessage() . ', falling back to locale en.');
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . 
                ' ' . $e->getTraceAsString());
            $locale = new Zend_Locale('en');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " selected locale: '$locale'");
        return $locale;
    }
    
    /**
     * get zend translate for an application
     * 
     * @param  string $_applicationName
     * @param  Zend_Locale $_locale [optional]
     * @return Zend_Translate_Adapter
     */
    public static function getTranslation($_applicationName = 'Tinebase', Zend_Locale $_locale = NULL)
    {
        $locale = $_locale
            ?: Tinebase_Core::getLocale()
            ?: (Tinebase_Config::getInstance()->get(Tinebase_Config::DEFAULT_LOCALE, 'en'));
        
        $cacheId = (string) $locale . $_applicationName;
        
        // get translation from internal class member?
        if ((isset(self::$_applicationTranslations[$cacheId]) || array_key_exists($cacheId, self::$_applicationTranslations))) {
            return self::$_applicationTranslations[$cacheId];
        }
        
        $translationFiles = self::getPoTranslationFiles(array('locale' => (string) $locale), $_applicationName);

        // create new translation
        $adapter = defined('TINE20_BUILDTYPE') && TINE20_BUILDTYPE != 'DEVELOPMENT' ? 'gettext' : 'gettextPo';
        $translate = new Zend_Translate($adapter, array(), (string)$locale, $options = array(
            'disableNotices' => true
        ));

        foreach ($translationFiles as $appName => $translationFiles) {
            foreach ($translationFiles as $translationFile) {
                $filename = $adapter == 'gettext' ? str_replace('.po', '.mo', $translationFile) : $translationFile;
                try {
                    $translate->getAdapter()->addTranslation(array(
                        'content' => $filename,
                        'locale' => (string)$locale
                    ));
                } catch (Zend_Translate_Exception $zte) {
                    // skip translation file
                    Tinebase_Exception::log($zte);
                }
            }
        }

        self::$_applicationTranslations[$cacheId] = $translate;
        
        return $translate;
    }
    
    /**
     * Returns collection of all javascript translations data for requested language
     * 
     * This is a javascript special function!
     * The data will be preseted to be included as javascript on client side!
     *
     * NOTE: This function is called from release.php cli script. In this case no 
     *       tine 2.0 core initialisation took place beforehand
     *       
     * @param  Zend_Locale|string $_locale
     * @return string      javascript
     */
    public static function getJsTranslations($_locale, $_appName = 'all')
    {
        $locale = ($_locale instanceof Zend_Locale) ? $_locale : new Zend_Locale($_locale);
        $localeString = (string) $_locale;
        
        $availableTranslations = self::getAvailableTranslations();
        $info = (isset($availableTranslations[$localeString]) || array_key_exists($localeString, $availableTranslations)) ? $availableTranslations[$localeString] : array('locale' => $localeString);
        $baseDir = ((isset($info['path']) || array_key_exists('path', $info)) ? dirname($info['path']) . '/..' : dirname(__FILE__)) . '/..';
        
        $defaultDir = dirname(__FILE__) . "/..";
        
        $genericTranslationFile = "$baseDir/Tinebase/js/Locale/static/generic-$localeString.js";
        $genericTranslationFile = is_readable($genericTranslationFile) ? $genericTranslationFile : "$defaultDir/Tinebase/js/Locale/static/generic-$localeString.js";
        
        $extjsTranslationFile   = "$baseDir/library/ExtJS/src/locale/ext-lang-$localeString.js";
        $extjsTranslationFile   = is_readable($extjsTranslationFile) ? $extjsTranslationFile : "$defaultDir/library/ExtJS/src/locale/ext-lang-$localeString.js";
        if (! is_readable($extjsTranslationFile)) {
            // trying language as fallback if lang_region file can not be found, @see 0008242: Turkish does not work / throws an error
            $language = $locale->getLanguage();
            $extjsTranslationFile   = "$baseDir/library/ExtJS/src/locale/ext-lang-$language.js";
            $extjsTranslationFile   = is_readable($extjsTranslationFile) ? $extjsTranslationFile : "$defaultDir/library/ExtJS/src/locale/ext-lang-$language.js";
        }

        $allTranslationFiles    = array($genericTranslationFile, $extjsTranslationFile);

        $tine20TranslationFiles = self::getPoTranslationFiles($info, $_appName);
        foreach($tine20TranslationFiles as $appName => $translationFiles) {
            $allTranslationFiles = array_merge($allTranslationFiles, $translationFiles);
        }

        $jsTranslations = NULL;
        
        if (Tinebase_Core::get(Tinebase_Core::CACHE) && $_appName == 'all') {
            // setup cache (saves about 20% @2010/01/28)
            $cache = new Zend_Cache_Frontend_File(array(
                'master_files' => $allTranslationFiles
            ));
            $cache->setBackend(Tinebase_Core::get(Tinebase_Core::CACHE)->getBackend());
            
            $cacheId = __CLASS__ . "_". __FUNCTION__ . "_{$localeString}";
            
            $jsTranslations = $cache->load($cacheId);
        }
        
        if (! $jsTranslations) {
            $jsTranslations  = "/************************** namespace for static data **************************/ \n";
            $jsTranslations .= "Tine = window.Tine || {};\n";
            $jsTranslations .= "Tine.__translationData = Tine.__translationData || {};\n";
            $jsTranslations .= "Tine.__translationData.msgs = Tine.__translationData.msgs || {};\n";

            if (in_array($_appName, array('Tinebase', 'all'))) {
                $jsTranslations .= "/************************** generic translations **************************/ \n";
                
                $jsTranslations .= file_get_contents($genericTranslationFile);
                
                $jsTranslations  .= "/*************************** extjs translations ***************************/ \n";
                if (file_exists($extjsTranslationFile)) {
                    $jsTranslations  .= file_get_contents($extjsTranslationFile);
                } else {
                    $jsTranslations  .= "console.error('Translation Error: extjs changed their lang file name again ;-(');";
                }
            }
            
            foreach ($tine20TranslationFiles as $appName => $poPaths) {
                $poObject = self::po2jsObject($poPaths);
                //if (! json_decode($poObject)) {
                //    $jsTranslations .= "console.err('tanslations for application $appName are broken');";
                //} else {
                    $jsTranslations .= "/********************** tine translations of $appName **********************/ \n";
                    $jsTranslations .= "Tine.__translationData.msgs['./LC_MESSAGES/$appName'] = $poObject; \n";
                //}
            }

            $jsTranslations .= "/********************** translation loaded flag **********************/ \n";
            $jsTranslations .= "Tine.__translationData.__isLoaded = true; \n";

            if (isset($cache)) {
                $cache->save($jsTranslations, $cacheId);
            }
        }
        
        return $jsTranslations;
    }
    
    /**
     * gets array of lang dirs from all applications having translations
     * 
     * Note: This functions must not query the database! 
     *       It's only used in the development and release building process
     * 
     * @return array appName => translationDir
     */
    public static function getTranslationDirs()
    {
        $tine20path = dirname(__File__) . "/..";
        
        $langDirs = array();
        $d = dir($tine20path);
        while (false !== ($appName = $d->read())) {
            $appPath = "$tine20path/$appName";
            if ($appName{0} != '.' && is_dir($appPath)) {
                $translationPath = "$appPath/translations";
                if (is_dir($translationPath)) {
                    $langDirs[$appName] = $translationPath;
                }
            }
        }
        
        return $langDirs;
    }
    
    /**
     * gets all available po files for a given locale
     *
     * @param  array  $info translation info
     * @param  string $applicationName (all for all)
     * @return array appName => array => pofile path
     */
    public static function getPoTranslationFiles($info, $applicationName='all')
    {
        $localeString = $info['locale'];
        $translationDirs = self::getTranslationDirs();
        $poFiles = [];
        foreach ($translationDirs as $appName => $translationDir) {
            if ($applicationName != 'all' && $applicationName != $appName) continue;
            // applications own translation
            $poPaths = ["$translationDir/$localeString.po"];

            // collect extra from other app
            foreach(self::getTranslationDirs() as $extraApp => $extraTranslationDir) {
                $poPaths[] = "$extraTranslationDir/extra/$appName/$localeString.po";
            }
            // check for existance
            foreach($poPaths as $poPath) {
                if (file_exists($poPath)) {
                    $poFiles[$appName][] = $poPath;
                }
            }
        }

        return $poFiles;
    }
    
    /**
     * convertes po file to js object
     *
     * @param  array/string $filePath
     * @return string
     */
    public static function po2jsObject($filePath)
    {
        $filePath = is_array($filePath) ? $filePath : [$filePath];
        $po = '';
        foreach($filePath as $file) {
            $po .= file_get_contents($file);
        }

        global $first, $plural;
        $first = true;
        $plural = false;
        
        $po = preg_replace('/\r?\n/', "\n", $po);
        $po = preg_replace('/^#.*\n/m', '', $po);
        // 2008-08-25 \s -> \n as there are situations when whitespace like space breaks the thing!
        $po = preg_replace('/"(\n+)"/', '', $po);
        // Create a singular version of plural defined words
        preg_match_all('/msgid "(.*?)"\nmsgid_plural ".*"\nmsgstr\[0\] "(.*?)"\n/', $po, $plurals);
        for ($i = 0; $i < count($plurals[0]); $i++) {
            $po = $po . "\n".'msgid "' . $plurals[1][$i] . '"' . "\n" . 'msgstr "' . $plurals[2][$i] . '"' . "\n";
        }
        $po = preg_replace('/msgid "(.*?)"\nmsgid_plural "(.*?)"/', 'msgid "$1, $2"', $po);
        $po = preg_replace_callback(
            '/msg(\S+) /', function($matches) {
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
            }, $po);
                $po = "({\n" . (string)$po . ($plural ? "]\n})" : "\n})");
        return $po;
    }
    
    /**
     * convert date to string
     * 
     * @param Tinebase_DateTime $date [optional]
     * @param string            $timezone [optional]
     * @param Zend_Locale       $locale [optional]
     * @param string            $part one of date, time or datetime [optional]
     * @param boolean           $addWeekday should the weekday be added (only works with $part = 'date[time]') [optional] 
     * @return string
     */
    public static function dateToStringInTzAndLocaleFormat(DateTime $date = null, $timezone = null, Zend_Locale $locale = null, $part = 'datetime', $addWeekday = false)
    {
        $date = ($date !== null) ? clone($date) : Tinebase_DateTime::now();
        $timezone = ($timezone !== null) ? $timezone : Tinebase_Core::getUserTimezone();
        $locale = ($locale !== null) ? $locale : Tinebase_Core::getLocale();
        
        $date = new Zend_Date($date->getTimestamp());
        $date->setTimezone($timezone);

        if (in_array($part, array('date', 'time', 'datetime'))) {
            $dateString = $date->toString(Zend_Locale_Format::getDateFormat($locale), $locale);
            if ($addWeekday) {
                $dateString = $date->toString('EEEE', $locale) . ', ' . $dateString;
            }
            $timeString = $date->toString(Zend_Locale_Format::getTimeFormat($locale), $locale);

            switch ($part) {
                case 'date':
                    return $dateString;
                case 'time':
                    return $timeString;
                default:
                    return $dateString . ' ' . $timeString;
            }
        } else {
            return $date->toString($part, $locale);
        }

    }
}
