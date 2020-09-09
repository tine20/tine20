<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Addressbook_Export_VCard
 */
class Addressbook_Export_VCardTest extends TestCase
{
    protected $_testContainer = null;

    public function testExportPersonalContainer()
    {
        $this->_testNeedsTransaction();

        $this->_import();
        $result = $this->_export('stdout=1');

        self::assertContains('Platz der Deutschen Einheit 4', $result);
        self::assertContains('BEGIN:VCARD', $result);
        self::assertEquals(7, substr_count($result, 'BEGIN:VCARD'),
            'expected 7 contacts');
    }

    protected function _import()
    {
        $this->_importDemoData(
            'Addressbook',
            Addressbook_Model_Contact::class, [
                'definition' => 'adb_tine_import_csv',
                'file' => 'Contact.csv',
                'duplicateResolveStrategy' => 'keep', // we want the duplicates!
            ], $this->_getTestAddressbook()
        );
    }

    protected function _getTestAddressbook()
    {
        if ($this->_testContainer === null) {
            $this->_testContainer = $this->_getTestContainer('Addressbook', Addressbook_Model_Contact::class);
        }
        return $this->_testContainer;
    }

    protected function _export($params = '', $addContainerid = true)
    {
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Addressbook.exportVCard';
        $args = $addContainerid ? 'container_id=' .
            $this->_getTestAddressbook()->getId() : '';
        if (! empty($params)) {
            $args .= ' ' . $params;
        }
        $cmd = TestServer::assembleCliCommand($cmd, TRUE,  $args);
        exec($cmd, $output);
        return implode(',', $output);
    }

    public function testExportIntoFile()
    {
        $this->_testNeedsTransaction();

        $this->_import();
        $filename = '/tmp/export.vcf';
        $this->_export('filename=' . $filename);
        self::assertTrue(file_exists($filename), 'export file does not exist');
        $result = file_get_contents($filename);
        unlink($filename);
        self::assertContains('Platz der Deutschen Einheit 4', $result);
        self::assertContains('BEGIN:VCARD', $result);
        self::assertContains('END:VCARD', $result);
    }

    public function testExportAllAddressbooks()
    {
        $this->_testNeedsTransaction();

        $this->_import();

        $path = Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . 'tine20_export_' . Tinebase_Record_Abstract::generateUID(8);
        mkdir($path);
        $output = $this->_export('path=' . $path . ' type=personal', false);

        self::assertContains('Exported container ' . $this->_getTestAddressbook()->getId() . ' into file', $output);

        // loop files in export dir
        $exportFilesFound = 0;
        $fh = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fh as $splFileInfo) {
            /** @var SplFileInfo $splFileInfo */
            $filename = $splFileInfo->getFilename();
            if ($filename === '.' || $filename === '..') {
                continue;
            }
            self::assertContains(Tinebase_Core::getUser()->accountLoginName, $filename);
            $result = file_get_contents($splFileInfo->getPathname());
            self::assertContains('END:VCARD', $result);
            $exportFilesFound++;
            unlink($splFileInfo->getPathname());
        }
        self::assertGreaterThan(0, $exportFilesFound);

        rmdir($path);
    }
}
