<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schhüle <p.schuele@metaways.de>
 */
class Calendar_Export_ResourceCsvTest extends Calendar_TestCase
{
    public function testExport()
    {
        $resource = Calendar_Controller_Resource::getInstance()->create($this->_getResource());

        $options = [
            'definitionId' => Tinebase_ImportExportDefinition::getInstance()->getByName('cal_resource_csv')
        ];
        $export = new Calendar_Export_Resource_Csv(null, null, $options);
        $export->generate();
        $fh = fopen('php://memory', 'r+');
        $export->write($fh);
        rewind($fh);
        $contents = stream_get_contents($fh);
        fclose($fh);
        self::assertStringContainsString($resource->getId(), $contents);
        self::assertStringContainsString($resource->name, $contents);
        self::assertStringContainsString('admin users', $contents);
        self::assertStringContainsString(';"' . Tinebase_Core::getUser()->accountLoginName . '"', $contents);
    }
}
