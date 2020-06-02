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
        $definition = new Tinebase_Model_ImportExportDefinition([
            'name' => 'test definition',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'model' => Tinebase_Model_ImportExportDefinition::class,
            'type' => 'export',
            'plugin' => 'unknown',
            'plugin_options_definition' => '<?xml version="1.0" encoding="UTF-8"?>
            <config>
                <someOption>
                    <label>myOptionLabel</label>
                    <length>40</length>
                    <type>string</type>
                    <disable>1</disable>
                    <hide>1</hide>
                    <default></default>
                </someOption>
            </config>'
        ]);
        $savedDefinition = Tinebase_ImportExportDefinition::getInstance()->create($definition);

        $converter = Tinebase_Convert_Factory::factory(Tinebase_Model_ImportExportDefinition::class);
        $jsonRecord = $converter->fromTine20Model($savedDefinition);
        self::assertTrue(isset($jsonRecord['plugin_options_definition_json']));
        self::assertEquals([
            'someOption' => [
                'label' => 'myOptionLabel',
                'length' => 40,
                'type' => 'string',
                'disable' => 1,
                'hide' => 1,
                'default' => '',
            ]
        ], $jsonRecord['plugin_options_definition_json']);
    }
}
