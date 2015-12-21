<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * encapsulates SQL commands of Oracle database
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Command_Oracle implements Tinebase_Backend_Sql_Command_Interface
{
    /**
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
     * @todo replace by equivalent function of MySQL GROUP_CONCAT in Oracle
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
     * get switch case expression with multiple cases
     *
     * @param string $field
     * @param array $cases
     *
     * @return Zend_Db_Expr
     */
    public function getSwitch($field, $cases)
    {
        $case = 'CASE ' . $this->_adapter->quoteIdentifier($field) . ' ';
        foreach ($cases as $when => $then) {
            $case .=  $this->_adapter->quoteInto(' WHEN ' . $when . ' THEN ?', $then);
        }
        $case .= ' END';
        return new Zend_Db_Expr($case);
    }
    
    /**
     * @param string $date
     * @return string
     */
    public function setDate($date)
    {
        return "TO_DATE({$date}, 'YYYY-MM-DD hh24:mi:ss') ";
    }
    
    /**
     * @param string $value
     * @return string
     */
    public function setDateValue($value)
    {
        return "TO_DATE('{$value}', 'YYYY-MM-DD hh24:mi:ss') ";
    }
    
    /**
     * @return mixed
     */
    public function getFalseValue()
    {
        return '0';
    }
    
    /**
     * @return mixed
     */
    public function getTrueValue()
    {
        return '1';
    }
    
    /**
     * @return string
     */
    public function setDatabaseJokerCharacters()
    {
        return array('%', '_');
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
    
    /**
     * prepare value for case insensitive search
     *
     * @param string $value
     * @return string
     */
    public function prepareForILike($value)
    {
        return ' UPPER (' . $value . ')';
    }
    
    /**
     * returns field without accents (diacritic signs) - for Pgsql;
     *
     * @param string $field
     * @return string
     */
    public function getUnaccent($field)
    {
        return $field;
    }
    
    /**
     * escape special char
     *
     * @return string
     */
     public function escapeSpecialChar($value)
     {
         return $value;
     }
     
     /**
      * Initializes database procedures
      * 
      * @param Setup_Backend_Interface $backend
      */
     public function initProcedures(Setup_Backend_Interface $backend)
     {
         $md5 = "CREATE OR REPLACE
            function md5( input varchar2 ) return sys.dbms_obfuscation_toolkit.varchar2_checksum as
            begin
                return lower(rawtohex(utl_raw.cast_to_raw(sys.dbms_obfuscation_toolkit.md5( input_string => input ))));
            end;";
         $backend->execQueryVoid($md5);

         $now = "CREATE OR REPLACE
            function NOW return DATE as
            begin
                return SYSDATE;
            end;";
         $backend->execQueryVoid($now);

         $typeStringAgg = "CREATE OR REPLACE TYPE t_string_agg AS OBJECT
                (
                  g_string  VARCHAR2(32767),

                  STATIC FUNCTION ODCIAggregateInitialize(sctx  IN OUT  t_string_agg)
                    RETURN NUMBER,

                  MEMBER FUNCTION ODCIAggregateIterate(self   IN OUT  t_string_agg, value  IN      VARCHAR2 )
                     RETURN NUMBER,

                  MEMBER FUNCTION ODCIAggregateTerminate(self         IN   t_string_agg,
                                                         returnValue  OUT  VARCHAR2,
                                                         flags        IN   NUMBER)
                    RETURN NUMBER,

                  MEMBER FUNCTION ODCIAggregateMerge(self  IN OUT  t_string_agg,
                                                     ctx2  IN      t_string_agg)
                    RETURN NUMBER
                );";
         $backend->execQueryVoid($typeStringAgg);

         $typeStringAgg = "CREATE OR REPLACE TYPE BODY t_string_agg IS
              STATIC FUNCTION ODCIAggregateInitialize(sctx  IN OUT  t_string_agg)
                RETURN NUMBER IS
              BEGIN
                sctx := t_string_agg(NULL);
                RETURN ODCIConst.Success;
              END;

              MEMBER FUNCTION ODCIAggregateIterate(self   IN OUT  t_string_agg,
                                                   value  IN      VARCHAR2 )
                RETURN NUMBER IS
              BEGIN
                SELF.g_string := self.g_string || ',' || value;
                RETURN ODCIConst.Success;
              END;

              MEMBER FUNCTION ODCIAggregateTerminate(self         IN   t_string_agg,
                                                     returnValue  OUT  VARCHAR2,
                                                     flags        IN   NUMBER)
                RETURN NUMBER IS
              BEGIN
                returnValue := RTRIM(LTRIM(SELF.g_string, ','), ',');
                RETURN ODCIConst.Success;
              END;

              MEMBER FUNCTION ODCIAggregateMerge(self  IN OUT  t_string_agg,
                                                 ctx2  IN      t_string_agg)
                RETURN NUMBER IS
              BEGIN
                SELF.g_string := SELF.g_string || ',' || ctx2.g_string;
                RETURN ODCIConst.Success;
              END;
            END;";
         $backend->execQueryVoid($typeStringAgg);

         $group_concat = "CREATE OR REPLACE
            FUNCTION GROUP_CONCAT (p_input VARCHAR2)
            RETURN VARCHAR2
            PARALLEL_ENABLE AGGREGATE USING t_string_agg;";
         $backend->execQueryVoid($group_concat);
     }

    /**
     * returns something similar to "interval $staticPart * $dynamicPart $timeUnit"
     *
     * @param string $timeUnit
     * @param string $staticPart
     * @param string $dynamicPart
     * @return string
     */
    public function getDynamicInterval($timeUnit, $staticPart, $dynamicPart)
    {
        return 'INTERVAL ' . $staticPart . ' * ' . $dynamicPart . ' ' . $timeUnit;
    }

    /**
     * returns something similar to "interval $staticPart $timeUnit"
     *
     * @param string $timeUnit
     * @param string $staticPart
     * @return string
     */
    public function getInterval($timeUnit, $staticPart)
    {
        return 'INTERVAL ' . $staticPart . ' ' . $timeUnit;
    }
}
