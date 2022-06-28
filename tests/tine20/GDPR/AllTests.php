<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     GDPR
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * All GDPR tests
 *
 * @package     GDPR
 */
class GDPR_AllTests
{
    public static function suite ()
    {
        $suite = new PHPUnit\Framework\TestSuite('All GDPR tests');

        $suite->addTestSuite(GDPR_Controller_DataIntendedPurposeRecordTest::class);
        $suite->addTestSuite(GDPR_Controller_DataIntendedPurposeTest::class);
        $suite->addTestSuite(GDPR_Controller_DataProvenanceTest::class);
        $suite->addTestSuite(GDPR_Frontend_JsonTest::class);
        $suite->addTestSuite(GDPR_Model_DataProvenanceTest::class);

        return $suite;
    }
}
