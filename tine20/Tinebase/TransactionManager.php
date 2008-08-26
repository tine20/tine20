<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  TransactionManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Transaction Manger for Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  TransactionManager
 */
class Tinebase_TransactionManager
{
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }

    /**
     * @var Tinebase_TransactionManager
     */
    private static $_instance = NULL;
    
    /**
     * constructor
     */
    private function __construct()
    {
        
    }
    
    /**
     * @return Tinebase_TransactionManager
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_TransactionManager;
        }
        
        return self::$_instance;
    }
    
    
}