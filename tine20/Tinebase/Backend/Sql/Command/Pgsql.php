<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * encapsulates SQL commands of PostgreSQL database
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Command_Pgsql implements Tinebase_Backend_Sql_Command_Interface
{
    /**
     * 
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_adapter;
    
    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     */
    public function __construct(Zend_Db_Adapter_Abstract $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    /**
     * @param string $field
     * @return string
     */
    public function getAggregate($field)
    {
        $quotedField = $this->_adapter->quoteIdentifier($field);
        
        // since 9.0
        #return new Zend_Db_Expr("string_agg(DISTINCT $quotedField, ',')");
        
        // before 9.0
        return new Zend_Db_Expr("array_to_string(ARRAY(SELECT DISTINCT unnest(array_agg($quotedField)) ORDER BY 1),',')");
    }

    /**
     * @param string $field
     * @param mixed $returnIfTrue
     * @param mixed $returnIfFalse
     * @return string
     */
    public function getIfIsNull($field, $returnIfTrue, $returnIfFalse)
    {
        $quotedField = $this->_adapter->quoteIdentifier($field);
        
        return new Zend_Db_Expr("(CASE WHEN $quotedField IS NULL THEN $returnIfTrue ELSE $returnIfFalse END)");
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $condition
     * @param string $returnIfTrue
     * @param string $returnIfFalse
     * @return string
     */
    public function getIfElse($condition, $returnIfTrue, $returnIfFalse)
    {
        return new Zend_Db_Expr("(CASE WHEN $condition THEN $returnIfTrue ELSE $returnIfFalse END)");
    }
    
    /**
     * @param string $date
     * @return string
     */
    public function setDate($date)
    {
        return "DATE({$date})";
    }

    /**
     * @param string $value
     * @return string
     */
    public function setDateValue($value)
    {
        return $this->_adapter->quote($value);
    }

    /**
     * @return mixed
     */
    public function getFalseValue()
    {
        return 'FALSE';
    }

    /**
     * @return mixed
     */
    public function getTrueValue()
    {
        return 'TRUE';
    }

    /**
     * @return string
     */
    public function setDatabaseJokerCharacters()
    {
        return array('%', '\_');
    }
    
    /**
     * get like keyword
     * 
     * @return string
     */
    public function getLike()
    {
        return 'iLIKE';
    }
    
    /**
     * Even if the database backend is PostgreSQL, we have to verify
     * if the extension Unaccent is installed and loaded.
     * 
     * @return boolean
     */
    protected function _hasUnaccentExtension()
    {
        $cache = Tinebase_Core::getCache();
        $cacheId = 'PostgreSQL_Unaccent_Extension';
        if ($cache->test($cacheId) === FALSE)
        {
            $tableName = 'pg_extension';
            $cols = 'COUNT(*)';

            if ($this->_adapter) {
                $select = $this->_adapter->select();
                $select->from($tableName, $cols);
                $select->where("extname = 'unaccent'");             
            }
            //result of the verification if the extension is installed or not
            $result = $this->_adapter->fetchOne($select);
            $cache->save($result, $cacheId, array('pgsql','extension','unaccent'), null);
        }
        else
        {
            //I need the result of the query (there is or there isn't
            //the extension)
            $result = $cache->load($cacheId);
        }
        return $result;
    }
    
    /**
     * returns field without accents (diacritic signs) - for Pgsql;
     * 
     * @return string
     */
    public function getUnaccent($field)
    {
        if($this->_hasUnaccentExtension() == true){
            return ' unaccent('.$field.') ';
        } else{
            return $field;
        }
        
    }
    
    /**
     * escape special char 
     * 
     * @return string
     */ 
     public function escapeSpecialChar($value)
     {
         return str_replace('\\', '\\\\', $value);
     }     
}
