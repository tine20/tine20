<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Courses_JsonTest::main');
}

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
        $this->_json = new Courses_Frontend_Json();        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {        
    }
    
    /**
     * try to add a Course
     *
     */
    public function testAddCourse()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse(Zend_Json::encode($course));
        
        //print_r($courseData);
        
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
     *
     */
    public function testGetCourse()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse(Zend_Json::encode($course));
        $courseData = $this->_json->getCourse($courseData['id']);
        
        // checks
        $this->assertEquals($course['description'], $courseData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $courseData['created_by']);
                        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }

    /**
     * try to update a Course
     *
     */
    public function testUpdateCourse()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse(Zend_Json::encode($course));

        //print_r($courseData);
        
        // update Course
        $courseData['description'] = "blubbblubb";
        $courseData['members'] = array();
        $courseData['type'] = $courseData['type']['value'];
        $courseUpdated = $this->_json->saveCourse(Zend_Json::encode($courseData));
        
        //print_r($courseUpdated);
        
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
     *
     */
    public function testSearchCourses()
    {
        // create
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse(Zend_Json::encode($course));
        
        // search & check
        $search = $this->_json->searchCourses(Zend_Json::encode($this->_getCourseFilter($courseData['name'])), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($course['description'], $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }
       
    /**
     * test for import of members
     *
     */
    public function testImportMembersIntoCourse()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse(Zend_Json::encode($course));
        
        // import data
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('admin_user_import_csv');
        $importer = new $definition->plugin(
            $definition, 
            Admin_Controller_User::getInstance(),
            array(
                'group_id'                  => $courseData['group_id'],
                'accountLoginNamePrefix'    => $courseData['name'] . '_',
                'encoding'                  => 'ISO8859-1'            
            )
        );
        $importer->import(dirname(dirname(__FILE__)) . '/Admin/files/testHeadline.csv');
        $courseData = $this->_json->getCourse($courseData['id']);
        
        // checks
        $this->assertEquals(4, count($courseData['members']));
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
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
            'type'          => Tinebase_Record_Abstract::generateUID(),
            /*'members'       => array(
                Tinebase_Core::getUser()->getId(),
            )
            */
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
}
