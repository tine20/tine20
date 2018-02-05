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
        unlink($tempfile);
    }

    public function testExportCalendarResource()
    {
        $resourceTest = new Calendar_Controller_ResourceTest();
        $resourceTest->setUp();
        $resource = $resourceTest->testCreateResource();
        $resource->relations = [
            new Tinebase_Model_Relation([
                'related_degree' => Tinebase_Model_Relation::DEGREE_CHILD,
                'related_model' => Addressbook_Model_Contact::class,
                'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id' => $this->_personas['sclever']->contact_id,
                'type' => 'STANDORT'
            ], true)
        ];
        Calendar_Controller_Resource::getInstance()->update($resource);

        $filter = new Calendar_Model_ResourceFilter();
        $export = new Calendar_Export_Resource_Doc($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(
                    new Tinebase_Model_ImportExportDefinitionFilter([
                    'model' => Calendar_Model_Resource::class,
                    'name' => 'cal_resource_doc'
                ]))->getFirstRecord()->getId()
            ]);

        $tempfile = Tinebase_TempFile::getTempPath() . '.docx';
        $export->generate();
        $export->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
        unlink($tempfile);
    }
}