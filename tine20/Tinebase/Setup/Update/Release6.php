<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1
     * - changepw config option has moved
     */
    public function update_0()
    {
        $changepwSetting = Tinebase_User::getBackendConfiguration('changepw', TRUE);
        if (! $changepwSetting) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::PASSWORD_CHANGE, FALSE);
        }
        
        $this->setApplicationVersion('Tinebase', '6.1');
    }
    
    /**
     * update to 6.2
     *  - apply 5.11 if nessesary
     */
    public function update_1()
    {
        if ($this->getTableVersion('alarm') < 3) {
            $update = new Tinebase_Setup_Update_Release5($this->_backend);
            $update->update_10();
        }
        
        $this->setApplicationVersion('Tinebase', '6.2');
    }
    
    /**
     * update to 6.3
     *  - create container-field model (the containing model)
     *  - assign models to existing apps
     */
    public function update_2()
    {
        if(! $this->_backend->columnExists('model', 'container')) {
            // create definition col
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>model</name>
                    <type>text</type>
                    <length>64</length>
                </field>');
            
            $this->_backend->addCol('container', $declaration);
        }
        
        $modelsToSet = array(
                'Calendar'    => 'Calendar_Model_Event',
                'Addressbook' => 'Addressbook_Model_Contact',
                'Courses'     => 'Courses_Model_Course',
                'Crm'         => 'Crm_Model_Lead',
                'Tasks'       => 'Tasks_Model_Task',
                'Sales'       => 'Sales_Model_Contract',
                'Projects'    => 'Projects_Model_Project',
                'SimpleFAQ'   => 'SimpleFAQ_Model_FAQ'
                );
        
        foreach($modelsToSet as $app => $model) {
            if(Tinebase_Application::getInstance()->isInstalled($app)) {
                $application = Tinebase_Application::getInstance()->getApplicationByName($app);
                $filter = new Tinebase_Model_ContainerFilter(array(array(
                        'field' => 'application_id',
                        'operator' => 'equals',
                        'value' => $application->getId()
                        )), 'AND');
                
                $records = Tinebase_Container::getInstance()->search($filter);
                foreach($records as $record) {
                    $record->model = $model;
                    Tinebase_Container::getInstance()->update($record);
                }
            }
        }
        $this->setTableVersion('container', 6);
        $this->setApplicationVersion('Tinebase', '6.3');
    }
    
    /**
     * update to 6.4
     * - add credential cache cleanup task to scheduler
     */
    public function update_3()
    {
        Tinebase_Scheduler_Task::addSessionsCleanupTask(Tinebase_Core::getScheduler());
        
        $this->setApplicationVersion('Tinebase', '6.4');
    }
    
    /**
     * update to 6.5
     * - add primary key to customfield table
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>id</name>
                <primary>true</primary>
                <field>
                    <name>id</name>
                </field>
            </index>
        ');
        
        $this->_backend->addIndex('customfield', $declaration);
        $this->setTableVersion('customfield', 2);
        
        $this->setApplicationVersion('Tinebase', '6.5');
    }
}
