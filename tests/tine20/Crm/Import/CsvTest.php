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
        Crm_Config::getInstance()->set(Crm_Config::LEAD_IMPORT_NOTIFICATION, false);
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
     * @param string $duplicateResolveStrategy
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    protected function _importHelper($importFilename, $definitionName = 'crm_tine_import_csv', $dryrun = true, $duplicateResolveStrategy = null)
    {
        $this->_testNeedsTransaction();

        $this->_testContainer = $this->_getTestContainer('Crm', 'Crm_Model_Lead');
        $this->_filename = dirname(__FILE__) . '/files/' . $importFilename;
        $this->_deleteImportFile = false;

        $options = array(
            'container_id'  => $this->_testContainer->getId(),
            'dryrun' => $dryrun,
        );

        if ($duplicateResolveStrategy) {
            $options['duplicateResolveStrategy'] = $duplicateResolveStrategy;
        }

        $result = $this->_doImport($options, $definitionName);

        return $result;
    }

    /**
     * @see 0011234: automatically add task for responsible person on lead import
     */
    public function testAutoTaskImport()
    {
        Crm_Config::getInstance()->set(Crm_Config::LEAD_IMPORT_AUTOTASK, true);
        $defaultContainerOfSClever = Tinebase_Container::getInstance()->getDefaultContainer('Tasks_Model_Task', $this->_personas['sclever'], 'defaultTaskList');
        $this->_setPersonaGrantsForTestContainer($defaultContainerOfSClever, 'sclever', true, false);

        $result = $this->testImport(/* dry run = */ false);
        foreach ($result['results'] as $lead) {
            foreach ($lead->relations as $relation) {
                if ($relation->type === 'TASK') {
                    $this->_tasksToDelete[] = $relation->related_id;
                }
            }
        }

        $tasks = $this->_searchTestTasks($defaultContainerOfSClever->getId());
        $this->assertEquals(1, count($tasks), 'could not find task in sclevers container: '
            . print_r($defaultContainerOfSClever->toArray(), true));
        $task = $tasks->getFirstRecord();
        $this->assertEquals($this->_personas['sclever']['accountId'], $task->organizer);
        $this->assertEquals('IN-PROCESS', $task->status);
    }

    /**
     * search tasks
     *
     * @param      $containerId
     * @param null $summary
     * @return array|Tinebase_Record_RecordSet
     */
    protected function _searchTestTasks($containerId, $summary = null)
    {
        if (! $summary) {
            $translate = Tinebase_Translation::getTranslation('Crm');
            $summary = $translate->_('Edit new lead');
        }
        $tasksFilter = new Tasks_Model_TaskFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $containerId),
            array('field' => 'summary', 'operator' => 'contains', 'value' => $summary),
        ));
        $tasks = Tasks_Controller_Task::getInstance()->search($tasksFilter);
        $this->_tasksToDelete = array_merge($this->_tasksToDelete, $tasks->getArrayOfIds());
        return $tasks;
    }

    /**
     * @see 0011376: send mail on lead import to responsibles
     *
     * @group nogitlabci
     * gitlabci: expecting 2 or more mails (at least for unittest + sclever) / messages:Array ...
     */
    public function testEmailNotification()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
            $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }

        Crm_Config::getInstance()->set(Crm_Config::LEAD_IMPORT_NOTIFICATION, true);
        $this->testImport(/* dry run = */ false);
        // mark tasks for deletion
        $this->_searchTestTasks(Tinebase_Container::getInstance()->getDefaultContainer('Tasks_Model_Task')->getId(), 'task');

        // assert emails for responsibles
        $messages = self::getMessages();
        $this->assertGreaterThan(1, count($messages));

        $translate = Tinebase_Translation::getTranslation('Crm');
        $importNotifications = array();
        $subjectToMatch = sprintf($translate->_('%s new leads have been imported'), 1);
        foreach ($messages as $message) {
            if ($message->getSubject() == $subjectToMatch) {
                $importNotifications[] = $message;
            }
        }

        $this->assertGreaterThan(1, count($importNotifications),
            'expecting 2 or more mails (at least for unittest + sclever) / messages:'
            . print_r($messages, true));
        $firstMessage = $importNotifications[0];
        $this->assertContains('neuer lead 2', $firstMessage->getBodyText()->getContent(), 'lead name missing');
        $this->assertContains('PHPUnit', $firstMessage->getBodyText()->getContent(), 'container name missing');
    }
}
