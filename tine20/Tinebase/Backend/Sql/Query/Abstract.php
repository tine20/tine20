<?php
/**    
 * Generalization for customized queries
 *    
 * @package Tinebase
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

 /**
 * Generalization for customized queries
 * 
 * How to use:
 * 
 * 1) Create a class in application folder following this pattern:
 * 
 * [Application]_Backend_Sql_Query extends Tinebase_Backend_Sql_Factory_Abstract
 * 
 * This class needs no code.
 * 
 * 2) Create a interface [Application]_Backend_Sql_Query_Interface with methods for creating and executing queries.
 *
 * 3) Create a class [Application]_Backend_Sql_Query_Abstract that extends [Application]_Backend_Sql_Query_Interface 
 * 
 * This class keeps the building of queries of same way, it's the default behavior.
 *  
 * 4) Create a class for each database Adapter: 
 * [Application]_Backend_Sql_Query_Mysql
 * [Application]_Backend_Sql_Query_Pgsql
 * [Application]_Backend_Sql_Query_Oracle
 * 
 * If the adapter keeps the former query building, it extends [Application]_Backend_Sql_Query_Abstract
 * If the adapter customizes the query, it only implements [Application]_Backend_Sql_Query_Interface 
 * 
 * The use of a generic query created with Zend_Db_Select must be replaced for call of method of one of these adapters.
 * Instead of:
 * 
 * ...
 * several lines of code with a generic query
 * ...
 * 
 * you should use:
 * 
 * [Application]_Backend_Sql_Query::factory($this->_db)->[method]()
 * 
 * That works for every database supported, but can executes an optimized query for one determined database  
 *
 * @package Tinebase
 */
class Tinebase_Backend_Sql_Query_Abstract 
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    /**
     *
     * @param Zend_Db_Adapter_Abstract $db
     */
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->_db = $db;
    }    
}
