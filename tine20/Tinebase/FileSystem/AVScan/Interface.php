<?php
/**
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * interface for av scanner
 */
interface Tinebase_FileSystem_AVScan_Interface
{
    /**
     * @param resource $handle
     * @return Tinebase_FileSystem_AVScan_Result
     */
    public function scan($handle);
}