<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Interface Tinebase_Export_Convertible
 */
interface Tinebase_Export_Convertible
{
    const PDF = 'pdf';

    /**
     * Convert's given file to defined format.
     * 
     * $from can be defined, to pass a file which is supposed to be converted, if no file is given, the exporters save method will be called.
     * 
     * 
     * @param $to Path
     * @param null $from Path
     * @return mixed
     */
    public function convert($to, $from = null);
}