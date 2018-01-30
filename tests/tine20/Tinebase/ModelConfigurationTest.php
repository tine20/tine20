<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_ModelConfiguration, using the test class from hr
 */
class Tinebase_ModelConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * tests if the modelconfiguration gets created for the traditional models
     */
    public function testModelCreationTraditional()
    {
        $contact = new Addressbook_Model_Industry([], true);
        $cObj = $contact->getConfiguration();

        // at first this is just null
        $this->assertNull($cObj);
    }

    /**
     * tests if the modelconfiguration is created for foreign record keys that are disabled by a feature switch
     */
    public function testModelConfigWithDisabledForeignRecords()
    {
        // TODO disable feature first
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $this->markTestSkipped('only testable when disabled');
        }

        $timesheet = new Timetracker_Model_Timesheet(array(), true);
        $mcFields = $timesheet->getConfiguration()->getFields();
        $this->assertEquals('string', $mcFields['invoice_id']['type']);
        $this->assertEquals(null, $mcFields['invoice_id']['label']);
    }
}
