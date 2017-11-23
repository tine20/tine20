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
 * Class Tinebase_Helper_Algorithm_TerminationDeadline
 *
 * Calculates a termination deadline by the following criteria:
 *
 *  - Commencement of contract
 *  - Term of contract in months
 *  - Automatic contract extension in months
 *  - Cancelation period in months
 *
 *
 * @todo: Terminatation effective, is not implemented yet.
 *  - Termination effective
 *    - End of month
 *    - End of quarter
 *    - End of half-year
 *    - End of year
 */
class Tinebase_Helper_Algorithm_TerminationDeadline
{
    const END_OF_MONTH = 'end_of_month';
    const END_OF_QUARTER = 'end_of_quarter';
    const END_OF_HALF_YEAR = 'end_of_half_year';
    const END_OF_YEAR = 'end_of_year';

    /**
     * @var Tinebase_Helper_Algorithm_TerminationDeadline
     */
    private static $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @return Tinebase_Helper_Algorithm_TerminationDeadline
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Tinebase_DateTime $commencement
     * @param $termOfContractInMonths
     * @param $automaticContractExtensionInMonths
     * @param $cancelationPeriodInMonths
     * @param null|Tinebase_DateTime $today
     * @return Tinebase_DateTime
     */
    public function getTerminationDeadline(
        Tinebase_DateTime $commencement,
        $termOfContractInMonths,
        $automaticContractExtensionInMonths,
        $cancelationPeriodInMonths,
        $today = null
    ) {
        $today = $today ?: Tinebase_DateTime::now();

        // Without renewal consideration
        $contractEndDate = (clone $commencement)->addMonth($termOfContractInMonths);

        // Calculate end date including renewals
        $commencementCopy = clone $commencement;
        for (
            $commencementCopy->addMonth($termOfContractInMonths);
            (clone $commencementCopy)->subMonth($cancelationPeriodInMonths) < $today;
            $commencementCopy->addMonth($automaticContractExtensionInMonths)
        ) {
            $contractEndDate = $commencementCopy;
        }

        return $contractEndDate->subMonth($cancelationPeriodInMonths)->subDay(1);
    }

    /**
     * Finds the next yearly quarter date of a given date
     *
     * @param Tinebase_DateTime $date
     * @return float|int
     */
    protected function getQuarterOfDate(Tinebase_DateTime $date)
    {
        $month = $date->format('n');
        return floor(($month - 1) / 3) + 1;
    }
}