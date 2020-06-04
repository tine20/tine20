<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_ImportExportDefinition
 */
class Tinebase_ImportExportDefinitionTest extends TestCase
{
    public function testPluginOptionsDefinitionConverter()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('cal_default_vcalendar_report');

        $converter = Tinebase_Convert_Factory::factory(Tinebase_Model_ImportExportDefinition::class);
        $jsonRecord = $converter->fromTine20Model($definition);
        self::assertTrue(isset($jsonRecord['plugin_options_definition']));
        self::assertEquals([
            'sources' => [
                'label' => 'Containers to export',
                'type' => 'containers',
                'config' => [
                    'recordClassName' => Calendar_Model_Event::class,
                ]
            ],
            'target' => [
                'label' => 'Export target',
                'type' => 'filelocation',
            ]
        ], $jsonRecord['plugin_options_definition']);
    }
}
