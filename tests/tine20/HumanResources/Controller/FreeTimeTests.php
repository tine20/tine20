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
    protected $_account2018 = null;

    protected function tearDown(): void
    {
        $this->_account2018 = null;
        parent::tearDown();
    }

    protected function _getAccount2018()
    {
        if (null === $this->_account2018) {
            $this->_createBasicData('pwulf');
            HumanResources_Controller_Account::getInstance()->createMissingAccounts(2018, $this->employee->getId());
            $this->_account2018 = HumanResources_Controller_Account::getInstance()->getByEmployeeYear($this->employee->getId(), 2018);
        }
        return $this->_account2018;
    }

    protected function _createFreeTime()
    {
        $accountId = $this->_getAccount2018()->getId();

        return HumanResources_Controller_FreeTime::getInstance()->create(
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
    }
    public function testCalculateReportsForEmployeeSickness()
    {
        $this->_getAccount2018();
        $total = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(),
            new Tinebase_DateTime('2018-12-31 23:59:59'));
        $this->_createFreeTime();

        $remaining = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(), 
            new Tinebase_DateTime('2018-08-01 23:59:59'));
        $this->assertEquals($total-1, $remaining);

        $remaining = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(),
            new Tinebase_DateTime('2018-08-02 23:59:59'));
        $this->assertEquals($total-2, $remaining);
    }

    public function testFreeDaysGrantFail()
    {
        Tinebase_Core::setUser($this->_personas['pwulf']);
        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $this->expectExceptionMessage('acl delegation field freetime_id must not be empty');
        HumanResources_Controller_FreeDay::getInstance()->create(new HumanResources_Model_FreeDay([]));
    }

    public function testFreeDaysGrant1Fail()
    {
        $freeTime = $this->_createFreeTime();

        Tinebase_Core::setUser($this->_personas['pwulf']);
        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $this->expectExceptionMessage('No Permission.');
        HumanResources_Controller_FreeDay::getInstance()->create(new HumanResources_Model_FreeDay([
            'freetime_id' => $freeTime->getId()
        ]));
    }

    public function testFreeDaysGrantAccessFail()
    {
        $freeTime = $this->_createFreeTime();

        $freeDay = HumanResources_Controller_FreeDay::getInstance()->search(new HumanResources_Model_FreeDayFilter([
            ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $freeTime->getId()]
        ]))->getFirstRecord();
        $this->assertNotNull($freeDay);

        Tinebase_Core::setUser($this->_personas['pwulf']);
        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $this->expectExceptionMessage('No Permission.');
        HumanResources_Controller_FreeDay::getInstance()->get($freeDay->getId());
    }

    public function testFreeDaysGrantAccessFail1()
    {
        $freeTime = $this->_createFreeTime();

        Tinebase_Core::setUser($this->_personas['pwulf']);

        $this->assertSame(0, HumanResources_Controller_FreeDay::getInstance()
            ->search(new HumanResources_Model_FreeDayFilter([
                ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $freeTime->getId()]
            ]))->count());
    }

    public function testAccessOwnDataGrant()
    {
        $freeTime = $this->_createFreeTime();

        Tinebase_Container::getInstance()->addGrants(
            HumanResources_Controller_Division::getInstance()->get($this->employee->division_id)->container_id,
            Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, $this->_personas['pwulf']->getId(),
            [HumanResources_Model_DivisionGrants::READ_OWN_DATA], true);

        Tinebase_Core::setUser($this->_personas['pwulf']);

        $freeDays = HumanResources_Controller_FreeDay::getInstance()->search(new HumanResources_Model_FreeDayFilter([
            ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $freeTime->getId()]
        ]));
        $this->assertSame(2, $freeDays->count());

        HumanResources_Controller_FreeDay::getInstance()->get($freeDays->getFirstRecord()->getId());
        HumanResources_Controller_FreeTime::getInstance()->get($freeTime->getId());

        $this->assertSame(1, HumanResources_Controller_FreeTime::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                HumanResources_Model_FreeTime::class, [
                    ['field' => 'account_id', 'operator' => 'equals', 'value' => $freeTime->account_id],
                ]))->count());
    }
}
