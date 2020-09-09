<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */


class Tinebase_FileSystem_PreviewTest extends TestCase
{
    /**
     * @var Tinebase_FileSystem
     */
    protected $_fileSystem;

    /**
     * @var Tinebase_FileSystem_Previews
     */
    protected $_previews;

    /**
     * @var Tinebase_FileSystem_Preview_ServiceInterface
     */
    protected $_previewService;

    protected $_basePath;


    protected $_rmDir = array();

    protected function setUp()
    {
        if (empty(Tinebase_Core::getConfig()->filesdir)) {
            $this->markTestSkipped('filesystem base path not found');
        }

        parent::setUp();

        $this->_rmDir = array();

        $this->_oldFileSystemConfig = clone Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};
        Tinebase_Core::getConfig()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
        $this->_oldQuota = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};

        $this->_fileSystem = new Tinebase_FileSystem();
        $this->_basePath   = '/' . Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId()
            . '/folders/' . Tinebase_Model_Container::TYPE_SHARED;

        $this->_fileSystem->initializeApplication(Tinebase_Application::getInstance()->getApplicationByName('Tinebase'));


        $this->_previewService = new Tinebase_FileSystem_TestPreviewService();

        $this->_previews = Tinebase_FileSystem_Previews::getInstance();
        $this->_previews->setPreviewService($this->_previewService);
    }

    protected function tearDown()
    {
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM} = $this->_oldFileSystemConfig;
        Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA} = $this->_oldQuota;

        Tinebase_FileSystem::getInstance()->resetBackends();

        parent::tearDown();

        Tinebase_FileSystem::getInstance()->clearStatCache();

        if (!empty($this->_rmDir)) {
            try {
                foreach ($this->_rmDir as $rmDir) {
                    Tinebase_FileSystem::getInstance()->rmdir($rmDir, true);
                }
            } catch (Exception $e) {
            }
            Tinebase_FileSystem::getInstance()->clearStatCache();
        }

        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem(false);
    }

    public function assertCreatePreviewsFormNode($preview_count, $preview_status, $preview_error_count_min, $preview_error_count_max)
    {
        $path = $this->_basePath . '/PHPUNIT';
        $this->_fileSystem->mkdir($path);
        $this->_rmDir[] = $path;

        $path .= "/testFile.txt";
        $handle = $this->_fileSystem->fopen($path, 'w');

        fwrite($handle, 'phpunit');

        $this->_fileSystem->fclose($handle);

        $node = $this->_fileSystem->stat($path);

        //create preview could already have run
        $this->_previews->createPreviewsFromNode($node);

        $node = $this->_fileSystem->stat($path);

        $this->assertEquals($preview_count, $node->preview_count, "preview count:");
        $this->assertEquals($preview_status, $node->preview_status, "preview status:");
        $this->assertTrue(
            $node->preview_error_count <= $preview_error_count_max && $node->preview_error_count >= $preview_error_count_min,
            "preview error count ($node->preview_error_count) not in interval [$preview_error_count_min, $preview_error_count_max]"
        );

        return $node;
    }

    public function testCreatePreviewsFormNodeSuccess()
    {
        $node = $this->assertCreatePreviewsFormNode(3, 0, 0, 0);
        self::assertTrue(
            $this->_previews->hasPreviews($node)
        );
    }

    public function testCreatePreviewsFormNodeFailPreviewCreationFailed()
    {
        $this->_previewService->setReturnValueGetPreviewsForFile(false);
        //if createPreviews has run while file creation, it will also run a second time (because there are no previews for the file)
        $this->assertCreatePreviewsFormNode(0, 0, 1, 2);
    }

    public function testCreatePreviewsFormNodeFailUnusableFile()
    {
        $this->_previewService->setThrowExceptionGetPreviewsForFile(new Tinebase_FileSystem_Preview_BadRequestException("File not usable", 400));
        $this->assertCreatePreviewsFormNode(0, 400, 0, 0);
    }
}