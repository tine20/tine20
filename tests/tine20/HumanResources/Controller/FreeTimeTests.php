<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for HumanResources Controller
 */
class HumanResources_Controller_FreeTimeTests extends HumanResources_TestCase
{
    public function testCalculateReportsForEmployeeSickness()
    {
        $this->_createBasicData();
        HumanResources_Controller_Account::getInstance()->createMissingAccounts(2018, $this->employee->getId());
        $accountId = HumanResources_Controller_Account::getInstance()->getByEmployeeYear($this->employee->getId(),2018)->getId();

        $total = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(),
            new Tinebase_DateTime('2018-12-31 23:59:59'));
        
        HumanResources_Controller_FreeTime::getInstance()->create(
            new HumanResources_Model_FreeTime([
                'employee_id' => $this->employee->getId(),
                'account_id' => $accountId,
                'type' => HumanResources_Model_FreeTimeType::ID_VACATION,
                'freedays' => [
                    ['date' => '2018-08-01'],
                    ['date' => '2018-08-02']
                ]
            ])
        );

        $remaining = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(), 
            new Tinebase_DateTime('2018-08-01 23:59:59'));
        $this->assertEquals($total-1, $remaining);

        $remaining = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(),
            new Tinebase_DateTime('2018-08-02 23:59:59'));
        $this->assertEquals($total-2, $remaining);
    }
}
