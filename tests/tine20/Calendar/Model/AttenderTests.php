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

/***************** NOTE: not yet active *****************/


/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Model_AttenderTests::main');
}

/**
 * Test class for Calendar_Model_Attender
 * 
 * @package     Calendar
 */
class Calendar_Model_AttenderTests extends Calendar_TestCase
{
    /**
     * @todo test tine side deleted user
     * 
     * @return unknown_type
     */
    public function testEmailsToAttendee()
    {
    	$event = $this->_getEvent();
        $persitentEvent = $this->_controller->create($event);
        
        $sclever = Addressbook_Controller_Contact::getInstance()->get(Tinebase_User::getInstance()->getUserByLoginName('sclever')->contact_id);
        $newEmail = Tinebase_Record_Abstract::generateUID() . '@unittest.com';
        
        $emails = array(
            $sclever->email,
            $newEmail
        );
        
        Calendar_Model_Attender::emailsToAttendee($persitentEvent, $emails, TRUE);
        
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Model_AttenderTests::main') {
    Calendar_Model_AttenderTests::main();
}
