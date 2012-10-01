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
        
        try {
            $this->_backend->addIndex('customfield', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // looks like we have duplicate ids ... try a normal index
            $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>id</name>
                <field>
                    <name>id</name>
                </field>
            </index>
            ');
            try {
                $this->_backend->addIndex('customfield', $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                // no index could be added
            }
        }
        $this->setTableVersion('customfield', 2);
        $this->setApplicationVersion('Tinebase', '6.5');
    }

    /**
     * update to 6.6
     * 
     * @see 0007356: increase tag name size to 256 chars
     */
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>name</name>
                <type>text</type>
                <length>256</length>
                <default>NULL</default>
            </field>
        ');
        
        $this->_backend->alterCol('tags', $declaration);
        $this->setTableVersion('tags', 5);
        
        $this->setApplicationVersion('Tinebase', '6.6');
    }

    /**
     * update to 6.7
     * 
     * @see 0007396: add index for 'li' in access_log table
     */
    public function update_6()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>li</name>
                <field>
                    <name>li</name>
                </field>
            </index>
        ');
        try {
            $this->_backend->addIndex('access_log', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // no index could be added
        }
        $this->setTableVersion('access_log', 4);
        $this->setApplicationVersion('Tinebase', '6.7');
    }
    
    /**
     * update to 6.8
     * 
     * @see 0007232: Index customfield table
     */
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>customfield_id</name>
                <field>
                    <name>customfield_id</name>
                </field>
            </index>
        ');
        try {
            $this->_backend->addIndex('customfield', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // no index could be added
        }
        $this->setTableVersion('customfield', 3);
        $this->setApplicationVersion('Tinebase', '6.8');
    }
    
    /**
     * update to 6.9
     * 
     *  - set length for field (persistent)filter-model from 40 to 64
     */
    public function update_8()
    {
        $dec = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>model</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>');
        
        $this->_backend->alterCol('filter', $dec);
        
        $this->setTableVersion('filter', 4);
        
        $dec = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>model</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>');
        
        $this->_backend->alterCol('importexport_definition', $dec);
        
        $dec = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>plugin</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>');
        
        $this->_backend->alterCol('importexport_definition', $dec);
        
        $this->setTableVersion('importexport_definition', 6);
        
        $this->setApplicationVersion('Tinebase', '6.9');
    }
    
    /**
    * update to 7.0
    *
    * @return void
    */
    public function update_9()
    {
        $this->setApplicationVersion('Tinebase', '7.0');
    }
}
