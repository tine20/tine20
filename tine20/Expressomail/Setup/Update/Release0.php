<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Expressomail updates for version 0.x
 *
 * @package     Expressomail
 * @subpackage  Setup
 */
class Expressomail_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update to 0.2
     * add Expressomail config parameter IMAPSEARCHMAXRESULTS
     *
     * @return void
     */
    public function update_1()
    {
        $settings = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        if (! array_key_exists(Expressomail_Config::IMAPSEARCHMAXRESULTS, $settings)) {
            try {
                $properties = Expressomail_Config::getProperties();
                $property = $properties[Expressomail_Config::IMAPSEARCHMAXRESULTS];
                $default_value = $property['default'];
                $config = array(Expressomail_Config::IMAPSEARCHMAXRESULTS => $default_value);
                Expressomail_Controller::getInstance()->saveConfigSettings($config);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }

        $this->setApplicationVersion('Expressomail', '0.2');
    }

    /**
     * update to 0.3
     *
     * change index name conflicting with Expressomail
     */
    public function update_2()
    {
        try {
            $this->_backend->dropForeignKey('expressomail_account', 'account::credentials_id--credentials::id');
            $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>emaccount::credentials_id--credentials::id</name>
                <field>
                    <name>credentials_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>credential_cache</table>
                    <field>id</field>
                </reference>
            </index>');

            $this->_backend->addForeignKey('expressomail_account', $declaration);

            $this->setApplicationVersion('Expressomail', '0.3');
        } catch (Exception $e) {
            // do nothing
        }
    }

    /**
     * update to 0.4
     * add Expressomail config parameter AUTOSAVEDRAFTSINTERVAL
     *
     * @return void
     */
    public function update_3()
    {
        $settings = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        if (! array_key_exists(Expressomail_Config::AUTOSAVEDRAFTSINTERVAL, $settings)) {
            try {
                $properties = Expressomail_Config::getProperties();
                $property = $properties[Expressomail_Config::AUTOSAVEDRAFTSINTERVAL];
                $default_value = $property['default'];
                $settings[Expressomail_Config::AUTOSAVEDRAFTSINTERVAL] = $default_value;
                Expressomail_Controller::getInstance()->saveConfigSettings($settings);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }

        $this->setApplicationVersion('Expressomail', '0.4');
    }

    /**
     * update to 0.5
     * add Expressomail config parameter AUTOSAVEDRAFTSINTERVAL
     *
     * @return void
     */
    public function update_4()
    {
        $settings = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        if (! array_key_exists(Expressomail_Config::REPORTPHISHINGEMAIL, $settings)) {
            try {
                $properties = Expressomail_Config::getProperties();
                $property = $properties[Expressomail_Config::REPORTPHISHINGEMAIL];
                $default_value = $property['default'];
                $settings[Expressomail_Config::REPORTPHISHINGEMAIL] = $default_value;
                Expressomail_Controller::getInstance()->saveConfigSettings($settings);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }

        $this->setApplicationVersion('Expressomail', '0.5');
    }

    /**
     * update to 0.6
     * add Expressomail domain config file parameter ENABLEMAILDIREXPORT
     * add Expressomail table 'expressomail_maildirexport_queue'
     * insert Expressomail applications table new record
     *
     * @return void
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception_Backend_Database|Tinebase_Exception
     */
    public function update_5()
    {
        //Add new setup entry at domain setup configuration file
        $settings = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        if (!array_key_exists(Expressomail_Config::ENABLEMAILDIREXPORT, $settings)) {
            try {
                $properties = Expressomail_Config::getProperties();
                $property = $properties[Expressomail_Config::ENABLEMAILDIREXPORT];
                $defaultValue = $property['default'];
                $settings[Expressomail_Config::ENABLEMAILDIREXPORT] = $defaultValue;
                Expressomail_Controller::getInstance()->saveConfigSettings($settings);
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 successfully added new entry at domain configuration file: "' . $settings[Expressomail_Config::ENABLEMAILDIREXPORT] . '"');
            } catch (Tinebase_Exception_NotFound $tenf) {
                Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 fails to add new domain entry: "' . $settings[Expressomail_Config::ENABLEMAILDIREXPORT] . '"');
            }
        } else{
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 attempted to created a new domain config file entry, but it already exists! "' . Expressomail_Config::ENABLEMAILDIREXPORT .'"');
        }

        //Add table used by queue script for export mail dir data
        $newTable = 'expressomail_backup_scheduler';
        if(!$this->_backend->tableExists($newTable)) {
            try{
                $table = Setup_Backend_Schema_Table_Factory::factory('String', '
                    <table>
                        <name>'.$newTable.'</name>
                        <version>1</version>
                        <declaration>
                            <field>
                                <name>id</name>
                                <type>text</type>
                                <length>40</length>
                                <notnull>true</notnull>
                            </field>
                            <field>
                                <name>account_id</name>
                                <type>text</type>
                                <length>40</length>
                                <notnull>true</notnull>
                            </field>
                            <field>
                                <name>folder</name>
                                <type>text</type>
                                <length>250</length>
                                <notnull>true</notnull>
                            </field>
                            <field>
                                <name>scheduler_time</name>
                                <type>datetime</type>
                                <notnull>true</notnull>
                            </field>
                            <field>
                                <name>start_time</name>
                                <type>datetime</type>
                            </field>
                            <field>
                                <name>end_time</name>
                                <type>datetime</type>
                            </field>
                            <field>
                                <name>status</name>
                                <type>text</type>
                                <length>40</length>
                                <notnull>true</notnull>
                                <default>PENDING</default>
                            </field>
                            <field>
                                <name>is_deleted</name>
                                <type>boolean</type>
                                <default>false</default>
                            </field>
                            <field>
                                <name>deleted_time</name>
                                <type>datetime</type>
                            </field>
                            <field>
                                <name>deleted_by</name>
                                <type>text</type>
                                <length>40</length>
                            </field>
                            <field>
                                <name>priority</name>
                                <type>integer</type>
                                <notnull>true</notnull>
                                <default>5</default>
                            </field>
                            <field>
                                <name>expunged_time</name>
                                <type>datetime</type>
                            </field>
                            <index>
                                <name>id</name>
                                <field>
                                    <name>id</name>
                                </field>
                            </index>
                            <index>
                                <name>account_id</name>
                                <field>
                                    <name>account_id</name>
                                </field>
                            </index>
                            <index>
                                <name>folder</name>
                                <field>
                                    <name>folder</name>
                                </field>
                            </index>
                            <index>
                                <name>status</name>
                                <field>
                                    <name>status</name>
                                </field>
                            </index>
                            <index>
                                <name>scheduler_time</name>
                                <field>
                                    <name>scheduler_time</name>
                                </field>
                            </index>
                            <index>
                                <name>is_deleted</name>
                                <field>
                                    <name>is_deleted</name>
                                </field>
                            </index>
                            <index>
                                <name>priority</name>
                                <field>
                                    <name>priority</name>
                                </field>
                            </index>
                            <index>
                                <name>id</name>
                                <primary>true</primary>
                                <field>
                                    <name>id</name>
                                </field>
                            </index>
                            <index>
                                <name>account_id--folder--status--is_deleted</name>
                                <unique>true</unique>
                                <field>
                                    <name>account_id</name>
                                </field>
                                <field>
                                    <name>folder</name>
                                </field>
                                <field>
                                    <name>status</name>
                                </field>
                                <field>
                                    <name>is_deleted</name>
                                </field>
                            </index>
                            <index>
                                <name>'.$newTable.'::account_id--accounts::id</name>
                                <field>
                                    <name>account_id</name>
                                </field>
                                <foreign>true</foreign>
                                <reference>
                                    <table>accounts</table>
                                    <field>id</field>
                                    <ondelete>CASCADE</ondelete>
                                </reference>
                            </index>
                        </declaration>
                    </table>
                ');
                $this->_backend->createTable($table);
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade 0.6 successfully created a new table at data base schema: "' . $newTable . '"');

                //Insert new Expressomail application table record
                try{
                    //Fetch application id to perform insert operation
                    $selectId = $this->_db->select()
                        ->from(SQL_TABLE_PREFIX . 'applications', 'id')
                            ->where($this->_db->quoteIdentifier('name') . ' = ?', 'Expressomail');

                    $resultId = $this->_db->fetchAll($selectId);
                    $appId = $resultId[0]["id"];

                    //Check basic data consistence at table application
                    if(count($resultId) != 1){
                        Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 inconsistent data at applications table for appName "ExpressoMail"');
                        throw(new Tinebase_Exception_Backend_Database("Application 'Expressomail' inconsistent data at applications table!"));
                    }

                    //Try to find out if some corrupted event have already iserted this data before
                    $selectAppTable = $this->_db->select()
                        ->from(SQL_TABLE_PREFIX . 'application_tables', 'name')
                            ->where($this->_db->quoteIdentifier('name') . ' = ?', $newTable);

                    $resultAppTable = $this->_db->fetchAll($selectAppTable);
                    if(count($resultAppTable) != 0){
                        Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 inconsistent data at applications table for appName "ExpressoMail": the new table "'.$newTable.'" already is there!');
                        throw(new Tinebase_Exception_Backend_Database("Application 'Expressomail' inconsistent data at applications table: new table '$newTable' relationship already exists at 'application_tables'!"));
                    }

                    //Follows to add new record
                    $appRecord = new SimpleXMLElement("
                                <record>
                                    <table>
                                        <name>application_tables</name>
                                    </table>
                                    <field>
                                        <name>application_id</name>
                                        <value>$appId</value>
                                    </field>
                                    <field>
                                        <name>name</name>
                                        <value>$newTable</value>
                                    </field>
                                    <field>
                                        <name>version</name>
                                        <value>1</value>
                                    </field>
                                </record>
                    ");

                     //Performs insert new record
                     $this->_backend->execInsertStatement($appRecord);
                     Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 successfully inserted a new record at "application_tables" table: "' . $newTable . '"');

                     try{
                        //Fetch update action
                        $this->setApplicationVersion('Expressomail', '0.6');
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 successfully finished.');
                     } catch (Tinebase_Exception $updateException){
                        Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 fails to be up to dated: ' . $updateException->getMessage());
                        throw(new Tinebase_Exception_Backend_Database("Application 'Expressomail' fails to update to 0.6 at 'setApplicationVersion':" . $updateException->getMessage()));
                     }
                } catch (Tinebase_Exception_Backend_Database $insertAppException) {
                    Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 fails to insert new applications table: ' . $insertAppException->getMessage());
                    throw(new Tinebase_Exception_Backend_Database("Application 'Expressomail' fails to update to 0.6 at insert applicatons table new record:" . $insertAppException->getMessage()));
                }
            } catch (Tinebase_Exception_Backend_Database $createException){
                Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 fails to create new table: ' . $createException->getMessage());
                throw(new Tinebase_Exception_Backend_Database("Application 'Expressomail' fails to update to 0.6 at create new table:" . $createException->getMessage()));
            }
        } else{
            Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . ' ExpressoMail upgrade to 0.6 fails to process new table creation: table "' .$newTable . '" already exists at application schema database!');
            throw(new Tinebase_Exception_Backend_Database("Application 'Expressomail' fails to update to 0.6: base table '$newTable' already exists at database schema!"));
        }
    }
}