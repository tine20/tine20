<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_User
 */
class Tinebase_FileSystem_RecordAttachmentsTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 filesystem streamwrapper tests');
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
        if (empty(Tinebase_Core::getConfig()->filesdir)) {
            $this->markTestSkipped('filesystem base path not found');
        }
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        Tinebase_FileSystem::getInstance()->initializeApplication(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        
        clearstatcache();
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
        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem();
    }
    
    /**
     * test adding attachments to record
     * 
     * @todo add assertions
     */
    public function testAddRecordAttachments()
    {
        $recordAttachments = Tinebase_FileSystem_RecordAttachments::getInstance();
        
        $record = new Addressbook_Model_Contact(array('n_family' => Tinebase_Record_Abstract::generateUID()));
        $record->setId(Tinebase_Record_Abstract::generateUID());
        
        $recordAttachments->addRecordAttachment($record, 'Test.txt', fopen(__FILE__, 'r'));
        
        $attachments = $this->testGetRecordAttachments($record);
    }
    
    /**
     * test getting record attachments
     * 
     * @todo add assertions
     */
    public function testGetRecordAttachments($record = null)
    {
        $recordAttachments = Tinebase_FileSystem_RecordAttachments::getInstance();
        
        if (!$record) {
            $record = new Addressbook_Model_Contact(array('n_family' => Tinebase_Record_Abstract::generateUID()));
            $record->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $attachments = $recordAttachments->getRecordAttachments($record);
        
        return $attachments;
    }
    
    /**
     * test getting multiple attachments at once
     */
    public function testGetMultipleAttachmentsOfRecords()
    {
        $recordAttachments = Tinebase_FileSystem_RecordAttachments::getInstance();
        $records = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        
        for ($i = 0; $i < 10; $i++) {
            $record = new Addressbook_Model_Contact(
                array('n_family' => Tinebase_Record_Abstract::generateUID())
            );
            $record->setId(Tinebase_Record_Abstract::generateUID());
            
            $recordAttachments->addRecordAttachment($record, $i . 'Test.txt', fopen(__FILE__, 'r'));
            
            $records->addRecord($record);
        }
        
        $recordAttachments->getMultipleAttachmentsOfRecords($records);
        
        foreach ($records as $records) {
            $this->assertEquals(1, $record->attachments->count(), 'Attachments missing');
        }
        
    }
}
