<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */


/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Model_Attender
 * 
 * @package     Calendar
 */
class Addressbook_Model_ContactIdFilterTest extends Calendar_TestCase
{
    public function testToArrayJsonSelf()
    {
        $filter = new Addressbook_Model_ContactIdFilter('id', 'equals', Addressbook_Model_Contact::CURRENTCONTACT);
        $filterArray = $filter->toArray(TRUE);
        
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $filterArray['value']['id']);
    }
    
    public function testToArrayJsonNonExisting()
    {
        $nonExistingId = Tinebase_Record_Abstract::generateUID();
        $filter = new Addressbook_Model_ContactIdFilter('id', 'equals', $nonExistingId);
        $filterArray = $filter->toArray(TRUE);
        
        $this->assertEquals($nonExistingId, $filterArray['value']);
    }
}
