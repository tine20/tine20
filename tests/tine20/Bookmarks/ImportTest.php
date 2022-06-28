<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Bookmarks
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Timo Scholz <t.scholz@metaways.de>
 */

/**
 * Test class for Bookmarks_ImportTest
 */
class Bookmarks_ImportTest extends ImportTestCase
{
    public function testCliImport($dryRun = false)
    {
        $countBeforeImport = Bookmarks_Controller_Bookmark::getInstance()->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Bookmarks_Model_Bookmark::class));
        $this->_deleteImportFile = false;
        $this->_filename = __DIR__ . '/../../../tine20/Bookmarks/Import/examples/bookmarks.html';
        $cli = new Bookmarks_Frontend_Cli();
        $opts = new Zend_Console_Getopt('abp:');
        $args = array(
            'definition=bookmarks_import_html',
            $this->_filename
        );
        if ($dryRun) {
            $args[] = 'dryrun=1';
        }
        $opts->setArguments($args);

        ob_start();
        $cli->import($opts);
        $out = ob_get_clean();

        $countAfterImport = Bookmarks_Controller_Bookmark::getInstance()->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Bookmarks_Model_Bookmark::class));

        $this->assertStringContainsString('Imported 12 records', $out);
        $expectedCount = $dryRun ? $countBeforeImport: $countBeforeImport + 12;
        $this->assertEquals($expectedCount, $countAfterImport);
    }

    public function testCliImportDryrun()
    {
        $this->testCliImport(true);
    }
}
