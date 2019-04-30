<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * returns one value of an array, identified by its key
     *
     * @param mixed $_key
     * @param array $_array
     * @return mixed
     */
    public static function array_value($_key, array $_array)
    {
        return (isset($_array[$_key]) || array_key_exists($_key, $_array)) ? $_array[$_key] : NULL;
    }

    public static function array_remove_by_value($_value, array $_array)
    {
        return array_values(array_diff($_array, array($_value)));
    }
    
    /**
     * Generates a hash from keys(optional) and values of an array
     *
     * @param  array | string  $values
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
     * @return int
     * @throws Exception
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
     * converts value to human readable unit
     *
     * @param integer $bytes        value to format
     * @param string  $force_unit   force given unit
     * @param string  $format       formatstring for sprintf
     * @param bool    $si           use SI unit
     * @return string
     */
    public static function formatBytes($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
    {
        // Format string
        $format = ($format === NULL) ? '%01.2f %s' : (string) $format;

        // IEC prefixes (binary)
        if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE)
        {
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            $mod   = 1024;
        }
        // SI prefixes (decimal)
        else
        {
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
            $mod   = 1000;
        }

        // Determine unit to use
        if (($power = array_search((string) $force_unit, $units)) === FALSE)
        {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
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
     * Convert array values including keys (optional) to cache Id
     * @param array $values
     * @param boolean $includeKeys
     * @return string
     */
    public static function arrayToCacheId($values, $includeKeys = false)
    {
        $ctx = hash_init('md5');

        $values = (array) $values;

        foreach ($values as $id => $value) {
            if (is_array($value)) {
                $value = self::arrayToCacheId($value, $includeKeys);
            }

            hash_update($ctx, ($includeKeys ? $id : null) . (string)$value);
        }

        return hash_final($ctx);
    }

    /**
     * get release Codename
     *
     * @return string
     */
    public static function getCodename()
    {
        return 'Elena';
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
     * @param bool $_replaceWhitespace
     * @return string
     */
    public static function replaceSpecialChars($_input, $_replaceWhitespace = true)
    {
        $search  = array('ä',  'ü',  'ö',  'ß',  'é', 'è', 'ê', 'ó' ,'ô', 'á', 'ź', 'Ä',  'Ü',  'Ö',  'É', 'È', 'Ê', 'Ó' ,'Ô', 'Á', 'Ź');
        $replace = array('ae', 'ue', 'oe', 'ss', 'e', 'e', 'e', 'o', 'o', 'a', 'z', 'Ae', 'Ue', 'Oe', 'E', 'E', 'E', 'O', 'O', 'a', 'z');
        
        $output = str_replace($search, $replace, $_input);

        if ($_replaceWhitespace) {
            $pattern = '/[^a-zA-Z0-9._\-]/';
        } else {
            $pattern = '/[^a-zA-Z0-9._\-\s]/';
        }
        return preg_replace($pattern, '', $output);
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
        if (! is_string($string)) {
            return false;
        }

        $firstChar = substr($string, 0, 1);
        if ($firstChar !== '[' && $firstChar !== '{' && $firstChar !== '"') {
            return false;
        }
        
        try {
            Zend_Json::decode($string);
        } catch (Exception $e) {
            return false;
        }
        
        // check if error occured (only if json_last_error() exists)
        return (! function_exists('json_last_error') || json_last_error() === JSON_ERROR_NONE);
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

    /**
     * decode json string or
     *
     * Prepare function input to be an array. Input maybe already an array or (empty) text.
     * Starting PHP 7 Zend_Json::decode can't handle empty strings.
     *
     * @param mixed $jsonOrArray
     * @return array
     * @throws Zend_Json_Exception
     */
    public static function jsonDecode($jsonOrArray)
    {
        if (is_array($jsonOrArray)) {
            return $jsonOrArray;
        } else if (empty($jsonOrArray) || trim($jsonOrArray) == '') {
            return array();
        } else {
            try {
                return Zend_Json::decode($jsonOrArray);
            } catch (Zend_Json_Exception $zje) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not json decode: ' . var_export($jsonOrArray, true) . ' - assuming empty array.');
                Tinebase_Exception::log($zje, false);
                return array();
            }
        }
    }

    /**
     * removes characters that are illegal in XML (those characters are not even in CDATA allowed)
     *
     * @param string $string
     * @return string
     */
    public static function removeIllegalXMLChars($string)
    {
        return preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $string);
    }

    /**
     * fetches contents from file or uri
     *
     * @param string $filenameOrUrl
     * @return string|null
     */
    public static function getFileOrUriContents($filenameOrUrl)
    {
        if (strpos($filenameOrUrl, 'http') === 0) {
            try {
                $client = Tinebase_Core::getHttpClient($filenameOrUrl);
                // 0011054: Problems with ScheduledImport of external ics calendars
                // google shows a lot of trouble with gzip in Zend_Http_Response, so let's deny it
                $client->setHeaders('Accept-encoding', 'identity');

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Fetching content from ' . $filenameOrUrl);

                $requestBody = $client->request()->getBody();
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                $requestBody = null;
            }
            return $requestBody;
        }

        $filename = self::getFilename($filenameOrUrl, false);
        return $filename ? file_get_contents($filename) : null;
    }

    /**
     * get filename (might be an url)
     * note: local files are returned with prepended "file://" scheme
     *
     * @param string $filenameOrUrl
     * @param boolean $throwException
     * @return null|string
     * @throws FileNotFoundException
     */
    public static function getFilename($filenameOrUrl, $throwException = true)
    {
        if (strpos($filenameOrUrl, 'http') === 0) {
            // TODO use "real" tempfile?
            // fetch file and save in tempfile
            $content = self::getFileOrUriContents($filenameOrUrl);
            $extension = null;
            if (preg_match('/\.(\w+)$/', $filenameOrUrl, $matches)) {
                $extension = $matches[1];
            }
            $filename = self::writeToTempFile($content, $extension);
        } else {
            $filename = $filenameOrUrl;
            $baseDir = dirname(__DIR__) . '/';

            if (strpos($filename, '://') === false) {
                // relative to tine20 root
                if ('/' !== $filename[0]) {
                    $filename = $baseDir . $filename;
                }

                $filename = 'file://' . $filename;
            }

            if (! file_exists($filename)) {
                if ($throwException) {
                    throw new FileNotFoundException('File ' . $filename . ' not found');
                } else {
                    $filename = null;
                }
            }
        }

        return $filename;
    }

    /**
     * @param $content
     * @param $extension
     * @return null|string filename
     */
    public static function writeToTempFile($content, $extension = null)
    {
        $tempPath = Tinebase_TempFile::getTempPath();
        if ($extension) {
            $tempPath .= '.' . $extension;
        }
        $file = fopen($tempPath, 'w');
        if ($file) {
            fwrite($file, $content);
            return $tempPath;
        }

        return null;
    }
}
