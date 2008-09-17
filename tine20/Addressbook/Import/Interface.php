<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Interface for Addressbook Import
 * 
 * @package Addressbook
 * @subpackage  Import
 * 
 */
interface Addressbook_Import_Interface
{
    /**
     * read data from import file
     *
     * @param string $_filename filename to import
     * @param array $_mapping mapping csv columns to record fields
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    public function read($_filename, $_mapping);
    
    /**
     * import the data
     *
     * @param Tinebase_Record_RecordSet $_records Addressbook_Model_Contact records
     * @param integer $_containerId
     */
    public function import(Tinebase_Record_RecordSet $_records, $_containerId);
}
