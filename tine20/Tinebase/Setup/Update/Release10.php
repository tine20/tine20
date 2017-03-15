<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * @see 0012162: create new MailFiler application
     */
    public function update_0()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_9();
        $this->setApplicationVersion('Tinebase', '10.1');
    }

    /**
     * update to 10.2
     *
     * @see 0012300: add container owner column
     */
    public function update_1()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        try {
            $release9->update_4();
        } catch (Zend_Db_Exception $zde) {
            Tinebase_Exception::log($zde);
        }
        $this->setApplicationVersion('Tinebase', '10.2');
    }

    /**
     * update to 10.3
     *
     * change length of groups.description column from varchar(255) to text
     */
    public function update_2()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_5();
        $this->setApplicationVersion('Tinebase', '10.3');
    }

    /**
     * update to 10.4
     *
     * dd numberables
     */
    public function update_3()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_10();
        $this->setApplicationVersion('Tinebase', '10.4');
    }

    /**
     * needs to be done again to make sure we have the column!
     *
     * @see 0012300: add container owner column
     */
    public function update_4()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        try {
            $release9->update_4();
        } catch (Zend_Db_Exception $zde) {
            Tinebase_Exception::log($zde);
        }
        $this->setApplicationVersion('Tinebase', '10.5');
    }

    /**
     * update to 10.6
     *
     * add account sync scheduler job
     */
    public function update_5()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Tinebase_Scheduler_Task::addAccountSyncTask($scheduler);

        $this->setApplicationVersion('Tinebase', '10.6');
    }


    /**
     * update to 10.7
     *
     * update timemachine_modlog table
     */
    public function update_6()
    {
        if (!$this->_backend->columnExists('instance_id', 'timemachine_modlog')) {

            $this->_backend->truncateTable('timemachine_modlog');

            $query = $this->_backend->addAddCol(null, 'timemachine_modlog',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>instance_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>'), 'id'
            );
            $query = $this->_backend->addAddCol($query, 'timemachine_modlog',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>instance_seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <autoincrement>true</autoincrement>
                </field>'), 'instance_id'
            );
            $query = $this->_backend->addAddCol($query, 'timemachine_modlog',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>change_type</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>'), 'instance_seq'
            );
            $query = $this->_backend->addAddIndex($query, 'timemachine_modlog', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>instance_id</name>
                    <field>
                        <name>instance_id</name>
                    </field>
                </index>'));
            $query = $this->_backend->addAddIndex($query, 'timemachine_modlog', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>instance_seq</name>
                    <unique>true</unique>
                    <field>
                        <name>instance_seq</name>
                    </field>
                </index>'));
            $this->_backend->execQueryVoid($query);

            $this->setTableVersion('timemachine_modlog', '4');
        }

        $this->setApplicationVersion('Tinebase', '10.7');
    }
}
