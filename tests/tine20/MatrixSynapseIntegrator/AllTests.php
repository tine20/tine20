<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     MatrixSynapseIntegrator
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * All MatrixSynapseIntegrator tests
 *
 * @package     MatrixSynapseIntegrator
 */
class MatrixSynapseIntegrator_AllTests
{


    public static function suite ()
    {
        $suite = new PHPUnit\Framework\TestSuite('All MatrixSynapseIntegrator tests');

        $suite->addTestSuite(MatrixSynapseIntegrator_ControllerTests::class);

        return $suite;
    }
}
