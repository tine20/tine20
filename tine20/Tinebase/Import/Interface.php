<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * constructs a new importer from given config
     * 
     * @param array $_config
     */
    public function __construct(array $_config = array());
    
    /**
     * creates a new importer from an importexport definition
     * 
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_config
     * @return Tinebase_Import_Abstract
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_config = array());
    
    /**
     * import the data
     *
     * @param  stream $_resource 
     * @return array : 
     *  'results'           => Tinebase_Record_RecordSet, // for dryrun only
     *  'totalcount'        => int,
     *  'failcount'         => int,
     *  'duplicatecount'    => int,
     */
    public function import($_resource = NULL);
    
    /**
     * import given filename
     * 
     * @param string $_filename
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importFile($_filename);
    
    /**
     * import from given data
     * 
     * @param string $_data
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importData($_data);
    
}
