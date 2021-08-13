<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * All SSO tests
 * 
 * @package     SSO
 */
class SSO_AllTests
{
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('All SSO tests');
        $suite->addTestSuite(SSO_PublicAPITest::class);

        return $suite;
    }
}
