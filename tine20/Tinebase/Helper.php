<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
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
    if (is_int($_value)) {
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
 * get svn revision info
 *
 * @return string
 */
function getDevelopmentRevision()
{
    try {
        $file = fopen(dirname(dirname(__FILE__)) . '/.svn/entries', 'r');
        while ($line = fgets($file)) {
            if ((int)$line > 5000) {
                $rev = (int)$line;
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]+\d{2}:\d{2}:\d{2}/', $line)) {
                $date = trim($line);
            }
            
            if (preg_match('/svn\.tine20\.org\/svn/', $line)) {
                $parts = explode('/', $line);
                $branch = $parts[count($parts)-2];
            }
            if ($branch && $date && $line) {
                break;
            }
        }
        
        $revision = "$branch: $rev ($date)";
    } catch (Exception $e) {
        $revision = 'not resolvable';
    }
    
    return $revision;
}
    
