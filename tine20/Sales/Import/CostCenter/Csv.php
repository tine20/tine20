<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Sales
 *
 * @package     Sales
 * @subpackage  Import
 *
 */
class Sales_Import_CostCenter_Csv extends Tinebase_Import_Csv_Generic
{

    /**
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        if($result['number'] == '') {
            while(!isset($existCostCenter)) {
                $result['number'] = rand('100', '999');
                $filter = Sales_Model_CostCenterFilter::getFilterForModel('Sales_Model_CostCenter', [
                    ['field' => 'number', 'operator' => 'equals', 'value' => $result['number']]
                ]);
                $existCostCenter = Sales_Controller_CostCenter::getInstance()->search($filter);
            }
        }
        return $result;
    }

}
