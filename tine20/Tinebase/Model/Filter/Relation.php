<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase_Model_Filter_Relation
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters own ids match result of related filter
 * 
 * @example
        'contact'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
            'related_model'     => 'Addressbook_Model_Contact',
            'related_filter'    => 'Addressbook_Model_ContactFilter'
        )     
 */
class Tinebase_Model_Filter_Relation extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'AND',
        1 => 'OR',
    );
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $value = $this->_value;
        $relationTypeFilter = NULL;
        foreach($value as $idx => $filterData) {
            if ($filterData['field'] == 'relation_type') {
                $relationTypeFilter = $value[$idx];
                unset($value[$idx]);
            }
        }
        
        $relatedFilterConstructor = $this->_options['related_filter'];
        $relatedFilter = new $relatedFilterConstructor($value);
        
        $relatedRecordController = Tinebase_Controller_Record_Abstract::getController($this->_options['related_model']);
        $relatedIds = $relatedRecordController->search($relatedFilter, NULL, FALSE, TRUE);
        
        $relationFilter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'own_model',     'operator' => 'equals', 'value' => $_backend->getModelName()),
            array('field' => 'related_model', 'operator' => 'equals', 'value' => $this->_options['related_model']),
            array('field' => 'related_id',    'operator' => 'in'    , 'value' => $relatedIds)
        ));
        
        if ($relationTypeFilter) {
            $relationFilter->addFilter($relationFilter->createFilter('type', $relationTypeFilter['operator'], $relationTypeFilter['value']));
        }
        
        $ownIds = Tinebase_Relations::getInstance()->search($relationFilter, NULL)->own_id;
        
        $idField = array_key_exists('idProperty', $this->_options) ? $this->_options['idProperty'] : 'id';
        
        $db = $_backend->getAdapter();
        $qField = $db->quoteIdentifier($_backend->getTableName() . '.' . $idField);
        
        $_select->where($db->quoteInto("$qField IN (?)", empty($ownIds) ? ' ' : $ownIds));
     }
     
}