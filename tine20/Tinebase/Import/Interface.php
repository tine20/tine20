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
 * @todo        add more functions or change some?
 * @todo        remove obsolete code when finished
 */

/**
 * Interface for Tinebase Import
 * 
 * @package Tinebase
 * @subpackage  Import
 * 
 */
interface Addressbook_Import_Interface
{
    /**
     * read data from import file
     *
     * @param mixed $_from filename|database|... to import from
     * @return Tinebase_Record_RecordSet
     */
    // @param array $_options general options for the import(mapping, ...) ?
    public function read($_from);
    
    /**
     * import the data
     *
     * @param Tinebase_Record_RecordSet $_records Addressbook_Model_Contact records
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    // @param array $_options general options for the import(mapping, ...) ?
    public function import(Tinebase_Record_RecordSet $_records);
}
