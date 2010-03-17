<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Model_AttenderFilterTests::main');
}

/**
 * Test class for Calendar_Model_Attender
 * 
 * @package     Calendar
 */
class Calendar_Model_AttenderFilterTests extends Calendar_TestCase
{
    public function testSetFromSimpleArray() {
        $filterArray = array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['sclever']->getId()
        );
        
        $filterModel = new Calendar_Model_AttenderFilter('attendee', 'equals', $filterArray);
        
        $tvalue = $filterModel->getValue();
        $this->assertEquals(1, count($tvalue), 'only one attender should be set');
        $this->assertEquals($filterArray['user_type'], $tvalue[0]['user_type']);
        $this->assertEquals($filterArray['user_id'], $tvalue[0]['user_id']);
    }
    
    public function testSetFromResolvedArray() {
        $filterArray = array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['sclever']->toArray()
        );
        
        $filterModel = new Calendar_Model_AttenderFilter('attendee', 'equals', $filterArray);
        
        $tvalue = $filterModel->getValue();
        $this->assertEquals(1, count($tvalue), 'only one attender should be set');
        $this->assertEquals($filterArray['user_type'], $tvalue[0]['user_type']);
        $this->assertEquals($this->_personasContacts['sclever']->getId(), $tvalue[0]['user_id']);
    }
    
    public function testSetFromMultipleResolvedArray() {
        $filterArray = array(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['sclever']->toArray()
        ), array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['pwulf']->toArray()
        ));
        
        $filterModel = new Calendar_Model_AttenderFilter('attendee', 'in', $filterArray);
        
        $tvalue = $filterModel->getValue();
        $this->assertEquals(2, count($tvalue), 'only one attender should be set');
        $this->assertEquals($filterArray[0]['user_type'], $tvalue[0]['user_type']);
        $this->assertEquals($filterArray[1]['user_type'], $tvalue[1]['user_type']);
        $this->assertEquals($this->_personasContacts['sclever']->getId(), $tvalue[0]['user_id']);
        $this->assertEquals($this->_personasContacts['pwulf']->getId(), $tvalue[1]['user_id']);
    }
    
    
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Model_AttenderFilterTests::main') {
    Calendar_Model_AttenderFilterTests::main();
}
