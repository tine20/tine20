<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */


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
        // @TODO have some demodata to export here
        $filter = new Calendar_Model_EventFilter(array(
//            array('field' => 'period', 'operator' => 'within', 'value' => array(
//                'from' => '',
//                'until' => ''
//            ))
        ));
        $doc = new Calendar_Export_Doc($filter);
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';
        $doc->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
    }
}