<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Helper class
 *
 * @package     Tinebase
 */
class Tinebase_Helper
{
    /**
     * returns one value of an array, indentified by its key
     *
     * @param mixed $_key
     * @param array $_array
     * @return mixed
     */
    public static function array_value($_key, array $_array)
    {
        return (isset($_array[$_key]) || array_key_exists($_key, $_array)) ? $_array[$_key] : NULL;
    }
    
    /**
     * Generates a hash from keys(optional) and values of an array
     *
     * @param  array | string  $value
     * @param  boolean         $includeKeys
     * @return string
     */
    public static function arrayHash($values, $includeKeys = false)
    {
        $ctx = hash_init('md5');
        
        foreach ((array)$values as $id => $value) {
            if (is_array($value)) {
                $value = self::arrayHash($value, $includeKeys);
            }
            
            hash_update($ctx, ($includeKeys ? $id : null) . (string)$value);
        }
        
        return hash_final($ctx);
    }

    /**
     * converts string with M or K to bytes integer
     * - for example: 50M -> 52428800
     *
     * @param mixed $_value
     * @return integer
     */
    public static function convertToBytes($value)
    {
        $powers = 'KMGTPEZY';
        $power = 0;

        if (preg_match('/(\d+)\s*([' . $powers . '])/', $value, $matches)) {
            $value = $matches[1];
            $power = strpos($powers, $matches[2]) + 1;
        }

        return (int)$value * pow(1024, $power);
    }
    
    /**
     * converts value to megabytes
     * 
     * @param integer $_value
     * @return integer
     */
    public static function convertToMegabytes($_value)
    {
        $result = ($_value) ? round($_value / 1024 / 1024) : $_value;
        
        return $result;
    }
    
    /**
     * get svn revision info
     *
     * @return string
     */
    public static function getDevelopmentRevision()
    {
        $branch = '';
        $rev = 0;
        $date = '';
        
        try {
            // try to find the .git dir
            $dir = realpath(dirname(dirname(dirname(__FILE__)))) . '/.git';
            if (file_exists($dir)) {
                $HEAD = trim(str_replace('ref: ', '', @file_get_contents("$dir/HEAD")));
                $explodedHead = explode('/', $HEAD);
                
                $branch = array_pop($explodedHead);
                $rev = trim(@file_get_contents("$dir/$HEAD"));
                
                $hashes = str_split($rev, 2);
    
                $objPath = "$dir/objects";
                while (count($hashes) != 0) {
                    $objPath .= '/' . array_shift($hashes);
                    $objFile = "$objPath/" . implode('', $hashes);
                    if (@file_exists($objFile)) {
                        $date = date_create('@' . filemtime($objFile))->format('Y-m-d H:i:s');
                        break;
                    }
                }
            }
            $revision = "$branch: $rev ($date)";
        } catch (Exception $e) {
            $revision = 'not resolvable';
        }
        
        return $revision;
    }
    
    /**
     * get release Codename
     *
     * @return string
     */
    public static function getCodename()
    {
        return 'Koriander';
    }
    
    /**
     * converts cache id
     * cache id strings can only contain the chars [a-zA-Z0-9_]
     * 
     * @param string $_cacheId
     * @return string
     */
    public static function convertCacheId($_cacheId) 
    {
        $result = preg_replace('/[^a-z^A-Z^0-9^_]/', '', $_cacheId);
        
        return $result;
    }
    
    /**
     * replaces and/or strips special chars from given string
     *
     * @param string $_input
     * @return string
     */
    public static function replaceSpecialChars($_input)
    {
        $search  = array('ä',  'ü',  'ö',  'ß',  'é', 'è', 'ê', 'ó' ,'ô', 'á', 'ź', 'Ä',  'Ü',  'Ö',  'É', 'È', 'Ê', 'Ó' ,'Ô', 'Á', 'Ź');
        $replace = array('ae', 'ue', 'oe', 'ss', 'e', 'e', 'e', 'o', 'o', 'a', 'z', 'Ae', 'Ue', 'Oe', 'E', 'E', 'E', 'O', 'O', 'a', 'z');
        
        $output = str_replace($search, $replace, $_input);
        
        return preg_replace('/[^a-zA-Z0-9._\-]/', '', $output);
    }
    
    /**
     * Checks if needle $_str is in haystack $_arr but ignores case.
     * 
     * @param array $_arr
     * @param string $_str
     * @return boolean
     */
    public static function in_array_case($_arr, $_str)
    {
        if (! is_array($_arr)) {
            return false;
        }
        
        foreach ($_arr as $s) {
            if (strcasecmp($_str, $s) == 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * try to convert string to utf8 using mb_convert_encoding
     * 
     * @param string $string
     * @param string $encodingTo (default: utf-8)
     * @return string
     */
    public static function mbConvertTo($string, $encodingTo = 'utf-8')
    {
        if (! extension_loaded('mbstring')) {
            return $string;
        }
        // try to fix bad encodings
        $encoding = mb_detect_encoding($string, array('utf-8', 'iso-8859-1', 'windows-1252', 'iso-8859-15'));
        if ($encoding !== FALSE) {
            $string = @mb_convert_encoding($string, $encodingTo, $encoding);
        }
        
        return $string;
    }
    
    /**
     * converts all linebreaks to unix linebreaks
     *
     * @param string $string
     * @return string
     */
    public static function normalizeLineBreaks($string)
    {
        if (is_string($string)) {
            $result = str_replace(array("\r\n", "\r"), "\n", $string);
        } else {
            $result = $string;
        }
        
        return $result;
    }
    
    /**
     * returns all elements of an array whose key matches the $pattern
     * 
     * @param string $pattern
     * @param array $array
     * @return array
     */
    public static function searchArrayByRegexpKey($pattern, $array)
    {
        $keys = array_keys($array);
        $result = preg_grep($pattern, $keys);
        
        return array_intersect_key($array, array_flip($result));
    }
    
    /**
     * checks if a string is valid json
     * 
     * @param string $string
     * @return boolean 
     */
    public static function is_json($string)
    {
        if (! is_string($string) || (substr($string, 0, 1) !== '[' && substr($string, 0, 1) !== '{')) {
            return false;
        }
        
        try {
            Zend_Json::decode($string);
        } catch (Exception $e) {
            return false;
        }
        
        // check if error occured (only if json_last_error() exists)
        return (! function_exists('json_last_error') || json_last_error() == JSON_ERROR_NONE);
    }
    
    /**
     * formats a microtime diff to an appropriate string containing time unit based on the value range
     * 
     * value ranges
     * below 1s return ms
     * below 1m return s
     * below 1h return m
     * as of 1h return h
     * 
     * @param float $timediff
     * @return string
     */
    public static function formatMicrotimeDiff($timediff)
    {
        $ms = (int)($timediff * 1000);
        if ($ms>=3600000) {
            return (((int)($ms / 3600000 * 100.0)) / 100.0).'h';
        } elseif ($ms>=60000) {
            return (((int)($ms / 60000 * 100.0)) / 100.0).'m';
        } elseif ($ms>=1000) {
            return (((int)($ms / 1000 * 100.0)) / 100.0).'s';
        } else {
            return $ms.'ms';
        }
    }
    
    /**
     * checks if an url exists by checking headers 
     * -> inspired by http://php.net/manual/en/function.file-exists.php#75064
     * 
     * @param string $url
     * @return boolean
     */
    public static function urlExists($url)
    {
        $file_headers = @get_headers($url);
        
        if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
            $exists = false;
        } else {
            $exists = true;
        }
        
        return $exists;
    }
}