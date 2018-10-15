<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */


/**
 * Addressbook Csv generation class tests
 *
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_CsvTest extends TestCase
{
    protected function _genericExportTest($_config)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $definition = Tinebase_ImportExportDefinition::getInstance()
            ->updateOrCreateFromFilename($_config['definition'], $app);

        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'adr_one_street'   => 'Montgomerie',
            'n_given'           => 'Paul',
            'n_family'          => 'test',
            'email'             => 'tmpPaul@test.de'
        )));
        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'adr_one_street'   => 'Montgomerie',
            'n_given'           => 'Adam',
            'n_family'          => 'test',
            'email'             => 'tmpAdam@test.de'
        )));
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'Montgomerie')
        ));
        if (isset($_config['exportClass'])) {
            $class = $_config['exportClass'];
        } else {
            $class = 'Tinebase_Export_CsvNew';
        }
        /** @var Tinebase_Export_CsvNew $csv */
        $csv = new $class($filter, Addressbook_Controller_Contact::getInstance(), array(
            'definitionId' => $definition->getId()
        ));
        $csv->generate();

        $fh = fopen('php://memory', 'r+');
        $csv->write($fh);

        return $fh;
    }

    public function testNewCsvExport()
    {
        $fh = $this->_genericExportTest([
            'definition' => __DIR__ . '/definitions/adb_csv_test.xml',
        ]);
        try {
            rewind($fh);

            $row = fgetcsv($fh, 0, "\t", '"');
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertEquals('Adam', $row[0]);
            static::assertEquals('tmpadam@test.de', $row[1]);

            $row = fgetcsv($fh, 0, "\t", '"');
            static::assertTrue(is_array($row), 'could not read csv: ');
            static::assertEquals('Paul', $row[0]);
            static::assertEquals('tmppaul@test.de', $row[1]);
        } finally {
            fclose($fh);
        }
    }
}