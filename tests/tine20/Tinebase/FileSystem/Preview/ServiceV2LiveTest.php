<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */

class Tinebase_FileSystem_Preview_ServiceV2LiveTest extends TestCase
{
    /**
     * @var Tinebase_FileSystem_Preview_ServiceV2
     */
    protected $_previewService;

    protected function setUp()
    {
        $this->_previewService = Tinebase_Core::getPreviewService();
    }

    public function testMergePdfFiles()
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS} != true
            || Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_VERSION}  < 2
        ) {
            $this->markTestSkipped();
            return;
        }

        $filePaths = ["Tinebase/files/multipage-text.pdf","Tinebase/files/spreadsheet.pdf"];
        $result = $this->_previewService->mergePdfFiles($filePaths);

        $merged = Tinebase_TempFile::getTempPath();

        $tmp = base64_encode($result);

        file_put_contents($merged, $result);

        $hash = exec("gs -q -dBATCH -dNOPAUSE -sDEVICE=bit -sOutputFile=- " . $merged . " | md5sum");
        $this->assertEquals("618088b99200db65eb0d73d718c224fc  -", $hash);
    }
}
