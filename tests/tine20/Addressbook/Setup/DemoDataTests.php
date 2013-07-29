<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Setup DemoData
 * 
 * @package     Addressbook
 */
class Addressbook_Setup_DemoDataTests extends PHPUnit_Framework_TestCase
{
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
         Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    public function tearDown()
    {
         Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * tests if creating reduced demodata is possible
     */
    public function testCreateReducedDemoData()
    {
        ob_start();
        Addressbook_Setup_DemoData::getInstance()->createDemoData(array('locale' => 'de'));
        ob_end_clean();
        
        $c = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), 'Addressbook', FALSE);
        $id = $c->getId();
        $id = $id[1];
        $filter = new Addressbook_Model_ContactFilter(array(array('field' => 'container_id', 'operator' => 'equals', 'value' => $id)));
        $result = Addressbook_Controller_Contact::getInstance()->search($filter);
        $this->assertEquals(20, count($result));
    }
}
