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
 * encapsulates SQL commands that are different for each dialect
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
interface Tinebase_Backend_Sql_Command_Interface
{
    /**
     * @param string $field
     * @return string
     */
     public function getAggregate($field);

     /**
      * @param string $field
      * @param mixed $returnIfTrue
      * @param mixed $returnIfFalse
      * @return string
      */
    public function getIfIsNull($field, $returnIfTrue, $returnIfFalse);

    /**
     *
     * @param string $condition
     * @param string $returnIfTrue
     * @param string $returnIfFalse
     * @return string
     */
    public function getIfElse($condition, $returnIfTrue, $returnIfFalse);
    
    /**
     * @param date $date
     */
    public function setDate($date);
    
    /**
     * @param date $date
     */
    public function setDateValue($date);

    /**
     * returns the false value according to backend
     * @return mixed
     */
    public function getFalseValue();

    /**
     * returns the true value according to backend
     * @return mixed
     */
    public function getTrueValue();

    /**
     * @param array $field
     */
    public function setDatabaseJokerCharacters();

    /**
     * get like keyword
     * 
     * @return string
     */
    public function getLike();
}
