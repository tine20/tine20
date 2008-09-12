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
     * returns list of all available translations
     * NOTE available are those, having a Tinebase translation
     * 
     * @return array list of all available translation 'localecode' => localised lang name
     *
     */
    public static function getAvailableTranslations()
    {
        $availableTranslations = array();
        
        // look for po files in Tinebase an fill in en.po virtually
        $dirContents = scandir(dirname(__FILE__) . '/translations');
        array_push($dirContents, 'en.po');
        sort($dirContents);
        
        foreach ($dirContents as $poFile) {
            list ($localestring, $suffix) = explode('.', $poFile);
            if ($suffix == 'po') {
                $locale = new Zend_Locale($localestring);
                $availableTranslations[] = array(
                    'locale'   => $localestring,
                    'language' => $locale->getLanguageTranslation($locale->getLanguage()),
                    'region'   => $locale->getCountryTranslation($locale->getRegion())
                );
            }
        }
        return $availableTranslations;
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
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ucfirst($_applicationName) . DIRECTORY_SEPARATOR . 'translations';
        
        $translate = new Zend_Translate('gettext', $path, null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        try {
            $translate->setLocale(Zend_Registry::get('locale'));
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' locale used: ' . (string)Zend_Registry::get('locale'));
            
        } catch (Zend_Translate_Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' locale not found: ' . (string)Zend_Registry::get('locale'));
            // the locale of the user is not available
        }
        return $translate;
    }
    
    /**
     * returns the available language java script from a given locale
     * 
     * @param  Zend_Locale        $_locale
     * @param  string             $_location required location on of {generic|tine|ext}
     * @return string             filepath relative to tine installation
     */
    public static function getJsTranslationFile($_locale, $_location='tine')
    {
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
        
        $locale = (string)$_locale;
        $language = $_locale->getLanguage();
        
        if (! $_locale instanceof Zend_Locale) {
            $_locale = new Zend_Locale($_locale);
        }
        
        $file = $dir . $prefix . $locale . $suffix;
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

    /**
     * creates translation lists js files for locale with js object
     *
     * @param   string $_locale
     * @return  string the file contents
     */
    public static function createJsTranslationLists($_locale)
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
    private static function convertIsoToPhpFormat($format)
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

    
}