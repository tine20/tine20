<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */


class Tinebase_Export_DocumentPdfTraitTest extends TestCase
{
    public function write($target)
    {
        $previewService = null;

        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS} != true) {
            $previewService = new Tinebase_FileSystem_TestPreviewService();
        }

        $docPdf = new Tinebase_Export_DocPdf(null, null, [], $previewService);
        $result = $docPdf->write($target);

        $this->assertNotNull($result);

        $file = Tinebase_TempFile::getTempPath();
        file_put_contents($file, $result);
        $this->assertEquals('application/pdf', mime_content_type($file));
        unlink($file);
    }

    public function testWriteToFile()
    {
        $file = Tinebase_TempFile::getTempPath();
        $this->write($file);

        $this->assertTrue(is_file($file));
        $this->assertEquals('application/pdf', mime_content_type($file));
        unlink($file);
    }

    public function testWrite()
    {
        ob_start();
        $this->write(null);
        $result = ob_get_clean();

        $file = Tinebase_TempFile::getTempPath();
        file_put_contents($file, $result);
        $this->assertEquals('application/pdf', mime_content_type($file));
        unlink($file);
    }
}