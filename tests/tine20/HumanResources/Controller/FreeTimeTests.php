<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
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

    protected function _getAccount2018($user = null)
    {
        if (null === $this->_account2018) {
            $this->_createBasicData($user ?: 'pwulf');
            HumanResources_Controller_Account::getInstance()->createMissingAccounts(2018, $this->employee->getId());
            $this->_account2018 = HumanResources_Controller_Account::getInstance()->getByEmployeeYear($this->employee->getId(), 2018);
        }
        return $this->_account2018;
    }

    protected function _createFreeTime($user = null)
    {
        $accountId = $this->_getAccount2018($user)->getId();

        return HumanResources_Controller_FreeTime::getInstance()->create(
            new HumanResources_Model_FreeTime([
                'employee_id' => $this->employee->getId(),
                'account_id' => $accountId,
                'type' => HumanResources_Model_FreeTimeType::ID_VACATION,
                HumanResources_Model_FreeTime::FLD_PROCESS_STATUS => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED,
                'freedays' => [
                    ['date' => '2018-08-01'],
                    ['date' => '2018-08-02']
                ]
            ])
        );
    }
    public function testGetRemainingVacationDays()
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

        HumanResources_Controller_Account::getInstance()->createMissingAccounts(2019);
        $remaining = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(),
            new Tinebase_DateTime('2019-08-02 23:59:59'), new Tinebase_DateTime('2019-01-31 23:59:59'));
        $this->assertEquals($total-2+30, $remaining, 'should be remaining from 2018 + new from 2019');

        $remaining = HumanResources_Controller_FreeTime::getInstance()->getRemainingVacationDays($this->employee->getId(),
            new Tinebase_DateTime('2019-08-02 23:59:59'), new Tinebase_DateTime('2019-04-01 23:59:59'));
        $this->assertEquals(30, $remaining, 'should be new from 2019 only. 2018 is expired');
    }

    public function testFreeDaysGrantFail()
    {
        Tinebase_Core::setUser($this->_personas['jsmith']);

        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $this->expectExceptionMessage('acl delegation field freetime_id must not be empty');
        HumanResources_Controller_FreeDay::getInstance()->create(new HumanResources_Model_FreeDay([]));
    }

    public function testFreeDaysGrant1Fail()
    {
        $freeTime = $this->_createFreeTime('jsmith');

        Tinebase_Core::setUser($this->_personas['jsmith']);
        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $this->expectExceptionMessage('No Permission.');
        HumanResources_Controller_FreeDay::getInstance()->create(new HumanResources_Model_FreeDay([
            'freetime_id' => $freeTime->getId()
        ]));
    }

    public function testFreeDaysGrantAccessFail()
    {
        $freeTime = $this->_createFreeTime('jsmith');

        $freeDay = HumanResources_Controller_FreeDay::getInstance()->search(new HumanResources_Model_FreeDayFilter([
            ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $freeTime->getId()]
        ]))->getFirstRecord();
        $this->assertNotNull($freeDay);

        Tinebase_Core::setUser($this->_personas['jsmith']);
        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $this->expectExceptionMessage('No Permission.');
        HumanResources_Controller_FreeDay::getInstance()->get($freeDay->getId());
    }

    public function testFreeDaysGrantAccessFail1()
    {
        $freeTime = $this->_createFreeTime('jsmith');

        Tinebase_Core::setUser($this->_personas['jsmith']);

        $this->assertSame(0, HumanResources_Controller_FreeDay::getInstance()
            ->search(new HumanResources_Model_FreeDayFilter([
                ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $freeTime->getId()]
            ]))->count());
    }

    public function testAccessOwnDataGrant()
    {
        $freeTime = $this->_createFreeTime('jsmith');

        Tinebase_Container::getInstance()->addGrants(
            HumanResources_Controller_Division::getInstance()->get($this->employee->division_id)->container_id,
            Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, $this->_personas['jsmith']->getId(),
            [HumanResources_Model_DivisionGrants::READ_OWN_DATA], true);

        Tinebase_Core::setUser($this->_personas['jsmith']);

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

    public function testCreateChangeRequestGrant()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $freeTime = $this->_createFreeTime('pwulf');

        Tinebase_Core::setUser($this->_personas['jsmith']);

        try {
            HumanResources_Controller_FreeTime::getInstance()->get($freeTime->getId());
            $this->fail('jsmith should not see pwulf data');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        try {
            HumanResources_Controller_Account::getInstance()->get($freeTime->account_id);
            $this->fail('jsmith should not see pwulf data');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        try {
            HumanResources_Controller_Employee::getInstance()->get($freeTime->employee_id);
            $this->fail('jsmith should not see pwulf data');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer(
            ($d = HumanResources_Controller_Division::getInstance()->get($this->employee->division_id))->container_id,
            true);
        $grants->addRecord(new HumanResources_Model_DivisionGrants([
            'record_id' => $d->container_id,
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id' => $this->_personas['jsmith']->getId(),
            HumanResources_Model_DivisionGrants::CREATE_CHANGE_REQUEST => true,
        ]));
        Tinebase_Container::getInstance()->setGrants($d->container_id, $grants, true, false);


        HumanResources_Controller_FreeTime::getInstance()->get($freeTime->getId());
        HumanResources_Controller_Account::getInstance()->get($freeTime->account_id);
        HumanResources_Controller_Employee::getInstance()->get($freeTime->employee_id);
        $contracts = HumanResources_Controller_Contract::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Contract::class, [
                ['field' => 'employee_id', 'operator' => 'equals', 'value' => $freeTime->getIdFromProperty('employee_id')],
            ]));
        $this->assertSame(1, $contracts->count());
    }
}
