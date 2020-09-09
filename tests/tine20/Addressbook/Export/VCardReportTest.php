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
 * Addressbook_Export_VCardReport
 */
class Addressbook_Export_VCardReportTest extends Calendar_TestCase
{
    protected $_testContainer = null;

    public function testExportContainerToFilemanager()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Addressbook',
            Addressbook_Model_Contact::class, [
            'definition' => 'adb_tine_import_csv',
            'file' => 'Contact.csv',
            'duplicateResolveStrategy' => 'keep', // we want the duplicates!
        ], $this->_getTestAddressbook()
        );

        // export container to filemanager path
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('cal_default_vcard_report');
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/unittestexport');
        $fileLocation = new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE      => Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE,
            Tinebase_Model_Tree_FileLocation::FLD_FM_PATH   => '/shared/unittestexport/',
        ]);
        $exporter = Tinebase_Export::factory(null, [
            'definitionId' => $definition->getId(),
            'sources' => [
                $this->_getTestAddressbook()->toArray()
            ],
            'target' => $fileLocation->toArray(),
        ], null);
        $exporter->generate();

        // check if file exists in path and has the right contents
        $exportFilenamePath = 'Filemanager/folders/shared/unittestexport/'
            . str_replace([' ', DIRECTORY_SEPARATOR], '', $this->_getTestAddressbook()->name . '.vcf');
        $vcf = file_get_contents('tine20:///' . $exportFilenamePath);
        self::assertContains('Platz der Deutschen Einheit 4', $vcf);
        self::assertContains('BEGIN:VCARD', $vcf);
        self::assertEquals(7, substr_count($vcf, 'BEGIN:VCARD'),
            'expected 7 contacts');
    }

    protected function _getTestAddressbook()
    {
        if ($this->_testContainer === null) {
            $this->_testContainer = $this->_getTestContainer('Addressbook', Addressbook_Model_Contact::class);
        }
        return $this->_testContainer;
    }
}
