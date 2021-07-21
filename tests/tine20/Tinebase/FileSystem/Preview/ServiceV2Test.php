<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */

class Tinebase_FileSystem_Preview_ServiceV2Test extends TestCase
{
    /**
     * @var Tinebase_FileSystem_Preview_ServiceV2
     */
    protected $_previewService;

    /**
     * @var Tinebase_FileSystem_Preview_NetworkAdapter
     */
    protected $_networkAdapter;

    protected function setUp(): void
{
        $this->_networkAdapter = new Tinebase_FileSystem_Preview_TestNetworkAdapter();
        $this->_previewService = new Tinebase_FileSystem_Preview_ServiceV2($this->_networkAdapter);
    }

    protected function getPreviewsForFile($synchronous = false)
    {
        $config = array(
            'thumbnail' => array(
                'firstPage' => true,
                'filetype'  => 'jpg',
                'x'         => 142,
                'y'         => 200,
                'color'     => 'white'
            ),
            'previews' => array(
                'firstPage' => false,
                'filetype'  => 'jpg',
                'x'         => 1420,
                'y'         => 2000,
                'color'     => 'white'
            ),
        );

        if ($synchronous) {
            array_merge($config, array("synchronRequest" => true));
        }

        return $this->_previewService->getPreviewsForFile("Tinebase/files/fulltext/test.doc", $config);
    }


    public function testGetPreviewsForFileSuccesses()
    {
        for ($i = 0; $i < 2; $i++) {
            $this->_networkAdapter->getAdapter()->setResponse(
                "HTTP/1.1 200 OK\r\n" .
                "Content-Type: application/json\r\n" .
                "\r\n" .
                '{"thumbnail":["dGh1bWJuYWlsMA=="],"previews":["cHJldmlldzA=","cHJldmlldzE=","cHJldmlldzM="]}'
            );

            $previews = $this->getPreviewsForFile($i == 1);

            self::assertTrue($previews != false);
            self::assertEquals(["thumbnail" => ["thumbnail0"], "previews" => ["preview0", "preview1", "preview3"]], $previews);
        }
    }

    public function testGetPreviewsForFileFailOccupied()
    {
        for ($i = 0; $i < 2; $i++) {
            $this->_networkAdapter->getAdapter()->setResponse(
                "HTTP/1.1 423 OK\r\n" .
                "\r\n" .
                'Service occupied'
            );

            self::assertFalse($this->getPreviewsForFile($i == 1));
        }
    }

    public function testGetPreviewsForFileFailServerError()
    {
        for ($i = 0; $i < 2; $i++) {
            $this->_networkAdapter->getAdapter()->setResponse(
                "HTTP/1.1 500 OK\r\n" .
                "\r\n" .
                'Internal Server Error'
            );

            self::assertFalse($this->getPreviewsForFile($i == 1));
        }
    }

    public function testGetPreviewsForFileFailBadRequest()
    {
        for ($i = 0; $i < 2; $i++) {
            $this->_networkAdapter->getAdapter()->setResponse(
                "HTTP/1.1 415 OK\r\n" .
                "\r\n" .
                'Unsupported file type'
            );

            try {
                $this->getPreviewsForFile($i == 1);
                self::fail();
            } catch (Tinebase_FileSystem_Preview_BadRequestException $exception) {
                self::assertEquals("Preview creation failed. Status Code: 415", $exception->getMessage());
            } catch (Exception $exception) {
                self::fail();
            }
        }
    }

    public function testGetPreviewsForFileFailZendException()
    {
        for ($i = 0; $i < 2; $i++) {
            $this->_networkAdapter->getAdapter()->setNextRequestWillFail(true);

            self::assertFalse($this->getPreviewsForFile($i == 1));

            $this->_networkAdapter->getAdapter()->setNextRequestWillFail(false);
        }
    }


    public function testGetPreviewsForFilesSuccesses()
    {

        $this->_networkAdapter->getAdapter()->setResponse(
            "HTTP/1.1 200 OK\r\n".
            "Content-Type: application/json\r\n".
            "\r\n".
            '{"thumbnail":["dGh1bWJuYWlsMA=="],"previews":["cHJldmlldzA=","cHJldmlldzE=","cHJldmlldzM="]}'
        );

        $previews = $this->_previewService->getPreviewsForFiles(["Tinebase/files/fulltext/test.doc","Tinebase/files/fulltext/test.doc"], array(
            'synchronRequest' => true,
            'thumbnail' => array(
                'firstPage' => true,
                'filetype'  => 'jpg',
                'x'         => 142,
                'y'         => 200,
                'color'     => 'white',
                'merge'     => true
            ),
            'previews' => array(
                'firstPage' => false,
                'filetype'  => 'jpg',
                'x'         => 1420,
                'y'         => 2000,
                'color'     => 'white',
                'merge'     => true
            ),
        ));

        self::assertTrue($previews != false);
        self::assertEquals(["thumbnail" => ["thumbnail0"], "previews" => ["preview0", "preview1", "preview3"]], $previews);
    }
}
