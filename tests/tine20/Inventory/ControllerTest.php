<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Inventory
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Inventory_ControllerTest
 */
class Inventory_ControllerTest extends Inventory_TestCase
{
    /**
     * @group nogitlabci
     */
    public function testGetModels()
    {
        $models = Inventory_Controller::getInstance()->getModels();

        $this->assertEquals(array(
            'Inventory_Model_InventoryItem',
            'Inventory_Model_Status'
        ), $models);
    }
}
