<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_User
 */
class Tinebase_FileSystem_RecordAttachmentsTest extends TestCase
{
    use GetProtectedMethodTrait;

    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        if (empty(Tinebase_Core::getConfig()->filesdir)) {
            self::markTestSkipped('filesystem base path not found');
        }
        
        parent::setUp();
        
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
        parent::tearDown();
        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem(false);
    }
    
    /**
     * test adding attachments to record
     * 
     * @return Addressbook_Model_Contact
     */
    public function testAddRecordAttachments()
    {
        $recordAttachments = Tinebase_FileSystem_RecordAttachments::getInstance();
        
        $record = new Addressbook_Model_Contact(array(
            'n_family' => Tinebase_Record_Abstract::generateUID(),
            'container_id' => Addressbook_Controller::getDefaultInternalAddressbook()
        ));
        $record = Addressbook_Controller_Contact::getInstance()->create($record);
        
        $recordAttachments->addRecordAttachment($record, 'Test.txt', fopen(__FILE__, 'r'));
        
        $attachments = $this->testGetRecordAttachments($record);
        self::assertEquals(1, count($attachments));

        $adbJson = new Addressbook_Frontend_Json();
        $contactJson = $adbJson->getContact($record->getId());
        static::assertTrue(isset($contactJson['attachments']) && isset($contactJson['attachments'][0]) &&
            isset($contactJson['attachments'][0]['path']));
        Tinebase_FileSystem::getInstance()->stat(Tinebase_FileSystem::getInstance()->
            getApplicationBasePath('Addressbook') . '/folders' . $contactJson['attachments'][0]['path']);

        return $record;
    }

    public function testRecordAttachmentNodeAcl()
    {
        $record = $this->testAddRecordAttachments();
        $sclever = $this->_personas['sclever'];
        Tinebase_Core::setUser($sclever);
        $nodeId = $record->attachments->getFirstRecord()->getId();
        $node = Tinebase_FileSystem::getInstance()->get($nodeId);
        $nodePath = Tinebase_FileSystem::getInstance()->getPathOfNode($node, true);
        $path = Tinebase_Model_Tree_Node_Path::createFromStatPath($nodePath);
        $result = Tinebase_FileSystem::getInstance()->checkPathACL($path);
        self::assertTrue($result);
    }

    public function testRecordAttachmentFilter()
    {
        $result = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter([
            ['field' => 'attachments', 'operator' => 'in', 'value' => [
                ['field' => 'size', 'operator' => 'less', 'value' => 100]
            ]]
        ]), null, true);
        $oldCount = count($result);

        $this->testAddRecordAttachments();

        $result = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter([
            ['field' => 'attachments', 'operator' => 'in', 'value' => [
                ['field' => 'size', 'operator' => 'greater', 'value' => 100]
            ]]
        ]), null, true);

        static::assertGreaterThan(0, count($result), 'no records with attachments size > 100 found');

        $result = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter([
            ['field' => 'attachments', 'operator' => 'wordstartswith', 'value' => 'Test.txt']
        ]), null, true);

        static::assertGreaterThan(0, count($result), 'no records with attachments query =>s Test.txt found');

        $result = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter([
            ['field' => 'attachments', 'operator' => 'in', 'value' => [
                ['field' => 'size', 'operator' => 'less', 'value' => 100]
            ]]
        ]), null, true);
        static::assertEquals($oldCount, count($result));
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
        
        return $recordAttachments->getRecordAttachments($record);
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
        
        foreach ($records as $record) {
            self::assertEquals(1, $record->attachments->count(), 'Attachments missing');
        }
    }

    /**
     * @see 0013032: add GRANT_DOWNLOAD
     *
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testDownloadRecordAttachment()
    {
        $contactWithAttachment = $this->testAddRecordAttachments();
        $http = new Tinebase_Frontend_Http();

        $attachment = $contactWithAttachment->attachments->getFirstRecord();
        $path = Tinebase_Model_Tree_Node_Path::STREAMWRAPPERPREFIX
            . Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($contactWithAttachment)
            . '/' . $attachment->name;

        ob_start();
        $reflectionMethod = $this->getProtectedMethod(Tinebase_Frontend_Http::class, '_downloadFileNode');
        $reflectionMethod->invokeArgs($http, [$attachment, $path, null, /* ignoreAcl */ true]);
        $output = ob_get_clean();

        self::assertContains('Tinebase_FileSystem_RecordAttachmentsTest', $output);
    }
}
