<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class Tinebase_Helper_Algorithm_TerminationDeadlineTest
 */
class Tinebase_Helper_Algorithm_TerminationDeadlineTest extends TestCase
{
    protected $terminationDeadlineAlgorithm = null;

    /**
     * Test termination deadline calculation for a new contract create the 1.1. of the current year
     */
    public function testGetTerminationDeadline()
    {
        $commencement = new Tinebase_DateTime(date('Y-m-d', strtotime('first day of January this year')));
        $termOfContractInMonths = 12;
        $automaticContractExtensionInMonths = 12;
        $cancelationPeriodInMonths = 3;

        $terminationDeadline = Tinebase_Helper_Algorithm_TerminationDeadline::getInstance()->getTerminationDeadline(
            $commencement,
            $termOfContractInMonths,
            $automaticContractExtensionInMonths,
            $cancelationPeriodInMonths,
            (new Tinebase_DateTime())->setWeek(50)->setWeekDay(1)
        );

        $expectedTerminationDeadline = (clone $commencement)->addYear(2)->subMonth($cancelationPeriodInMonths)->subDay(1);
        $this->assertEquals($expectedTerminationDeadline, $terminationDeadline);
    }

    public function testGetTerminationDeadlineWithSixMonthExtension()
    {
        $commencement = new Tinebase_DateTime(date('Y-m-d', strtotime('first day of January this year')));
        $termOfContractInMonths = 12;
        $automaticContractExtensionInMonths = 6;
        $cancelationPeriodInMonths = 3;

        $terminationDeadline = Tinebase_Helper_Algorithm_TerminationDeadline::getInstance()->getTerminationDeadline(
            $commencement,
            $termOfContractInMonths,
            $automaticContractExtensionInMonths,
            $cancelationPeriodInMonths,
            (new Tinebase_DateTime())->setWeek(50)->setWeekDay(1)
        );

        $expectedTerminationDeadline = (clone $commencement)->addYear(1)->addMonth($automaticContractExtensionInMonths)->subMonth($cancelationPeriodInMonths)->subDay(1);
        $this->assertEquals($expectedTerminationDeadline, $terminationDeadline);
    }

    /**
     * Test termination deadline for a contract existing for 2 years
     */
    public function testGetTerminationDeadlineForExtendedContract()
    {
        $commencement = (new Tinebase_DateTime())->setWeek(1)->setWeekDay(1)->subYear(2);
        $termOfContractInMonths = 12;
        $automaticContractExtensionInMonths = 12;
        $cancelationPeriodInMonths = 3;

        $terminationDeadline = Tinebase_Helper_Algorithm_TerminationDeadline::getInstance()->getTerminationDeadline(
            $commencement,
            $termOfContractInMonths,
            $automaticContractExtensionInMonths,
            $cancelationPeriodInMonths,
            (new Tinebase_DateTime())->setWeek(50)->setWeekDay(1)
        );

        $expectedTerminationDeadline = (clone $commencement)->addYear(4)->subMonth($cancelationPeriodInMonths)->subDay(1);
        $this->assertEquals($expectedTerminationDeadline, $terminationDeadline);
    }
}