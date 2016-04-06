<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

use \PhpOffice\PhpWord;

/**
 * Calendar Doc generation class tests
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Calendar_Export_DocTest extends Calendar_TestCase
{
    public function testExportSimpleDocSheet()
    {
        // skip tests for php7
        // ERROR: PHP Fatal error:  Cannot use PhpOffice\PhpWord\Shared\String as String because 'String' is a special
        //  class name in /usr/local/share/tine20.git/tine20/vendor/phpoffice/phpword/src/PhpWord/TemplateProcessor.php
        //  on line 23
        if (PHP_VERSION_ID >= 70000) {
            $this->markTestSkipped('FIXME 0011730: fix doc export for php7');
        }

        // make sure definition is imported
        $definitionFile = __DIR__ . '/../../../../tine20/Calendar/Export/definitions/cal_default_doc_sheet.xml';
        $calendarApp = Tinebase_Application::getInstance()->getApplicationByName('Calendar');
        Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename($definitionFile, $calendarApp, 'cal_default_doc_sheet');

//        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);

        // @TODO have some demodata to export here
        $filter = new Calendar_Model_EventFilter(array(
//            array('field' => 'period', 'operator' => 'within', 'value' => array(
//                'from' => '',
//                'until' => ''
//            ))
        ));
        $doc = new Calendar_Export_DocSheet($filter);
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';
        $doc->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
//        `open $tempfile`;
    }
}