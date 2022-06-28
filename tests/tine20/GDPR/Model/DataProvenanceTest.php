<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     GDPR
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test class for GDPR_Model_DataProvenance
 */
class GDPR_Model_DataProvenanceTest extends TestCase
{
    public function testModelConstraints()
    {
        $expirationDate = Tinebase_DateTime::now();
        $dataProvenance = new GDPR_Model_DataProvenance([
            'name'              => 'test',
            'expiration'        => $expirationDate,
        ]);
        static::assertEquals('test', $dataProvenance->name);
        static::assertEquals($expirationDate, $dataProvenance->expiration);

        try {
            new GDPR_Model_DataProvenance([
                'expiration'        => $expirationDate,
            ]);
            static::fail('name should be mandatory');
        } catch (Tinebase_Exception_Record_Validation $terv) {}
    }
}