<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     EFile
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * All EFile tests
 *
 * @package     EFile
 */
class EFile_AllTests
{
    public static function suite ()
    {
        $suite = new PHPUnit\Framework\TestSuite('All EFile tests');

        $suite->addTestSuite(EFile_EFileNodeTest::class);

        return $suite;
    }
}
