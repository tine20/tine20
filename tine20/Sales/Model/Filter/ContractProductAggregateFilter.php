<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO generalize this for "records" type
 */

/**
 * product aggregate filter
 *
 * @package     Sales
 * @subpackage  Filter
 */
class Sales_Model_Filter_ContractProductAggregateFilter extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'contains',
    );

    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $filter = new Sales_Model_ProductFilter(array(
            array('field' => 'query', 'operator' => $this->_operator, 'value' => $this->_value)
        ));
        $productIds = Sales_Controller_Product::getInstance()->search($filter, new Tinebase_Model_Pagination(), FALSE, TRUE);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' $productIds: ' . print_r($productIds, true));

        $filter = new Tinebase_Model_Filter_FilterGroup();
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'product_id', 'operator' => 'in', 'value' => $productIds)));
        $contractIds = Sales_Controller_ProductAggregate::getInstance()->search($filter)->contract_id;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' $contractIds: ' . print_r($contractIds, true));

        $field = $_backend->getAdapter()->quoteIdentifier($_backend->getTableName(). '.id');
        $_select->where($field . ' IN (?)', empty($contractIds) ? new Zend_Db_Expr('NULL') : $contractIds);
    }
}
