<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */


/**
 * returns one value of an array, indentified by its key
 *
 * @param mixed $_key
 * @param array $_array
 * @return mixed
 */
function array_value($_key, array $_array)
{
    return array_key_exists($_key, $_array) ? $_array[$_key] : NULL;
}

/**
 * converts string with M or K to bytes integer
 * - for example: 50M -> 52428800
 *
 * @param mixed $_value
 * @return integer
 */
function convertToBytes($_value)
{
    if (is_int($_value) || ! $_value) {
        $bytes = $_value;
    } else {
        if (preg_match("/M/", $_value)) {
            $value = substr($_value, 0, strpos($_value, 'M'));
            $factor = 1024 * 1024;
        } elseif (preg_match("/K/", $_value)) {
            $value = substr($_value, 0, strpos($_value, 'K'));
            $factor = 1024;
        } elseif (is_string($_value)) {
            $value = $_value;
            $factor = 1;
        } else {
            throw new Exception('Argument type not supported:' . gettype($_value));
        }
        $bytes = intval($value) * $factor;
    }
    
    return $bytes;
}

/**
 * converts value to megabytes
 * 
 * @param integer $_value
 * @return integer
 */
function convertToMegabytes($_value)
{
    $result = ($_value) ? round($_value / 1024 / 1024) : $_value;
    return $result;
}

/**
 * get svn revision info
 *
 * @return string
 */
function getDevelopmentRevision()
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
 * converts cache id
 * cache id strings can only contain the chars [a-zA-Z0-9_]
 * 
 * @param string $_cacheId
 * @return string
 */
function convertCacheId($_cacheId) 
{
    $result = preg_replace('/[^a-z^A-Z^0-9^_]/', '', $_cacheId);

    return $result;
}

/**
 * recursive deleting of directory and all containing files
 * 
 * @param string $_dir
 * @return void
 */
function removeDir($_dir)
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_dir), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $fullFilename => $cur) {
        if (is_dir($fullFilename) && substr($fullFilename, -1) !== '.') {
            rmdir($fullFilename);
        } elseif (is_file($fullFilename)) {
            unlink($fullFilename);
        }
    }
    if (is_dir($_dir)) rmdir($_dir);
}

/**
 * replaces and/or strips special chars from given string
 *
 * @param string $_input
 * @return string
 */
function replaceSpecialChars($_input)
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
function in_array_case($_arr, $_str)
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
function mbConvertTo($string, $encodingTo = 'utf-8')
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
function normalizeLineBreaks($string)
{
    if (is_string($string)) {
        $result = str_replace(array("\r\n", "\r"), "\n", $string);
    } else {
        $result = $string;
    }
    
    return $result;
}

/**
 * get clone to be used with right hand side operator
 *
 * @param  Object $obj
 * @return Object
 */
function getClone($obj)
{
    return clone $obj;
}
