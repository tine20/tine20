<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Sales
 *
 * @package     Tinebase
 * @subpackage  Import
 *
 */
class Tinebase_Import_CostCenter_Csv extends Tinebase_Import_Csv_Generic
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
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_CostCenter::class, [
                    ['field' => 'number', 'operator' => 'equals', 'value' => $result['number']]
                ]);
                $existCostCenter = Tinebase_Controller_CostCenter::getInstance()->search($filter);
            }
        }
        return $result;
    }

}
