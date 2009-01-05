<?php
/**
 * csv generation class
 *
 * @package     Tinebase
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * defines the datatype for simple registration object
 * 
 * @package     Tinebase
 * @subpackage	Export
 * 
 */
class Tinebase_Export_Csv
{

    /**
     * The php build in fputcsv function is buggy, so we need an own one :-(
     *
     * @param resource $filePointer
     * @param array $dataArray
     * @param char $delimiter
     * @param char $enclosure
     */
    public static function fputcsv($filePointer, $dataArray, $delimiter=',', $enclosure=''){
        $string = "";
        $writeDelimiter = false;
        foreach($dataArray as $dataElement) {
            if($writeDelimiter) $string .= $delimiter;
            $string .= $enclosure . $dataElement . $enclosure;
            $writeDelimiter = true;
        } 
        $string .= "\n";
        
        fwrite($filePointer, $string);
    }
}
