<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Crm_Export_Ods
 */
class Crm_Export_OdsTest extends Crm_Export_AbstractTest
{
    /**
     * csv export class
     *
     * @var Crm_Export_Ods
     */
    protected $_instance;
    
    /**
     * export file
     * 
     * @var string
     */
    protected $_filename;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_container = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm_Export_OdsTest');
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
        $this->_instance = Tinebase_Export::factory(new Crm_Model_LeadFilter($this->_getLeadFilter()), 'ods');
        parent::setUp();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // set grants again
        if ($this->_container !== null) {
            Tinebase_Container::getInstance()->setGrants($this->_container, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
                'account_id'    => Tinebase_Core::getUser()->getId(),
                'account_type'  => 'user',
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => true,
                Tinebase_Model_Grants::GRANT_EXPORT    => true,
                Tinebase_Model_Grants::GRANT_SYNC      => true,
                Tinebase_Model_Grants::GRANT_ADMIN     => true,
            ))), TRUE);
        }

        unlink($this->_filename);
        
        parent::tearDown();
    }
    
    /**
     * test ods export
     * 
     * @return void
     * 
     * @todo add assertions for relations / other fields
     */
    public function testExportOds()
    {
        $this->_filename = $this->_instance->generate();
        
        $this->assertTrue(file_exists($this->_filename));
        
        $xmlBody = $this->_instance->getDocument()->asXML();
        $this->assertEquals(1, preg_match("/PHPUnit/",      $xmlBody), 'no name');
        $this->assertEquals(1, preg_match("/Description/",  $xmlBody), 'no description');
        $this->assertEquals(1, preg_match('/open/',         $xmlBody), 'no leadstate');
        $this->assertEquals(1, preg_match('/Admin Account, Tine 2\.0/',        $xmlBody), 'no creator');
        $this->assertEquals(1, preg_match('/Tine 2\.0 Admin Account/',         $xmlBody), 'no container name');
    }

    /**
     * test ods export without export grant
     * 
     * @return void
     */
    public function testExportOdsWithoutGrant()
    {
        // remove all grants for container
        $this->_container = Tinebase_Container::getInstance()->getDefaultContainer('Crm')->getId();
        Tinebase_Container::getInstance()->setGrants($this->_container, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ      => true,
        ))), TRUE, FALSE);
        
        $this->_filename = $this->_instance->generate();
        
        $this->assertTrue(file_exists($this->_filename));
        
        $xmlBody = $this->_instance->getDocument()->asXML();
        //echo  $xmlBody;
        $this->assertEquals(0, preg_match("/PHPUnit/",      $xmlBody), 'grant not forced');
    }
}
