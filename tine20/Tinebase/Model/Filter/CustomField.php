<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: CustomField.php 7546 2009-04-01 12:36:35Z c.weiss@metaways.de $
 */

/**
 * Tinebase_Model_Filter_CustomField
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters by given customfield name/value
 * 
 * a custom field filter is constructed like this:
 * 
 *  array(
 *     'field' => 'customfield', 
 *     'operator' => 'contains', 
 *     'value' => array('cfId' => '1234', 'value' => 'searchstring')
 *  ),
 */
class Tinebase_Model_Filter_CustomField extends Tinebase_Model_Filter_Text
{
    /**
     * set options 
     *
     * @param  array $_options
     */
    protected function _setOptions(array $_options)
    {
        $_options['idProperty'] = isset($_options['idProperty']) ? $_options['idProperty'] : 'id';
        
        $this->_options = $_options;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function appendFilterSql($_select, $_backend)
    {
        // don't take empty filter into account
        if (empty($this->_value) || ! is_array($this->_value) || ! isset($this->_value['cfId']) || ! isset($this->_value['value']) || empty($this->_value['cfId'])) {
            return;
        } else if ($this->_operator == 'oneof') {
            throw new Tinebase_Exception_UnexpectedValue('Operator "oneof" not supported.');
        }
        
        $cfId = $this->_value['cfId'];
        $value = $this->_replaceWildcards($this->_value['value']);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding custom field filter: ' . print_r($this->_value, true));
        
        $db = Tinebase_Core::getDb();
        $idProperty = $db->quoteIdentifier($this->_options['idProperty']);
        
        // per left join we add a customfield column named as the customfield and filter this joined column
        // NOTE: we name the column we join like the customfield, to be able to join multiple tag criteria (multiple invocations of this function)
        $_select->joinLeft(
            /* what */    array($cfId => SQL_TABLE_PREFIX . 'customfield'), 
            /* on   */    $db->quoteIdentifier("{$cfId}.record_id") . " = $idProperty AND " . $db->quoteIdentifier("{$cfId}.customfield_id") . " = " . $db->quote($cfId),
            /* select */ array());
        $_select->where($db->quoteInto($db->quoteIdentifier("{$cfId}.value") . $this->_opSqlMap[$this->_operator]['sqlop'], $value) . ' /* add cf filter */');
    }
}
