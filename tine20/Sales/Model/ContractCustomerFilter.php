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
 * Contract-Contract-Customer filter Class
*
* @package     Sales
* @subpackage  Model
*/
class Sales_Model_ContractCustomerFilter extends Tinebase_Model_Filter_Relation
{
    /**
     * returns own ids defined by relation filter
     *
     * @param string $_modelName
     * @return array
     */
    protected function _getOwnIds($_modelName)
    {
        // if no costcenter is given, perform search on contracts having no costcenters assigned
        if (empty($this->_value[0]['value'])) {
            $relationFilter = new Tinebase_Model_RelationFilter(array(
                array('field' => 'own_model',     'operator' => 'equals', 'value' => $_modelName),
                array('field' => 'related_model', 'operator' => 'equals', 'value' => $this->_options['related_model']),
            ));

            $notInIds = Tinebase_Relations::getInstance()->search($relationFilter, NULL)->own_id;
            $filter = new Sales_Model_ContractFilter(array(
                array('field' => 'id', 'operator' => 'notin', 'value' => $notInIds)
            ),'AND');

            return Sales_Controller_Contract::getInstance()->search($filter, null, false, true);
        }

        return parent::_getOwnIds($_modelName);
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Model_Filter_Relation::toArray()
     */
    public function toArray($_valueToJson = false)
    {
        // resolve costcenter
        $ret = parent::toArray($_valueToJson);
        if(! empty($this->_value[0]['value'])) {
            $found = false;
            foreach($ret['value'] as &$filter) {
                if($filter['field'] == ':id' && $filter['operator'] == 'equals' && is_string($filter['value']) && strlen($filter['value']) == 40) {
                    $filterValue = Sales_Controller_Customer::getInstance()->get($filter['value']);
                    $filterValue->relations = null;
                    $filter['value'] = $filterValue->toArray();
                    $found = true;
                }
            }
            if (! $found) {
                $filterValueRecord = Sales_Controller_Customer::getInstance()->get($this->_value[0]['value']);
                $filterValueRecord->relations = null;
                $filterValue = $filterValueRecord->toArray();
                $ret['value'][] = array(
                    'field' => ':id',
                    'operator' => 'equals',
                    'value' => $filterValue
                );
            }
        }
        return $ret;
    }
}
