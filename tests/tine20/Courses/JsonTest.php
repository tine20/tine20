<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Courses_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Courses_Frontend_Json
     */
    protected $_json = array();
    
    /**
     * config groups
     * 
     * @var array
     */
    protected $_configGroups = array();
    
    /**
     * test department
     * 
     * @var Tinebase_Model_Department
     */
    protected $_department = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Courses Json Tests');
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
        
        $this->_json = new Courses_Frontend_Json();
        
        $this->_department = Tinebase_Department::getInstance()->create(new Tinebase_Model_Department(array(
            'name'  => Tinebase_Record_Abstract::generateUID()
        )));
        
        foreach (array(Courses_Config::INTERNET_ACCESS_GROUP_ON, Courses_Config::INTERNET_ACCESS_GROUP_FILTERED, Courses_Config::STUDENTS_GROUP) as $configgroup) {
            $this->_configGroups[$configgroup] = Tinebase_Group::getInstance()->create(new Tinebase_Model_Group(array(
                'name'   => $configgroup
            )));
            Courses_Config::getInstance()->set($configgroup, $this->_configGroups[$configgroup]->getId());
        }
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
    }
    
    /**
     * try to add a Course
     */
    public function testAddCourse()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        
        // checks
        $this->assertEquals($course['description'], $courseData['description']);
        $this->assertEquals($course['type'], $courseData['type']['value']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $courseData['created_by'], 'Created by has not been set.');
        $this->assertTrue(! empty($courseData['group_id']));
        $this->assertGreaterThan(0, count($courseData['members']));
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);

        // check if it got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Courses_Controller_Course::getInstance()->get($courseData['id']);
    }
    
    /**
     * try to get a Course
     */
    public function testGetCourse()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $courseData = $this->_json->getCourse($courseData['id']);
        
        // checks
        $this->assertEquals($course['description'], $courseData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $courseData['created_by']);
                        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }

    /**
     * try to update a Course
     */
    public function testUpdateCourse()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);

        // update Course
        $courseData['description'] = "blubbblubb";
        $courseData['members'] = array();
        $courseData['type'] = $courseData['type']['value'];
        $courseUpdated = $this->_json->saveCourse($courseData);
        
        // check
        $this->assertEquals($courseData['id'], $courseUpdated['id']);
        $this->assertEquals($courseData['description'], $courseUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $courseUpdated['last_modified_by']);
        $this->assertEquals($courseData['members'], $courseUpdated['members']);
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }
    
    /**
     * try to get a Course
     */
    public function testSearchCourses()
    {
        // create
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        
        // search & check
        $search = $this->_json->searchCourses($this->_getCourseFilter($courseData['name']), $this->_getPaging());
        $this->assertEquals($course['description'], $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }
       
    /**
     * test for import of members (1)
     */
    public function testImportMembersIntoCourse1()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('admin_user_import_csv');
        $result = $this->_importHelper(dirname(dirname(__FILE__)) . '/Admin/files/testHeadline.csv', $definition);

        $this->assertEquals(4, count($result['members']), print_r($result, TRUE));
    }

    /**
     * test for import of members (2)
     */
    public function testImportMembersIntoCourse2()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/import.txt');
        
        $this->assertEquals(5, count($result['members']), print_r($result, TRUE));
        
        // find philipp lahm
        $lahm = array();
        foreach ($result['members'] as $member) {
            if ($member['name'] == 'Lahm, Philipp') {
                $lahm = $member;
            }
        }
        $this->assertTrue(! empty($lahm));
        $this->assertEquals('lahmph', $lahm['data']);
        
        // get user and check email
        $testConfig = Zend_Registry::get('testConfig');
        $maildomain = ($testConfig->maildomain) ? $testConfig->maildomain : 'school.org';
        $user = Tinebase_User::getInstance()->getFullUserById($lahm['id']);
        $this->assertEquals('lahmph', $user->accountLoginName);
        $this->assertEquals('lahmph@' . $maildomain, $user->accountEmailAddress);
        $this->assertEquals('//base/school/' . $result['name'] . '/' . $user->accountLoginName, $user->accountHomeDirectory);
    }
    
    /**
     * test for import of members (3) / json import
     */
    public function testImportMembersIntoCourse3()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/import.txt', NULL, TRUE);
        $this->assertEquals(5, count($result['members']), 'import failed');
        $this->assertEquals(5, count(Tinebase_Group::getInstance()->getGroupMembers($this->_configGroups[Courses_Config::STUDENTS_GROUP])), 'imported users not added to students group');
    }
    
    /**
     * testGetCoursesPreferences
     * 
     * @see 0006436: Courses preferences do not work (in pref panel)
     */
    public function testGetCoursesPreferences()
    {
        $tinebaseJson = new Tinebase_Frontend_Json();
        $coursesPrefs = $tinebaseJson->searchPreferencesForApplication('Courses', array());
        
        $this->assertTrue($coursesPrefs['totalcount'] > 0);
        $pref = $coursesPrefs['results'][0];
        
        $this->assertEquals(Tinebase_Preference_Abstract::DEFAULTPERSISTENTFILTER, $pref['name']);
        $this->assertEquals(2, count($pref['options']));
    }

    /**
     * test internet access on/off/filtered
     * 
     * @todo remove some code duplication
     */
    public function testInternetAccess()
    {
        // create new course with internet access
        $course = $this->_getCourseData();
        $course['internet'] = 'ON';
        $courseData = $this->_json->saveCourse($course);
        $userId = $courseData['members'][0]['id'];
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        $this->assertTrue(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $groupMemberships), $userId . ' not member of the internet group ' . print_r($groupMemberships, TRUE));
        sleep(1); // modlog issue
        
        // filtered internet access
        $courseData['internet'] = 'FILTERED';
        $courseData['type'] = $courseData['type']['value'];
        $courseData = $this->_json->saveCourse($courseData);
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        $this->assertTrue(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_FILTERED]->getId(), $groupMemberships), 'not member of the filtered internet group ' . print_r($groupMemberships, TRUE));
        $this->assertFalse(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $groupMemberships), 'member of the internet group ' . print_r($groupMemberships, TRUE));
        sleep(1); // modlog issue
        
        // remove internet access
        $courseData['internet'] = 'OFF';
        $courseData['type'] = $courseData['type']['value'];
        $courseData = $this->_json->saveCourse($courseData);
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        $this->assertFalse(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $groupMemberships), 'member of the internet group ' . print_r($groupMemberships, TRUE));
        $this->assertFalse(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_FILTERED]->getId(), $groupMemberships), 'member of the filtered internet group ' . print_r($groupMemberships, TRUE));
    }
    
    /**
     * testAddNewMember
     */
    public function testAddNewMember()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $courseData['type'] = $courseData['type']['value'];
        
        $result = $this->_json->addNewMember(array(
            'accountFirstName' => 'jams',
            'accountLastName'  => 'hot',
        ), $courseData);
        
        $this->assertEquals(2, count($result['results']));
    }
    
    /************ protected helper funcs *************/
    
    /**
     * get Course
     *
     * @return array
     */
    protected function _getCourseData()
    {
        return array(
            'name'          => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
            'type'          => $this->_department->getId(),
        );
    }
        
    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'creation_time',
            'dir' => 'ASC',
        );
    }

    /**
     * get Course filter
     *
     * @return array
     */
    protected function _getCourseFilter($_courseName)
    {
        return array(
            array(
                'field' => 'name', 
                'operator' => 'contains', 
                'value' => $_courseName
            ),     
        );
    }
    
    /**
     * import file
     * 
     * @param string $_filename
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @return array course data
     */
    protected function _importHelper($_filename, Tinebase_Model_ImportExportDefinition $_definition = NULL, $_useJsonImportFn = FALSE)
    {
        $definition = ($_definition !== NULL) ? $_definition : $this->_getCourseImportDefinition();
        
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        
        $this->_coursesToDelete[] = $courseData['id'];
        
        if ($_useJsonImportFn) {
            $tempFileBackend = new Tinebase_TempFile();
            $tempFile = $tempFileBackend->createTempFile($_filename);
            Courses_Config::getInstance()->set(Courses_Config::STUDENTS_IMPORT_DEFINITION, 'course_user_import_csv');
            $result = $this->_json->importMembers($tempFile->getId(), $courseData['group_id'], $courseData['id']);
            
            $this->assertGreaterThan(0, $result['results']);
            
        } else {
            $testConfig = Zend_Registry::get('testConfig');
            $maildomain = ($testConfig->maildomain) ? $testConfig->maildomain : 'school.org';
            
            $importer = call_user_func($definition->plugin . '::createFromDefinition', $definition, array(
                    'group_id'                  => $courseData['group_id'],
                    'accountHomeDirectoryPrefix' => '//base/school/' . $courseData['name'] . '/',
                    'accountEmailDomain'        => $maildomain,
                    'password'                  => $courseData['name'],
                    'samba'                     => array(
                        'homePath'    => '//basehome/',
                        'homeDrive'   => 'H:',
                        'logonScript' => 'logon.bat',
                        'profilePath' => '\\\\profile\\',
                    )
                )
            );
            $importer->importFile($_filename);
        }
        $courseData = $this->_json->getCourse($courseData['id']);

        return $courseData;
    }
    
    /**
     * returns course import definition
     * 
     * @return Tinebase_Model_ImportExportDefinition
     */
    protected function _getCourseImportDefinition()
    {
        try {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('course_user_import_csv');
        } catch (Tinebase_Exception_NotFound $e) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->create(new Tinebase_Model_ImportExportDefinition(array(
                    'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Admin')->getId(),
                    'name'              => 'course_user_import_csv',
                    'type'              => 'import',
                    'model'             => 'Tinebase_Model_FullUser',
                    'plugin'            => 'Admin_Import_Csv',
                    'plugin_options'    => '<?xml version="1.0" encoding="UTF-8"?>
            <config>
                <headline>1</headline>
                <use_headline>0</use_headline>
                <dryrun>0</dryrun>
                <encoding>UTF-8</encoding>
                <delimiter>;</delimiter>
                <mapping>
                    <field>
                        <source>lastname</source>
                        <destination>accountLastName</destination>
                    </field>
                    <field>
                        <source>firstname</source>
                        <destination>accountFirstName</destination>
                    </field>
                </mapping>
            </config>')
            ));
        }
        
        return $definition;
    }
}
