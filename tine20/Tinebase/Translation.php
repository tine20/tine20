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
    public static function getTranslation($_applicationName)
    {
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ucfirst($_applicationName) . DIRECTORY_SEPARATOR . 'translations';
        
        $translate = new Zend_Translate('gettext', $path, null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        try {
            $translate->setLocale(Zend_Registry::get('locale'));
        } catch (Zend_Translate_Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' locale not found: ' . (string)Zend_Registry::get('locale'));
            // the locale of the user is not available
            // translate with locale en
            $translate->setLocale('en');
        }
        
        return $translate;
    }
    
    /**
     * returns the available language java script from a given locale
     * 
     * @param  Zend_Locale|String $_locale
     * $param  string             $_location required location on of {generic|tine|ext}
     * @return string             filepath relative to tine installation
     */
    public static function getJsTranslationFile($_locale, $_location='tine')
    {
        switch ($_location) {
        	case 'generic':
        	   $dir = 'Tinebase/js/Locale/data/';
        	   $prefix = 'generic-';
        	   $suffix = '.js';
        	   break;
        	case 'tine':
        	   $dir = 'Tinebase/js/';
               $prefix = '';
               $suffix = '.js';
               break;
        	case 'ext':
        	    $dir = '../ExtJS/build/locale/';
               $prefix = 'ext-lang-';
               $suffix = '-min.js';
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
    }
}