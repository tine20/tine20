<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * explicit related record filter definition
 * 
 * filtergroup definition:
 * 
 * 'contract' => array('filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord', 'options' => array(
 *     'controller' => 'Sales_Controller_Contract',
 *     'filtergroup' => 'Sales_Model_ContractFilter',
 *     'own_filtergroup' => 'Timetracker_Model_TimeaccountFilter',
 *     'own_controller' => 'Timetracker_Controller_Timeaccount',
 *     'related_model' => 'Sales_Model_Contract',
 * ))
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_ExplicitRelatedRecord extends Tinebase_Model_Filter_Relation
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Model_Filter_Relation::toArray()
     */
    public function toArray($_valueToJson = false)
    {
        $ret = parent::toArray($_valueToJson);
        if (!isset($ret['value']) || !is_array($ret['value'])) return $ret;

        foreach($ret['value'] as &$filter) {
            if ($filter['field'] == ':id' && $filter['operator'] == 'equals' && is_string($filter['value']) && strlen($filter['value']) == 40) {
                $split = explode('_Model_', $this->_options['related_model']);
                $cname = $split[0] . '_Controller_' . $split[1];
                $fr = $cname::getInstance()->get($filter['value'], /* $_containerId = */ null, /* $_getRelatedData = */ false);
                $fr->relations = null;
                $filter['value'] = $fr->toArray();
            }
        }
        return $ret;
    }
}
