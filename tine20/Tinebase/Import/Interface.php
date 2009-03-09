<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Interface for Tinebase Import
 * 
 * @package Tinebase
 * @subpackage  Import
 * 
 */
interface Tinebase_Import_Interface
{
    /**
     * import the data
     *
     * @param  string $_filename
     * @param  resource $_resource (if $_filename is a stream)
     * @return array with Tinebase_Record_RecordSet the imported records (if dryrun) and totalcount 
     */
    public function import($_filename, $_resource = NULL);
}
