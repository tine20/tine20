<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase_Model_Filter_Tag
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters by given tag
 */
class Tinebase_Model_Filter_Tag extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'not',
        //2 => 'in'
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' IS NOT NULL'),
        'not'        => array('sqlop' => ' IS NULL'    ),
        //'in'         => array('sqlop' => ' IS NOT NULL'),
    );
    
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
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        // don't take empty tag filter into account
        if (empty($this->_value)) {
            return;
        }
        
        // check the view right of the tag (throws Exception if not accessable)
        Tinebase_Tags::getInstance()->getTagsById($this->_value);
        
        $db = Tinebase_Core::getDb();
        $idProperty = $db->quoteIdentifier($this->_options['idProperty']);
        
        // per left join we add a tag column named as the tag and filter this joined column
        // NOTE: we name the column we join like the tag, to be able to join multiple tag criteria (multiple invocations of this function)
        $_select->joinLeft(
            /* what */    array($this->_value => SQL_TABLE_PREFIX . 'tagging'), 
            /* on   */    $db->quoteIdentifier("{$this->_value}.record_id") . " = $idProperty AND " . $db->quoteIdentifier("{$this->_value}.tag_id") . " = " . $db->quote($this->_value),
            /* select */  array());
        
        $_select->where($db->quoteIdentifier("{$this->_value}.tag_id") .  $this->_opSqlMap[$this->_operator]['sqlop']);
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson == true) {
            $tags = Tinebase_Tags::getInstance()->getTagsById($this->_value)->toArray();
            if (count($tags) > 0) {
                $result['value'] = $tags[0];
            } else {
                $result['value'] = '';
            }
        }
        return $result;
    }
}