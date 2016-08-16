<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Addressbook Xls generation class tests
 *
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_XlsTest extends TestCase
{
    public function testExportXls()
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_given', 'operator' => 'equals', 'value' => 'Robert')
        ));
        $export = new Addressbook_Export_Xls($filter);
        $xls = $export->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.xlsx';

        // TODO add a save() fn to Tinebase_Export_Spreadsheet_Xls
        $xlswriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $xlswriter->setPreCalculateFormulas(FALSE);
        $xlswriter->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
    }
}
