<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Frontend_WebDAV_Container
 */
class Calendar_Frontend_WebDAV_ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV Container Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        )));
        
        Tinebase_Container::getInstance()->addGrants(
            $this->objects['initialContainer'],
            Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            Tinebase_Core::getUser()->accountPrimaryGroup,
            array(Tinebase_Model_Grants::GRANT_READ)
        );

        // rw cal agent
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        // must be defined for Calendar/Frontend/WebDAV/Event.php
        $_SERVER['REQUEST_URI'] = 'foobar';
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        Calendar_Config::getInstance()->set(Calendar_Config::SKIP_DOUBLE_EVENTS, '');
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetName()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->name, $result);
    }
    
    public function testGetOwner()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getOwner();
        
        $this->assertEquals('principals/users/' . Tinebase_Core::getUser()->contact_id, $result);
    }
    
    public function testGetACL()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getACL();
        
        static::assertEquals(6, count($result));

        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->objects['initialContainer'], true);
        $grantsClass = $grants->getRecordClassName();
        $grants->addRecord(new $grantsClass(array(
            'account_id'    => Tinebase_Acl_Roles::getInstance()->getRoleByName('user role')->getId(),
            'account_type'  => 'role',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        )));
        Tinebase_Container::getInstance()->setGrants($this->objects['initialContainer'], $grants, true);

        $result = $container->getACL();

        static::assertEquals(11, count($result));
    }
    
    public function testGetIdAsName()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer'], true);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->getId(), $result);
    }
    
    /**
     * test getProperties
     */
    public function testGetProperties()
    {
        $this->testCreateFile();
        
        $requestedProperties = array(
            '{http://calendarserver.org/ns/}getctag',
            '{DAV:}resource-id'
        );
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getProperties($requestedProperties);
       
        $this->assertTrue(! empty($result['{http://calendarserver.org/ns/}getctag']));
        $this->assertEquals($result['{DAV:}resource-id'], 'urn:uuid:' . $this->objects['initialContainer']->getId());
    }
    
    /**
     * test updateProperties of calendar folder
     */
    public function testUpdateProperties()
    {
        $this->testCreateFile();
        
        $mutations = array(
            '{http://apple.com/ns/ical/}calendar-color'      => '#123456FF',
            '{http://apple.com/ns/ical/}calendar-order'      => 2,
            '{DAV:}displayname'                              => 'testUpdateProperties',
            '{http://calendarserver.org/ns/}invalidProperty' => null
        );
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->updateProperties($mutations);
        
        $updatedContainer = Tinebase_Container::getInstance()->get($this->objects['initialContainer']);
        
        $this->assertEquals($result[200]["{http://apple.com/ns/ical/}calendar-color"],      null);
        $this->assertEquals($result[200]["{http://apple.com/ns/ical/}calendar-order"],      null);
        $this->assertEquals($result[200]["{DAV:}displayname"],                              null);
        $this->assertEquals($result[403]["{http://calendarserver.org/ns/}invalidProperty"], null);
        $this->assertEquals($updatedContainer->color, substr($mutations['{http://apple.com/ns/ical/}calendar-color'], 0, 7));
        $this->assertEquals($updatedContainer->order,  2);
        $this->assertEquals($updatedContainer->name,  $mutations['{DAV:}displayname']);
    }

    /**
     * test getCreateFile
     *
     * @param boolen $_useNumericId
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateFile($_useNumericId = false, $skipAssertions = false, $file = '/../../Import/files/lightning.ics')
    {
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . $file);

        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);

        if (true === $_useNumericId) {
            $id = (string)rand(10000, 100000000);
        } else {
            $id = Tinebase_Record_Abstract::generateUID();
        }

        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics",
            $vcalendarStream);

        if (!$skipAssertions) {
            $record = $event->getRecord();
            $this->assertTrue($event instanceof Calendar_Frontend_WebDAV_Event);
            $this->assertEquals($id, $record->getId(), 'ID mismatch');
        }
        
        return $event;
    }
    
    /**
     * test getChildren
     * 
     */
    public function testGetChildren($skipAssertions = true)
    {
        $event = $this->testCreateFile(false, $skipAssertions)->getRecord();
        
        // reschedule to match period filter
        $event->dtstart = Tinebase_DateTime::now();
        $event->dtend = Tinebase_DateTime::now()->addMinute(30);
        Calendar_Controller_MSEventFacade::getInstance()->update($event);
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $children = $container->getChildren();
        
        if (! $skipAssertions) {
            $this->assertEquals(1, count($children));
            $this->assertTrue($children[$event->getId()] instanceof Calendar_Frontend_WebDAV_Event);
        }
        
        return $children;
    }
    
    /*public function testGetChildrenSkipDoubleEvents()
    {
        Calendar_Config::getInstance()->set(Calendar_Config::SKIP_DOUBLE_EVENTS, 'personal');
        $children = $this->testGetChildren(true);
        $this->assertEquals(0, count($children));
        
        Calendar_Config::getInstance()->set(Calendar_Config::SKIP_DOUBLE_EVENTS, 'shared');
        $children = $this->testGetChildren(true);
        $this->assertEquals(1, count($children));
    }*/
    
    /**
     * test calendarQuery with start and end time set
     * 
     * @param boolean $timeRangeEndSet
     * @param boolean $removeOwnAttender
     */
    public function testCalendarQuery($timeRangeEndSet = true, $removeOwnAttender = false)
    {
        $event = $this->testCreateFile()->getRecord();
        
        // reschedule to match period filter
        $event->dtstart = Tinebase_DateTime::now();
        $event->dtend   = Tinebase_DateTime::now()->addMinute(30);
        if ($removeOwnAttender) {
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id'   => Tinebase_User::getInstance()->getUserByLoginName('sclever')->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                )
            ));
        }
        Calendar_Controller_MSEventFacade::getInstance()->update($event);
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $timeRange = $timeRangeEndSet ? array(
            'start' => Tinebase_DateTime::now()->subHour(1),
            'end'   => Tinebase_DateTime::now()->addHour(1)
        ) : array(
            'start' => Tinebase_DateTime::now()->subHour(1),
        );
        
        $urls = $container->calendarQuery(array(
            'name'         => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name'           => 'VEVENT',
                    'comp-filters'   => array(),
                    'prop-filters'   => array(),
                    'is-not-defined' => false,
                    'time-range'     => $timeRange,
                ),
            ),
            'prop-filters'   => array(),
            'is-not-defined' => false,
            'time-range'     => null
        ));
        
        $this->assertContains($event->getId() . '.ics', $urls);
    }
    
    /**
     * test calendarQuery with start time set only
     */
    public function testCalendarQueryNoEnd()
    {
        $this->testCalendarQuery(false);
    }
    
    /**
     * testCalendarQueryNotAttender
     * 
     * @see 0009204: "Foreign" events won't sync/show up via CalDAV.
     */
    public function testCalendarQueryNotAttender()
    {
        $this->testCalendarQuery(true, true);
    }
    
    /**
     * test calendarQuery with start and end time set
     * 
     * @param boolean $timeRangeEndSet
     * @param boolean $removeOwnAttender
     */
    public function testCalendarQueryPropertyFilter()
    {
        $event1 = $this->testCreateFile()->getRecord();
        $event2 = $this->testCreateFile(false, false, '/../../Import/files/lightning_allday.ics')->getRecord();
        
        // reschedule to match period filter
        $event1->dtstart = Tinebase_DateTime::now();
        $event1->dtend   = Tinebase_DateTime::now()->addMinute(30);
        Calendar_Controller_MSEventFacade::getInstance()->update($event1);
        
        $event2->dtstart = Tinebase_DateTime::now();
        $event2->dtend   = Tinebase_DateTime::now()->addMinute(30);
        Calendar_Controller_MSEventFacade::getInstance()->update($event2);
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $urls = $container->calendarQuery(array(
            'name'         => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name'           => 'VEVENT',
                    'prop-filters'   => array(
                        array(
                            'name' => 'UID',
                            'text-match' => array(
                                'value' => $event1->getId()
                            )
                        ),
                        array(
                            'name' => 'UID',
                            'text-match' => array(
                                'value' => $event2->getId()
                            )
                        )
                    )
                ),
            ),
            'prop-filters'   => array(),
            'is-not-defined' => false,
            'time-range'     => null
        ));
        
        $this->assertContains($event1->getId() . '.ics', $urls);
        $this->assertContains($event2->getId() . '.ics', $urls);
        $this->assertEquals(2, count($urls));
    }
    /**
     * test Tinebase_WebDav_Container_Abstract::getCalendarVTimezone
     */
    public function testGetCalendarVTimezone()
    {
        $vTimezone = Tinebase_WebDav_Container_Abstract::getCalendarVTimezone('Calendar');
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar', $vTimezone);
        
        $vTimezone = Tinebase_WebDav_Container_Abstract::getCalendarVTimezone(Tinebase_Application::getInstance()->getApplicationByName('Calendar'));
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar', $vTimezone);
    }
    
    /**
     * test Calendar_Frontend_WebDAV_Container::getShares
     */
    public function testGetShares()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $shares = $container->getShares();

        $this->assertEquals(2, count($shares), 'should find 2 shares');
        $hrefsToFind = array(
            'urn:uuid:anyone',
            'urn:uuid:'
                . Tinebase_Group::getInstance()->getGroupById(Tinebase_Core::getUser()->accountPrimaryGroup)->list_id
        );
        $found = 0;
        foreach ($shares as $share) {
            if (in_array($share['href'], $hrefsToFind)) {
                $found++;
            }
        }
        self::assertEquals(2, $found, 'should find 2 hrefs in shares: ' . print_r($hrefsToFind, true));
    }
    
    /**
     * test Calendar_Frontend_WebDAV_Container::getShares for container user has no admin grant for
     */
    public function testGetSharesWithoutRights()
    {
        $jmcblack = Tinebase_Helper::array_value('jmcblack', Zend_Registry::get('personas'));
        $jmcblacksCalId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $jmcblack->getId());
        $jmcblacksCal = Tinebase_Container::getInstance()->get($jmcblacksCalId);
        
        $container = new Calendar_Frontend_WebDAV_Container($jmcblacksCal);
    
        $shares = $container->getShares();
    
        $this->assertEquals(1, count($shares));
        $this->assertTrue((bool)$shares[0]['readOnly']);
    }
    
    /**
     * return vcalendar as string and replace organizers email address with emailaddess of current user
     * 
     * @param string $_filename  file to open
     * @return string
     */
    protected function _getVCalendar($_filename)
    {
        $vcalendar = file_get_contents($_filename);
        
        $vcalendar = preg_replace('/l.kneschke@metaway\n s.de/', Tinebase_Core::getUser()->accountEmailAddress, $vcalendar);
        
        return $vcalendar;
    }

    /**
     * this test will test for event with a numeric id, which caused issues with postgres
     * see https://forge.tine20.org/view.php?id=13494
     */
    public function testGetChanges()
    {
        // create an event with numeric id -> potential postgres issue, fixed in \Calendar_Backend_Sql::getUidOfBaseEvents
        $event = $this->testCreateFile(true);

        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        $result = $container->getChanges('0');

        static::assertTrue(isset($result['create'][$event->getRecord()->uid]));
    }
}
