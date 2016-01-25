<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     CoreData
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for CoreData_JsonTest
 */
class CoreData_JsonTest extends TestCase
{
    /**
     * unit in test
     *
     * @var CoreData_Frontend_Json
     */
    protected $_uit = null;

    /**
     * set up tests
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_uit = new CoreData_Frontend_Json();
    }

    /**
     * testGetCoreData
     */
    public function testGetCoreData()
    {
        $result = $this->_uit->getCoreData();

        $this->assertGreaterThan(0, $result['totalcount'], print_r($result, true));

        // look for 'lists'
        $lists = null;
        foreach ($result['results'] as $coreData) {
            if ($coreData['id'] === 'adb_lists') {
                $lists = $coreData;
            }
        }
        $this->assertTrue($lists !== null);
        $this->assertEquals('Addressbook_Model_List', $lists['model'], print_r($lists, true));
    }
}
