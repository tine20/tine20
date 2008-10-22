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
        'bg',      // Bulgarian            Dimitrina Mileva <dimitrina@gmx.de>
        'cs',      // Czech                Michael Sladek <msladek@brotel.cz>
        'de',      // German               Cornelius Weiss <c.weiss@metaways.de>
        'en',      // English              Cornelius Weiss <c.weiss@metaways.de>
        //'fr',      // Frensch              Wilfried Maurin <aiouto2@gmail.com>
        //'it',      // Italian              Lidia Panio <lidiapanio@hotmail.com>
        //'pl',      // Polish               Chrisopf Gacki <c.gacki@metaways.de>
        'ru',      // Russian              Ilia Yurkovetskiy <i.yurkovetskiy@metaways.de>
        'zh_CN',   // Chinese Simplified   Jason Qi <qry@yahoo.com>
    );
    
    /**
     * returns list of all available translations
     * NOTE available are those, having a Tinebase translation
     * 
     * @return array list of all available translation 'localecode' => localised lang name
     *
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
                'language' => $locale->getLanguageTranslation($locale->getLanguage()),
                'region'   => $locale->getCountryTranslation($locale->getRegion())
            );
        }
            
        return $availableTranslations;
    }
    
    /**
     * gets a supported locale
     *
     * @param string $_localeString
     * @return Zend_Locale
     */
    public static function getLocale($_localeString = 'auto')
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " given localeString '$_localeString'");
        try {
            $locale = new Zend_Locale($_localeString);
            
            // check if we suppot the locale
            $supportedLocales = array();
            $availableTranslations = self::getAvailableTranslations();
            foreach ($availableTranslations as $translation) {
                $supportedLocales[] = $translation['locale'];
            }
            
            if (! in_array($_localeString, $supportedLocales)) {
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " '$locale' is not supported, checking fallback");
                
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
                            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " no suiteable lang fallback found within this locales: " . print_r($supportedLocales, true) );
                            throw new Exception('no suiteable lang fallback found');
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " $e, falling back to locale en");
            $locale = new Zend_Locale('en');
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " selected locale: '$locale'");
        return $locale;
    }
    
    /**
     * get zend translate for an application
     * 
     * @param  string $_applicationName
     * @return Zend_Translate
     * 
     * @todo return 'void' if locale = en
    */
    public static function getTranslation($_applicationName)
    {
        $locale = Zend_Registry::get('locale');
        
        // check if translation exists
        if (isset(self::$_translations[(string)$locale][$_applicationName])) {

            // use saved translation
            $translate = self::$_translations[(string)$locale][$_applicationName];

            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' use saved translation for: ' . $_applicationName);
            
        } else {
            
            // create new translation
            $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ucfirst($_applicationName) . DIRECTORY_SEPARATOR . 'translations';
            $translate = new Zend_Translate('gettext', $path, null, array('scan' => Zend_Translate::LOCALE_FILENAME));

            try {
                $translate->setLocale($locale);
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' locale used: ' . (string)$locale);
                
            } catch (Zend_Translate_Exception $e) {
                // the locale of the user is not available
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' locale not found: ' . (string)$locale);
            }
            
            self::$_translations[(string)$locale][$_applicationName] = $translate;
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' created new translation for: ' . $_applicationName);
        }
        
        return $translate;
    }
    
    /**
     * Returns collection of all javascript translations data for requested language
     * 
     * This is a javascript spechial function!
     * The data will be preseted to be included as javascript on client side!
     *
     * @param  Zend_Locale $_locale
     * @return string      javascript
     */
    public static function getJsTranslations($_locale)
    {
        $baseDir = dirname(__FILE__) . "/..";
        $localeString = (string) $_locale;
        
        $jsTranslations  = "/************************** generic translations **************************/ \n";
        $jsTranslations .= file_get_contents("$baseDir/Tinebase/js/Locale/static/generic-$localeString.js");
        
        $jsTranslations  .= "/*************************** extjs translations ***************************/ \n";
        $jsTranslations  .= file_get_contents("$baseDir/ExtJS/build/locale/ext-lang-$localeString.js");
        
        $poFiles = self::getPoTranslationFiles($_locale);
        foreach ($poFiles as $appName => $poPath) {
            $poObject = self::po2jsObject($poPath);
            $jsTranslations  .= "/********************** tine translations of $appName**********************/ \n";
            $jsTranslations .= "Locale.Gettext.prototype._msgs['./LC_MESSAGES/$appName'] = new Locale.Gettext.PO($poObject); \n";
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
            if (is_dir($appPath) && $appName{0} != '.') {
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
     * returns the available language java script from a given locale
     * 
     * @deprecated 
     * 
     * @param  Zend_Locale        $_locale
     * @param  string             $_location required location on of {generic|tine|ext}
     * @return string             filepath relative to tine installation
     */
    public static function getJsTranslationFile($_locale, $_location='tine')
    {
        $localeString = (string)$_locale;
        if (! $_locale instanceof Zend_Locale) {
            $_locale = new Zend_Locale($_locale);
        }
        
        switch ($_location) {
        	case 'generic':
        	   $dir = 'Tinebase/js/Locale/static/';
        	   $prefix = 'generic-';
        	   $suffix = '.js';
        	   break;
        	case 'tine':
        	   $dir = 'Tinebase/js/Locale/build/';
               $prefix = '';
               $suffix = '.js';
               break;
        	case 'ext':
        	   $dir = 'ExtJS/build/locale/';
               $prefix = 'ext-lang-';
               $suffix = '.js';
               break;
        	default:
        		throw new Exception('no such location');
        	   break;
        }
        
        
        $language = $_locale->getLanguage();
        
        $file = $dir . $prefix . $localeString . $suffix;
        if (file_exists(dirname(__FILE__) . "/../$file")) {
            return $file;
        }
        
        $file = $dir . $prefix . $language . $suffix;
        
        if (file_exists(dirname(__FILE__) . "/../$file")) {
            return $file;
        }
        
        // fallback
        return $dir . $prefix . 'en' . $suffix;
    }
    
    /**
     * convertes po file to js object
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
}