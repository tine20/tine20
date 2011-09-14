<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Model_Filter_Text
 * 
 * @package     Tinebase
 */

class Tinebase_Model_Filter_TextTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyStringValues()
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id',          'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id),
            array('field' => 'org_unit',    'operator' => 'equals', 'value' => ''),
        ));
        $this->assertEquals(1, count(Addressbook_Controller_Contact::getInstance()->search($filter)), 'org_unit is NULL and should be included with empty string');
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id',          'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id),
            array('field' => 'n_fileas',    'operator' => 'equals', 'value' => ''),
        ));
        $this->assertEquals(0, count(Addressbook_Controller_Contact::getInstance()->search($filter)), 'n_fileas is set and should not be included with empty string');
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id',          'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id),
            array('field' => 'org_unit',    'operator' => 'not',    'value' => ''),
        ));
        $this->assertEquals(0, count(Addressbook_Controller_Contact::getInstance()->search($filter)), 'org_unit is NULL and should be not included with empty string with not operator');
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id',          'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id),
            array('field' => 'n_fileas',    'operator' => 'not',    'value' => ''),
        ));
        $this->assertEquals(1, count(Addressbook_Controller_Contact::getInstance()->search($filter)), 'n_fileas is set and should be included with empty string and not operator');
    }
}