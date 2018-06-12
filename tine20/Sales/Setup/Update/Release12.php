<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Sales_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     *  - add more contract favorites
     */
    public function update_0()
    {
        self::createDefaultFavoritesForContracts();
        $this->setApplicationVersion('Sales', '12.1');
    }

    /**
     * add more contract favorites
     */
    public static function createDefaultFavoritesForContracts()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues['model'] = 'Sales_Model_ContractFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Inactive Contracts", // _('Inactive Contracts')
                'description' => "Contracts that have already been terminated", // _('Contracts that have already been terminated')
                'filters' => [
                    ['field' => 'end_date', 'operator' => 'before', 'value' => Tinebase_Model_Filter_Date::DAY_THIS]
                ],
            ))
        ));
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Active Contracts", // _('Active Contracts')
                'description' => "Contracts that are still running", // _('Contracts that are still running')
                'filters' => [
                    ['condition' => 'OR', 'filters' => [
                        ['field' => 'end_date', 'operator' => 'after', 'value' => Tinebase_Model_Filter_Date::DAY_LAST],
                        ['field' => 'end_date', 'operator' => 'isnull', 'value' => null],
                    ]]
                ],
            ))
        ));
    }
}
