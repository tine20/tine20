<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */



/**
 * primary class to handle translations
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Translation
{
	/**
	 * Layzy loading for {@see getCountryList()}
	 * 
	 * @var array
	 */
	protected static $_countryLists = array();
	
    /**
     * array with translations for applications 
     * - is used in getTranslations to save already initialized translations
     * - 2 dim array -> language / application
     * 
     * @var array
     */
    protected static $_translations = array();
    
    /**
     * List of officially supported languages
     *
     * @var array
     */
    private static $SUPPORTED_LANGS = array(
        'bg',      // Bulgarian            Dimitrina Mileva <d.mileva@metaways.de>
        'cs',      // Czech                Michael Sladek <msladek@brotel.cz>
        'de',      // German               Cornelius Weiss <c.weiss@metaways.de>
        'en',      // English              Cornelius Weiss <c.weiss@metaways.de>
        'es',      // Spanish              Enrique Palacios <enriquepalaciosc@gmail.com>
        'fr',      // French               Rémi Peltier <rpeltier@agglo-clermont.fr>
        //'it',      // Italian              Lidia Panio <lidiapanio@hotmail.com>
        'ja',      // Japanese             Yuuki Kitamura <ykitamura@clasi-co.jp>
        'nb',      // Norwegian Bokmål     Ronny Gonzales <gonzito@online.no>
        //'nl',      // Dutch                Joost Venema <post@joostvenema.nl>
        'pl',      // Polish               Wojciech Kaczmarek <wojciech_kaczmarek@wp.pl>
        //'pt',      // Portuguese           Holger Rothemund <holger@rothemund.org>
        'ru',      // Russian              Nikolay Parukhin <parukhin@gmail.com> 
        'zh_CN',   // Chinese Simplified   Jason Qi <qry@yahoo.com>
        'zh_TW',   // Chinese Traditional  Frank Huang <frank.cchuang@gmail.com>
    );
    
    /**
     * returns list of all available translations
     * 
     * NOTE available are those, having a Tinebase translation
     * 
     * @return array list of all available translation
     *
     * @todo add test
     */
    public static function getAvailableTranslations()
    {
        $availableTranslations = array();
        
        if (TINE20_BUILDTYPE == 'RELEASE') {
            $list = self::$SUPPORTED_LANGS;
        } else {
            // look for po files in Tinebase
            $dirContents = scandir(dirname(__FILE__) . '/translations');
            sort($dirContents);
            $list = array();
            
            foreach ($dirContents as $poFile) {
                list ($localestring, $suffix) = explode('.', $poFile);
                if ($suffix == 'po') {
                    $list[] = $localestring;
                }
            }
        }
        
        foreach ($list as $localestring) {
            $locale = new Zend_Locale($localestring);
            $availableTranslations[] = array(
                'locale'   => $localestring,
                'language' => Zend_Locale::getTranslation($locale->getLanguage(), 'language', $locale),
                'region'   => Zend_Locale::getTranslation($locale->getRegion(), 'country', $locale)
            );
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
    	
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " given localeString '$_localeString'");
        try {
            $locale = new Zend_Locale($_localeString);
            
            // check if we suppot the locale
            $supportedLocales = array();
            $availableTranslations = self::getAvailableTranslations();
            foreach ($availableTranslations as $translation) {
                $supportedLocales[] = $translation['locale'];
            }
            
            if (! in_array($_localeString, $supportedLocales)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " '$locale' is not supported, checking fallback");
                
                // check if we find suiteable fallback
                $language = $locale->getLanguage();
                switch ($language) {
                    case 'zh':
                        $locale = new Zend_Locale('zh_CN');
                        break;
                    default: 
                        if (in_array($language, $supportedLocales)) {
                            $locale = new Zend_Locale($language);
                        } else {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " no suiteable lang fallback found within this locales: " . print_r($supportedLocales, true) );
                            throw new Tinebase_Exception_NotFound('No suiteable lang fallback found.');
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $e, falling back to locale en");
            $locale = new Zend_Locale('en');
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " selected locale: '$locale'");
        return $locale;
    }
    
    /**
     * get zend translate for an application
     * 
     * @param  string $_applicationName
     * @param  Zend_Locale $_locale [optional]
     * @return Zend_Translate
     * 
     * @todo return 'void' if locale = en
    */
    public static function getTranslation($_applicationName, Zend_Locale $_locale = NULL)
    {
        $locale = ($_locale !== NULL) ? $_locale : Tinebase_Core::get('locale');
        
        // check if translation exists
        if (isset(self::$_translations[(string)$locale][$_applicationName])) {

            // use saved translation
            $translate = self::$_translations[(string)$locale][$_applicationName];
            
        } else {
            
            // create new translation
            $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ucfirst($_applicationName) . DIRECTORY_SEPARATOR . 'translations';
            $translate = new Zend_Translate('gettext', $path, null, array('scan' => Zend_Translate::LOCALE_FILENAME));

            try {
                $translate->setLocale($locale);
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' locale used: ' . (string)$locale);
                
            } catch (Zend_Translate_Exception $e) {
                // the locale of the user is not available
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' locale not found: ' . (string)$locale);
            }
            
            self::$_translations[(string)$locale][$_applicationName] = $translate;
        }
        
        return $translate;
    }
    
    /**
     * Returns collection of all javascript translations data for requested language
     * 
     * This is a javascript spechial function!
     * The data will be preseted to be included as javascript on client side!
     *
     * NOTE: This functino is called from release.php cli script. In this case no 
     *       tine 2.0 core initialisation took place beforehand
     *       
     * @param  Zend_Locale $_locale
     * @return string      javascript
     */
    public static function getJsTranslations($_locale)
    {
        $baseDir = dirname(__FILE__) . "/..";
        $localeString = (string) $_locale;
        
        $genericTranslationFile = "$baseDir/Tinebase/js/Locale/static/generic-$localeString.js";
        $extjsTranslationFile   = "$baseDir/library/ExtJS/src/locale/ext-lang-$localeString.js";
        $tine20TranslationFiels = self::getPoTranslationFiles($_locale);
        
        $allTranslationFiles    = array_merge(array($genericTranslationFile, $extjsTranslationFile), $tine20TranslationFiels);
        
        $jsTranslations = NULL;
        
        if (Tinebase_Core::get(Tinebase_Core::CACHE)) {
            // setup cache (saves about 20% @2010/01/28)
            $cache = new Zend_Cache_Frontend_File(array(
                'master_files' => $allTranslationFiles
            ));
            $cache->setBackend(Tinebase_Core::get(Tinebase_Core::CACHE)->getBackend());
            
            $cacheId = __CLASS__ . "_". __FUNCTION__ . "_{$localeString}";
            
            $jsTranslations = $cache->load($cacheId);
        }
        
        if (! $jsTranslations) {
            $jsTranslations  = "/************************** generic translations **************************/ \n";
            $jsTranslations .= file_get_contents("$baseDir/Tinebase/js/Locale/static/generic-$localeString.js");
            
            $jsTranslations  .= "/*************************** extjs translations ***************************/ \n";
            if (file_exists("$baseDir/library/ExtJS/src/locale/ext-lang-$localeString.js")) {
                $jsTranslations  .= file_get_contents("$baseDir/library/ExtJS/src/locale/ext-lang-$localeString.js");
            } else {
                $jsTranslations  .= "console.error('Translation Error: extjs chaged their lang file name again ;-(');";
            }
            
            $poFiles = self::getPoTranslationFiles($_locale);
            foreach ($poFiles as $appName => $poPath) {
                $poObject = self::po2jsObject($poPath);
                $jsTranslations  .= "/********************** tine translations of $appName**********************/ \n";
                $jsTranslations .= "Locale.Gettext.prototype._msgs['./LC_MESSAGES/$appName'] = new Locale.Gettext.PO($poObject); \n";
            }
            
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
     * @param  Zend_Locale $_locale
     * @return array appName => pofile path
     */
    public static function getPoTranslationFiles($_locale)
    {
        $localeString = (string)$_locale;
        $poFiles = array();
        
        $translationDirs = self::getTranslationDirs();
        foreach ($translationDirs as $appName => $translationDir) {
            $poPath = "$translationDir/$localeString.po";
            if (file_exists($poPath)) {
                $poFiles[$appName] = $poPath;
            }
        }
        
        return $poFiles;
    }
    
    /**
     * convertes po file to js object
     * 
     * @todo rewrite this in a way that we can automatically add singulars
     *       seperatly into the js output
     *
     * @param  string $filePath
     * @return string
     */
    public static function po2jsObject($filePath)
    {
        $po = file_get_contents($filePath);
        
        global $first, $plural;
        $first = true; 
        $plural = false;
        
        $po = preg_replace('/\r?\n/', "\n", $po);
        $po = preg_replace('/#.*\n/', '', $po);
        // 2008-08-25 \s -> \n as there are situations when whitespace like space breaks the thing!
        $po = preg_replace('/"(\n+)"/', '', $po);
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
    }
    
    /**
     * convert zend date to string
     * 
     * @param Zend_Date $_date [optional]
     * @param string $_timezone [optional]
     * @param Zend_Locale $_locale [optional]
     * @return string
     */
    public static function dateToStringInTzAndLocaleFormat(Zend_Date $_date = NULL, $_timezone = NULL, Zend_Locale $_locale = NULL)
    {
        $date = ($_date !== NULL) ? clone($_date) : Zend_Date::now();
        $timezone = ($_timezone !== NULL) ? $_timezone : Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        $locale = ($_locale !== NULL) ? $_locale : Tinebase_Core::get(Tinebase_Core::LOCALE);
        
        $date->setTimezone($timezone);
        $result = $date->toString(Zend_Locale_Format::getDateFormat($locale), $locale) . ' ' .
            $date->toString(Zend_Locale_Format::getTimeFormat($locale), $locale);
            
        return $result;
    }
}
