<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Asterisk.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 *
 */

/**
 * salutation backend for the Addressbook application
 * 
 * @package     Addressbook
 * 
 */
class Addressbook_Backend_Salutation extends Tinebase_Abstract_SqlTableBackend
{
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Addressbook_Backend_Salutation
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Backend_Snom_Callhistory
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Backend_Salutation();
        }
        
        return self::$_instance;
    }

    /**
     * the constructor
     * 
     * don't use the constructor. use the singleton
     */
    private function __construct ()
    {
        $this->_tableName = SQL_TABLE_PREFIX . 'addressbook_salutations';
        $this->_modelName = 'Addressbook_Model_Salutation';
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->_table = new Tinebase_Db_Table(array('name' => $this->_tableName));
    }    

}
