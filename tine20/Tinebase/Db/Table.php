<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Db
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Db
 */
class Tinebase_Db_Table extends Zend_Db_Table_Abstract
{
    /**
     * wrapper around Zend_Db_Table_Abstract::fetchAll
     *
     * @param strin|array $_where OPTIONAL
     * @param string $_order OPTIONAL
     * @param string $_dir OPTIONAL
     * @param int $_count OPTIONAL
     * @param int $_offset OPTIONAL
     * @throws Tinebase_Exception_InvalidArgument if $_dir is not ASC or DESC
     * @return the row results per the Zend_Db_Adapter fetch mode.
     */
    public function fetchAll($_where = NULL, $_order = NULL, $_dir = 'ASC', $_count = NULL, $_offset = NULL)
    {
        if($_dir != 'ASC' && $_dir != 'DESC') {
            throw new Tinebase_Exception_InvalidArgument('$_dir can be only ASC or DESC');
        }
        
        $order = NULL;
        if($_order !== NULL) {
            $order = $_order . ' ' . $_dir;
        }
        
        // possibility to tracing queries
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE) && $config = Tinebase_Core::getConfig()->logger) {
            if ($config->traceQueryOrigins) {
                $e = new Exception();
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "\n" .
                    "BACKTRACE: \n" . $e->getTraceAsString() . "\n" .
                    "SQL QUERY: \n" . $this->select()->assemble());
            }
        }

        $rowSet = parent::fetchAll($_where, $order, $_count, $_offset);
        
        return $rowSet;
    }
    
    /**
     * get total count of rows
     *
     * @param string|array|Zend_Db_Select $_where
     */
    public function getTotalCount($_where)
    {
        $tableInfo = $this->info();
            
        if (is_array($_where) || is_string($_where)) {
            $select = $this->getAdapter()->select();
            foreach((array)$_where as $where) {
                $select->where($where);
            }
        } elseif ($_where instanceof Zend_Db_Select ) {
            $select = $_where;
        }
        
        $select->from($tableInfo['name'], array('count' => 'COUNT(*)'));
        
        $stmt = $this->getAdapter()->query($select);
        $result = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        return $result['count'];
    }
    
    /**
     * get describe table from metadata cache
     * 
     * @param string $tableName
     * @param Zend_Db_Adapter_Abstract $db
     * @return array
     */
    public static function getTableDescriptionFromCache($tableName, $db = NULL)
    {
        if ($db === NULL) {
            $db = Tinebase_Core::getDb();
        }
        
        $dbConfig = $db->getConfig();
        
        $cacheId = md5($dbConfig['host'] . $dbConfig['dbname'] . $tableName);
        
        // try to get description from in-memory caches first
        try {
            return Tinebase_Cache_PerRequest::getInstance()->load(__CLASS__, __METHOD__, $cacheId, false /* ignore persistent cache */);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // try to get description from APC
            if (function_exists('apc_fetch')) {
                // prefix with applications hash, as this hash changes if an application got updated
                $apcCacheId = Tinebase_Application::getInstance()->getApplicationsHash() . $cacheId;
                
                if ($result = apc_fetch($apcCacheId)) {
                    Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __METHOD__, $cacheId, $result, false /* store in in-memory cache only */);
                    
                    return $result;
                }
            }
        }
        
        // try to get description from persistent cache
        try {
            return Tinebase_Cache_PerRequest::getInstance()->load(__CLASS__, __METHOD__, $cacheId, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        
        // read description from database
        $result = $db->describeTable($tableName);
        
        // if table does not exist (yet), $result is an empty array
        if (count($result) > 0) {
            // save result for next request
            Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __METHOD__, $cacheId, $result, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
            
            // if APC extension is installed also store in APC
            if (function_exists('apc_store')) {
                apc_store($apcCacheId, $result, 3600);
            }
        }
        
        return $result;
    }
}
