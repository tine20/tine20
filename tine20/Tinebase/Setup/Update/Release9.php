<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     *
     * @see 0011178: allow to lock preferences for individual users
     */
    public function update_0()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_11();
        $this->setApplicationVersion('Tinebase', '9.1');
    }

    /**
     * update to 9.2
     *
     * adds index to relations
     */
    public function update_1()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_12();
        $this->setApplicationVersion('Tinebase', '9.2');
    }

    /**
     * update to 9.3
     *
     * adds ondelete cascade to some indices (tags + roles)
     */
    public function update_2()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_13();
        $this->setApplicationVersion('Tinebase', '9.3');
    }

    /**
     * update to 9.4
     *
     * move keyFieldConfig defaults to config files
     */
    public function update_3()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_14();
        $this->setApplicationVersion('Tinebase', '9.4');
    }

    /**
     * update to 9.5
     *
     * @see 0012300: add container owner column
     */
    public function update_4()
    {
        $this->_addOwnerIdColumn();
        $this->setTableVersion('container', 10);
        $this->setApplicationVersion('Tinebase', '9.5');
    }

    protected function _addOwnerIdColumn()
    {
        if ($this->_backend->columnExists('owner_id', 'container')) {
            return;
        }

        $declaration = new Setup_Backend_Schema_Field_Xml(
            '<field>
            <name>owner_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>false</notnull>
        </field>
        ');
        $this->_backend->addCol('container', $declaration);

        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>owner_id</name>
                <field>
                    <name>owner_id</name>
                </field>
            </index>
        ');

        $this->_backend->addIndex('container', $declaration);

        Tinebase_Container::getInstance()->setContainerOwners();
    }

    /**
     * update to 9.6
     *
     * change length of groups.description column from varchar(255) to text
     */
    public function update_5()
    {
        if ($this->getTableVersion('groups') < 6) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>description</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
            ');
            $this->_backend->alterCol('groups', $declaration);
            $this->setTableVersion('groups', '6');
        }

        $this->setApplicationVersion('Tinebase', '9.6');
    }

    /**
     * update to 9.7
     */
    public function update_6()
    {
        if (! $this->_backend->columnExists('order', 'container')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                        <name>order</name>
                        <type>integer</type>
                        <default>0</default>
                        <notnull>false</notnull>
                </field>');
            $this->_backend->addCol('container', $declaration);
            $this->setTableVersion('container', 11);
        }
        $this->setApplicationVersion('Tinebase', '9.7');
    }

    /**
     * update to 9.8
     *
     * - rename relation degree (fix direction)
     * - add type to constrain
     */
    public function update_7()
    {
        if (!$this->_backend->columnExists('related_degree', 'relations')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>related_degree</name>
                    <type>text</type>
                    <length>32</length>
                    <notnull>true</notnull>
                </field>
            ');
            $this->_backend->alterCol('relations', $declaration, 'own_degree');
        }

        // delete index unique-fields
        try {
            $this->_backend->dropIndex('relations', 'unique-fields');
        } catch (Exception $e) {
        }
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>unique-fields</name>
                <unique>true</unique>
                <field>
                    <name>own_model</name>
                </field>
                <field>
                    <name>own_backend</name>
                </field>
                <field>
                    <name>own_id</name>
                </field>
                <field>
                    <name>related_model</name>
                </field>
                <field>
                    <name>related_backend</name>
                </field>
                <field>
                    <name>related_id</name>
                </field>
                <field>
                    <name>type</name>
                </field>
            </index>'
        );

        $this->_backend->addIndex('relations', $declaration);

        $this->setTableVersion('relations', '9');
        $this->setApplicationVersion('Tinebase', '9.8');
    }

    /**
     * update to 9.9
     *
     * @see 0011620: add "path" filter for records
     */
    public function update_8()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>path</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>record_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>path</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>shadow_path</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>path</name>
                    <field>
                        <name>path</name>
                    </field>
                </index>
                <index>
                    <name>shadow_path</name>
                    <unique>true</unique>
                    <field>
                        <name>shadow_path</name>
                    </field>
                </index>
                <index>
                    <name>record_id</name>
                    <field>
                        <name>record_id</name>
                    </field>
                </index>
            </declaration>
        </table>');

        $this->createTable('path', $declaration);
        $this->setApplicationVersion('Tinebase', '9.9');
    }

    /**
     * update to 9.10
     *
     * adds failcount+lastfail to scheduled imports
     *
     * @see 0012082: deactivate failing scheduled imports
     */
    public function update_9()
    {
        if ($this->getTableVersion('import') < 2) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>failcount</name>
                    <type>integer</type>
                </field>');
            $this->_backend->addCol('import', $declaration);

            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>lastfail</name>
                    <type>text</type>
                </field>');
            $this->_backend->addCol('import', $declaration);
        }

        $this->setTableVersion('import', '2');
        $this->setApplicationVersion('Tinebase', '9.10');
    }

    /**
     * update to 9.11
     *
     * adds owner_id column if still missing
     */
    public function update_10()
    {
        $this->_addOwnerIdColumn();
        $this->setApplicationVersion('Tinebase', '9.11');
    }

    /**
     * update to 9.12
     *
     * @see xxx: add numberables
     */
    public function update_11()
    {
        if ($this->getTableVersion('numberable') === 0) {
            $declaration = new Setup_Backend_Schema_Table_Xml('<table>
                <name>numberable</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                        <autoincrement>true</autoincrement>
                    </field>
                    <field>
                        <name>bucket</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>number</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>bucket_number</name>
                        <unique>true</unique>
                        <field>
                            <name>bucket</name>
                        </field>
                        <field>
                            <name>number</name>
                        </field>
                    </index>
                </declaration>
            </table>');

            $this->createTable('numberable', $declaration);
        }
        $this->setApplicationVersion('Tinebase', '9.12');
    }

    /**
     * update to 9.13
     *
     * @see 0012162: create new MailFiler application
     */
    public function update_12()
    {
        if ($this->getTableVersion('tree_fileobjects') < 3) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>description</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
            ');
            $this->_backend->alterCol('tree_fileobjects', $declaration);
            $this->setTableVersion('tree_fileobjects', '3');
        }

        $this->setApplicationVersion('Tinebase', '9.13');
    }

    /**
     * update to 9.14
     *
     * fix pgsql index creation issue
     */
    public function update_13()
    {
        if (version_compare($this->getTableVersion('Tinebase'), '10.45') < 0) {
            return;
        }

        $db = Tinebase_Core::getDb();
        if (!$db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $this->setApplicationVersion('Tinebase', '9.14');
            return;
        }

        $failures = array();
        $successes = array();
        $setupBackend = Setup_Backend_Factory::factory();
        $setupController = Setup_Controller::getInstance();
        $setupUpdate = new Setup_Update_Abstract($setupBackend);

        /** @var Tinebase_Model_Application $application */
        foreach (Tinebase_Application::getInstance()->getApplications() as $application) {
            $xml = $setupController->getSetupXml($application->name);
            // should we check $xml->enabled? I don't think so, we asked Tinebase_Application for the applications...

            // get all MCV2 models for all apps, you never know...
            $controllerInstance = null;
            try {
                $controllerInstance = Tinebase_Core::getApplicationInstance($application->name);
            } catch(Tinebase_Exception_NotFound $tenf) {
                $failures[] = 'could not get application controller for app: ' . $application->name;
            }
            if (null !== $controllerInstance) {
                try {
                    $setupUpdate->updateSchema($application->name, $controllerInstance->getModels(true));
                } catch (Exception $e) {
                    $failures[] = 'could not update MCV2 schema for app: ' . $application->name;
                }
            }

            if (!empty($xml->tables)) {
                /** @var SimpleXMLElement $table */
                foreach ($xml->tables->table as $table) {
                    if (!empty($table->requirements)) {
                        foreach ($table->requirements->required as $requirement) {
                            // in older versions of the code this method is not there yet, but then also
                            // $table->requirements is empty always :)
                            /** @noinspection PhpUndefinedMethodInspection */
                            if (!$setupBackend->supports((string)$requirement)) {
                                continue 2;
                            }
                        }
                    }

                    // check for missing index
                    /** @var SimpleXMLElement $index */
                    foreach ($table->declaration->index as $index) {
                        // ignore primary, foreign and fulltext indices
                        if (!empty($index->primary) || !empty($index->foreign) || !empty($index->fulltext) ||
                            !empty($index->unique)) {
                            continue;
                        }
                        $stmt = $db->select()->from('pg_indexes')->where('"tablename" = \'' . SQL_TABLE_PREFIX .
                            (string)$table->name . '\' AND "indexname" = \'' . SQL_TABLE_PREFIX . (string)$table->name .
                            '_' . (string)$index->name . '\'')->query();
                        if ($stmt->rowCount() === 0) {
                            $declaration = new Setup_Backend_Schema_Index_Xml($index->asXML());
                            $setupBackend->addIndex((string)$table->name, $declaration);
                            $successes[] = (string)$table->name . ': ' . (string)$index->name;
                        }
                    }
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Created the following indices: ' . PHP_EOL . join(PHP_EOL, $successes));
        
        $this->setApplicationVersion('Tinebase', '9.14');
    }

    /**
     * update to 10.0
     *
     * @return void
     */
    public function update_14()
    {
        $this->setApplicationVersion('Tinebase', '10.0');
    }
}
