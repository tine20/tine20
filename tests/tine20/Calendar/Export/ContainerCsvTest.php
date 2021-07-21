<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schhüle <p.schuele@metaways.de>
 */
class Calendar_Export_ContainerCsvTest extends Calendar_TestCase
{
    public function testExport()
    {
        $calendar =  $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

        Tinebase_Container::getInstance()->doSearchAclFilter(false);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_Container::class, [
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_SHARED],
            ['field' => 'model', 'operator' => 'equals', 'value' => Calendar_Model_Event::class],
            ['field' => 'application_id', 'operator' => 'equals', 'value' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()],
        ], [
            'ignoreAcl' => true,
        ]);
        $options = [
            'definitionId' => Tinebase_ImportExportDefinition::getInstance()->getByName('cal_shared_calendar_csv'),
            'ignoreAcl' => true,
        ];

        $export = new Calendar_Export_Container_Csv($filter, null, $options);
        $export->generate();
        Tinebase_Container::getInstance()->doSearchAclFilter(true);

        $fh = fopen('php://memory', 'r+');
        $export->write($fh);
        rewind($fh);
        $contents = stream_get_contents($fh);
        fclose($fh);
        self::assertStringContainsString($calendar->getId(), $contents);
        self::assertStringContainsString($calendar->name, $contents);
        self::assertStringContainsString('admin users', $contents);
        self::assertStringContainsString(';"' . Tinebase_Core::getUser()->accountLoginName . '"', $contents, 'admin users not found');
        self::assertStringContainsString(';"anyone,' . Tinebase_Core::getUser()->accountLoginName . '"', $contents, 'read grant users not found');
    }
}
