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
 * encapsulates SQL commands of Mysql database
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Command_Mysql implements Tinebase_Backend_Sql_Command_Interface
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
        
        return new Zend_Db_Expr("GROUP_CONCAT( DISTINCT $quotedField)");
    }

    /**
     * @param string $field
     * @param mixed $returnIfTrue
     * @param mixed $returnIfFalse
     */
    public function getIfIsNull($field, $returnIfTrue, $returnIfFalse)
    {
        $quotedField = $this->_adapter->quoteIdentifier($field);
        
        return new Zend_Db_Expr("(CASE WHEN $quotedField IS NULL THEN " . (string) $returnIfTrue . " ELSE " . (string) $returnIfFalse . " END)");
    }
    
    /**
     *
     * @param string $condition
     * @param string $returnIfTrue
     * @param string $returnIfFalse
     * @return string
     */
    public function getIfElse($condition, $returnIfTrue, $returnIfFalse)
    {
        return new Zend_Db_Expr("(IF($condition, $returnIfTrue, $returnIfFalse))");
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
        return 'LIKE';
    }
}
