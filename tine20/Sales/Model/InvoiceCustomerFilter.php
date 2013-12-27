<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Invoice-Customer filter Class
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_InvoiceCustomerFilter extends Tinebase_Model_Filter_Relation
{
    /**
     * returns own ids defined by relation filter
     *
     * @param string $_modelName
     * @return array
     */
    protected function _getOwnIds($_modelName)
    {
        if (empty($this->_value[0]['value'])) {
            $relationFilter = new Tinebase_Model_RelationFilter(array(
                array('field' => 'own_model',     'operator' => 'equals', 'value' => $_modelName),
                array('field' => 'related_model', 'operator' => 'equals', 'value' => $this->_options['related_model']),
            ));

            $notInIds = Tinebase_Relations::getInstance()->search($relationFilter, NULL)->own_id;
            $filterName = 'Sales_Model_InvoiceFilter';
            $filter = new $filterName(array(
                array('field' => 'id', 'operator' => 'notin', 'value' => $notInIds)
            ),'AND');

            $cname = 'Sales_Controller_Invoice';
            return $cname::getInstance()->search($filter, null, false, true);
        }

        return parent::_getOwnIds($_modelName);
    }
    /**
     * (non-PHPdoc)
     * @see Tinebase_Model_Filter_ForeignRecord::toArray()
     */
    public function toArray($_valueToJson = false)
    {
        $ret = parent::toArray($_valueToJson);
        foreach($ret['value'] as &$filter) {
            if ($filter['field'] == ':id' && $filter['operator'] == 'equals' && is_string($filter['value']) && strlen($filter['value']) == 40) {
                $fr = Sales_Controller_Customer::getInstance()->get($filter['value']);
                $fr->relations = null;
                $filter['value'] = $fr->toArray();
            }
        }
        return $ret;
    }
}
