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
 * csv import class for the addressbook
 *
 * @package     Addressbook
 * @subpackage  Import
 */
class Addressbook_Import_Csv implements Addressbook_Import_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct ()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone ()
    {

    }
    
    /**
     * holdes the instance of the singleton
     *
     * @var Addressbook_Backend_Sql
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Backend_Sql
     */
    public static function getInstance ()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Import_Csv();
        }
        return self::$_instance;
    }   
    
    /**
     * read data from import file
     *
     * @param string $_filename filename to import
     * @param array $_mapping mapping csv columns to record fields
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    public function read($_filename, $_mapping)
    {
        
    }
    
    /**
     * import the data
     *
     * @param Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    public function import(Tinebase_Record_RecordSet $_records)
    {
        
    }
    
}
