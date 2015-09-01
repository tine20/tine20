<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Crm_Import_Csv
 */
class Crm_Import_CsvTest extends ImportTestCase
{
    protected $_importerClassName = 'Crm_Import_Csv';
    protected $_exporterClassName = 'Crm_Export_Csv';
    protected $_modelName         = 'Crm_Model_Lead';

    protected $_tasksToDelete = array();

    /**
     * tear down tests
     */
    protected function tearDown()
    {
        parent::tearDown();

        // delete tasks
        Tasks_Controller_Task::getInstance()->delete($this->_tasksToDelete);

        Crm_Config::getInstance()->set(Crm_Config::LEAD_IMPORT_AUTOTASK, false);
    }
    /**
     * test import
     *
     * @param boolean $dryrun
     * @return array
     */
    public function testImport($dryrun = true)
    {
        $result = $this->_importHelper('leads.csv', 'crm_tine_import_csv', $dryrun);
        $this->assertEquals(2, $result['totalcount'], 'should import 2 records: ' . print_r($result, true));

        $firstLead = $result['results']->getFirstRecord();
        $this->assertContains('neuer lead', $firstLead->lead_name);
        $this->assertEquals(1, count($firstLead->tags));
        $this->assertEquals(5, count($firstLead->relations),
            'relations not imported for first lead ' . print_r($firstLead->toArray(), true));
        $this->assertEquals(6, count($result['results'][1]->relations),
            'relations not imported for second lead ' . print_r($result['results'][1]->toArray(), true));

        return $result;
    }

    /**
     * import helper
     *
     * @param        $importFilename
     * @param string $definitionName
     * @param bool   $dryrun
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    protected function _importHelper($importFilename, $definitionName = 'crm_tine_import_csv', $dryrun = true)
    {
        $this->_testNeedsTransaction();

        $this->_testContainer = $this->_getTestContainer('Crm');
        $this->_filename = dirname(__FILE__) . '/files/' . $importFilename;
        $this->_deleteImportFile = false;

        $options = array(
            'container_id'  => $this->_testContainer->getId(),
            'dryrun' => $dryrun,
        );

        $result = $this->_doImport($options, $definitionName);

        return $result;
    }

    /**
     * @see 0011234: automatically add task for responsible person on lead import
     */
    public function testAutoTaskImport()
    {
        Crm_Config::getInstance()->set(Crm_Config::LEAD_IMPORT_AUTOTASK, true);
        $personalContainerOfSClever = $this->_getPersonalContainer('Tasks', $this->_personas['sclever']);
        $this->_setPersonaGrantsForTestContainer($personalContainerOfSClever->getId(), 'sclever', true, false);

        $result = $this->testImport(/* dry run = */ false);
        foreach ($result['results'] as $lead) {
            foreach ($lead->relations as $relation) {
                if ($relation->type === 'TASK') {
                    $this->_tasksToDelete[] = $relation->related_id;
                }
            }
        }

        $translate = Tinebase_Translation::getTranslation('Crm');
        $tasksFilter = new Tasks_Model_TaskFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $personalContainerOfSClever->getId()),
            array('field' => 'summary', 'operator' => 'equals', 'value' => $translate->_('Edit new lead')),
        ));
        $tasks = Tasks_Controller_Task::getInstance()->search($tasksFilter);
        $this->_tasksToDelete = array_merge($this->_tasksToDelete, $tasks->getArrayOfIds());

        $this->assertEquals(1, count($tasks), 'could not find task in sclevers container: '
            . print_r($personalContainerOfSClever->toArray(), true));
        $task = $tasks->getFirstRecord();
        $this->assertEquals($this->_personas['sclever']['accountId'], $task->organizer);
        $this->assertEquals('IN-PROCESS', $task->status);
    }
}
