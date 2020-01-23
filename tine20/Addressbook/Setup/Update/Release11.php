<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * @return void
     */
    public function update_0()
    {
        $release10 = new Addressbook_Setup_Update_Release10($this->_backend);
        $release10->update_6();

        $this->setApplicationVersion('Addressbook', '11.1');
    }

    /**
     * update to 11.2
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_1()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.2');
    }

    /**
     * update to 11.3
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_2()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.3');
    }

    /**
     * update to 11.4
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_3()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.4');
    }

    /**
     * update to 11.5
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_4()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.5');
    }

    /**
     * update to 11.6
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_5()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.6');
    }

    /**
     * update to 11.7
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_6()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.7');
    }

    /**
     * update to 11.8
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_7()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.8');
    }

    /**
     * update to 11.9
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_8()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.9');
    }

    /**
     * update to 11.10
     *
     * Update export templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_9()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('Addressbook', '11.10');
    }

    /**
     * update to 11.11
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_10()
    {
        $this->updateKeyFieldIcon(Addressbook_Config::getInstance(), Addressbook_Config::CONTACT_SALUTATION);

        $this->setApplicationVersion('Addressbook', '11.11');
    }

    /**
     * update to 11.12
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_11()
    {
        $stateRepo = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_State',
            'tableName' => 'state',
        ));

        $states = $stateRepo->search(new Tinebase_Model_StateFilter(array(
            array('field' => 'state_id', 'operator' => 'equals', 'value' => 'Addressbook-Contact-GridPanel-Grid'),
        )));

        foreach ($states as $state) {
            $decodedState = Tinebase_State::decode($state->data);
            $spliceAt = 0;
            if ($decodedState['columns'][1]['id'] == 'type') {
                $spliceAt = 1;
                $decodedState['columns'][1]['width'] = 25;
            }

            array_splice($decodedState, $spliceAt, 0, ['id' => 'jpegphoto', 'width' => 25]);
            array_splice($decodedState, $spliceAt, 0, ['id' => 'attachments', 'width' => 25]);

            $state->data = Tinebase_State::encode($decodedState);
            $stateRepo->update($state);
        }

        $this->setApplicationVersion('Addressbook', '11.12');
    }

    /**
     * update to 11.13
     */
    public function update_12()
    {
        //$this->fixContactData(); -- is called in Setup_Controller::updateApplications
        Setup_SchemaTool::updateSchema([Addressbook_Model_Contact::class]);

        $this->setApplicationVersion('Addressbook', '11.13');
    }

    // is called in Setup_Controller::updateApplications
    public function fixContactData()
    {
        if (version_compare($this->getApplicationVersion('Addressbook'), '11.13') < 0) {
            $this->_db->query('update ' . SQL_TABLE_PREFIX . 'addressbook set creation_time = "1970-01-01 00:00:00" where CAST(creation_time AS CHAR(20)) = "0000-00-00 00:00:00"');
            $this->_db->query('update ' . SQL_TABLE_PREFIX . 'addressbook set last_modified_time = "1970-01-01 00:00:00" where CAST(last_modified_time AS CHAR(20)) = "0000-00-00 00:00:00"');
            $this->_db->query('update ' . SQL_TABLE_PREFIX . 'addressbook set is_deleted = 0 where  is_deleted IS NULL');
        }
    }

    /**
     * Adds short name for contacts
     *
     * @return void
     */
    public function update_13()
    {
        Setup_SchemaTool::updateSchema([Addressbook_Model_Contact::class]);
        $this->setApplicationVersion('Addressbook', '11.14');
    }

    /**
     * renormalize telephone numbers to apply country code
     *
     * @return void
     */
    public function update_14()
    {
        // fill normalized columns with data
        $db = Tinebase_Core::getDb();
        $select = $db->select();
        $columns = array('id', 'tel_assistent', 'tel_car', 'tel_cell', 'tel_cell_private', 'tel_fax', 'tel_fax_home', 'tel_home', 'tel_pager', 'tel_work', 'tel_other', 'tel_prefer');

        // get all telephone columns
        $select->from(SQL_TABLE_PREFIX . 'addressbook', $columns)->where('is_deleted=0');
        $result = $db->query($select);
        $data = array();
        array_shift($columns);

        $results = $result->fetchAll(Zend_Db::FETCH_ASSOC);

        foreach ($results as $row) {
            foreach ($columns as $col) {
                if (!empty($row[$col])) {
                    $data[$col . '_normalized'] = Addressbook_Model_Contact::normalizeTelephoneNum((string)$row[$col]);
                }
            }
            if (count($data) > 0) {
                $db->update(SQL_TABLE_PREFIX . 'addressbook', $data, $db->quoteInto('id = ?', $row['id']));
                $data = array();
            }
        }

        $this->setApplicationVersion('Addressbook', '11.15');
    }

    /**
     * @return Set shortnames
     */
    public function update_15()
    {
        if (Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_SHORT_NAME)) {
            $controller = Addressbook_Controller_Contact::getInstance();
            $userContacts = $controller->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    'Addressbook_Model_Contact',[['field' => 'type', 'operator' => 'equals', 'value' => 'user']]
                )
            );
            foreach ($userContacts as $contact) {
                try {
                    $controller->update($contact);
                } catch (Exception $e) {
                    Tinebase_Exception::log($e);
                }
            }
        }

        $this->setApplicationVersion('Addressbook', '11.16');
    }

    /**
     * update to 12.0
     *
     * @return void
     */
    public function update_16()
    {
        $this->setApplicationVersion('Addressbook', '12.0');
    }
}
