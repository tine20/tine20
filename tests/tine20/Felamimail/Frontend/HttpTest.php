<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Felamimail_Frontend_Http
 * 
 * @package     Felamimail
 */
class Felamimail_Frontend_HttpTest extends Felamimail_TestCase
{
    public function testDownloadAttachments()
    {
        // write a message with attachment
        $message = $this->_sendMessage(
            'INBOX',
            [],
            '',
            'test download attachment',
            null,
            true
        );

        /* @var $uit Felamimail_Frontend_Http */
        $uit = $this->_getUit();
        ob_start();
        $uit->downloadAttachments($message['id']);
        $out = ob_get_clean();
        $zipfilename = Tinebase_TempFile::getTempPath();
        file_put_contents($zipfilename, $out);

        // create zip file, unzip, check content
        $zip = new ZipArchive();
        $opened = $zip->open($zipfilename);
        self::assertTrue($opened);
        $zip->extractTo(Tinebase_Core::getTempDir());
        $extractedFile = Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . 'test.txt';
        self::assertTrue(file_exists($extractedFile), 'did not find extracted '
            . $extractedFile . ' file in dir ');
        $content = file_get_contents($extractedFile);
        self::assertEquals('some content', $content);
        $zip->close();
    }
}
