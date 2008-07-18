<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement functions
 * @todo        add tests
 */

/**
 * Class for handling notes
 * 
 * @package     Tinebase
 * @subpackage  Notes 
 */
class Tinebase_Notes
{
    /**
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db;
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Notes
     */
    private static $_instance = NULL;
        
    /**
     * the singleton pattern
     *
     * @return Tinebase_Notes
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Notes;
        }
        
        return self::$_instance;
    }

    /**
     * the private constructor
     *
     */
    private function __construct()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    
}