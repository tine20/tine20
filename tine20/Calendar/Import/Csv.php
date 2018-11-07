<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Calendar
 *
 * @package     Calendar
 * @subpackage  Import
 *
 */
class Calendar_Import_Csv extends Tinebase_Import_Csv_Generic
{

    protected $_members;

    protected $_resources;

    protected $_groups;
    
    protected $_secretaryGrants = array(Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_ADD);


    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
    );

    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $this->_test = $this->_options['container_id'];
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        $result = $this->_setDate($result);
        $this->_members = $result['members'];
        $this->_resources = $result['resources'];
        $this->_groups = $result['groups'];

        return $result;
    }

    /**
     *  set the current date plus 'dtday' and/or 'dthours' form import.
     *
     * @param array $result
     * @return array
     *
     */
    protected function _setDate($result)
    {
        $date = new Tinebase_DateTime();

        $time = explode(':', $result['time']);
        $date->addDay($result['date']);
        $date->setTime(
            (integer)$time['0'],
            isset($time['1']) ? (integer)$time['1'] : 0,
            isset($time['2']) ? (integer)$time['2'] : 0
        );

        $result['dtstart'] = $date->toString();
        $duration = isset($result['duration']) && !empty($result['duration']) ? (integer)$result['duration'] : 1;
        $date->addHour($duration);
        $result['dtend'] = $date->toString();

        return $result;
    }

    /**
     * do after Import the relationship of members.
     *
     * @param $importedRecord
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _inspectAfterImport($importedRecord)
    {
        $this->_createResourceAttender($importedRecord);
        $this->_createMemberAttender($importedRecord);
        $this->_createGroupAttender($importedRecord);
    }


    protected function _createResourceAttender($importedRecord)
    {
        if (empty($this->_resources)) {
            return;
        }

        $resource_Controller = Calendar_Controller_Resource::getInstance();

        $cal_Backend_Sql = new Calendar_Backend_Sql();

        $name_check = [];
        foreach (explode(';', $this->_resources) as $resource) {


            //$resource_data_all = $resource_Controller->getAll();
            foreach ($resource_Controller->getAll() as $resource_data) {

                if ($resource == $resource_data['name']) {
                    $resource_id = $resource_data['id'];
                    $display_id = $resource_data['container_id'];
                }
                $name_check[] = $resource_data['name'];
            }
            if (!in_array($resource, $name_check)) {
                $resource_Model = new Calendar_Model_Resource(array(
                    'name' => $resource,
                    'description' => 'Import Csv resource',
                    'email' => 'csv@resource.com',
                    'is_location' => TRUE,
                    'grants' => [
                        array_merge([
                            'account_id' => Tinebase_Core::getUser()->getId(),
                            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                        ], [
                            Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
                            Calendar_Model_ResourceGrants::EVENTS_READ => true,
                            Calendar_Model_ResourceGrants::EVENTS_SYNC => true,
                            Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true,
                            Calendar_Model_ResourceGrants::EVENTS_EDIT => true,
                        ]),
                    ]
                ));
                $cal_Controller = Calendar_Controller_Resource::getInstance()->create($resource_Model);
                $resource_id = $cal_Controller->getId();
                $display_id = $cal_Controller->container_id;
            }

            $attender = new Calendar_Model_Attender();

            $attender->user_id = $resource_id;
            $attender->displaycontainer_id = $display_id;
            $attender->user_type = Calendar_Model_Attender::USERTYPE_RESOURCE;
            $attender->cal_event_id = $importedRecord['id'];
            $attender->status_authkey = Tinebase_Record_Abstract::generateUID();


            Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($attender, 'create');

            $cal_Backend_Sql->createAttendee($attender);

        }
    }

    protected $_calendars = array();

    protected function _createMemberAttender($importedRecord)
    {
        if (empty($this->_members)) {
            return;
        }

        $members = explode(';', $this->_members);
        $tinebase_User = Tinebase_User::getInstance();
        $adb_Controller_Contact = Addressbook_Controller_Contact::getInstance();
        foreach ($members as $member) {
            try {
                $user_account_data = $tinebase_User->getUserByLoginName($member);
                $user_id = $user_account_data['contact_id'];
            } catch (Exception $e) {
                $user_data_all = $adb_Controller_Contact->getAll();
                foreach ($user_data_all as $user_data) {
                    if ($user_data['n_fileas'] != "" && $user_data['n_fileas'] == $member) {
                        $user_id = $user_data['id'];
                    }
                    $member_check[] = $user_data['n_fileas'];
                }

                if (!in_array($member, $member_check)) {
                    if (strpbrk($member, ",") === FALSE) {
                        $user_Model = new Tinebase_Model_FullUser(array(
                            'accountDisplayName' => $member,
                            'accountLastName' => $member,
                            'accountFullName' => $member,
                            'accountLoginName' => $member,
                            'accountPrimaryGroup' => 'Users'
                        ));
                        $user_id = Admin_Controller_User::getInstance()->create($user_Model, 1456, 1456)->getId();

                        $tine_Container_Data = Tinebase_Container::getInstance()->getAll();
                        foreach ($tine_Container_Data as $container) {
                            if ($container['owner_id'] == $user_id && $container['model'] == 'Calendar_Model_Event') {
                                $container_id = $container['id'];
                            }
                        }
                        Tinebase_Container::getInstance()->addGrants($container_id, 'group', Tinebase_Group::getInstance()->getGroupByName('users')->getId(), $this->_secretaryGrants, true);

                    } else {
                        $member_Model = new Addressbook_Model_Contact();
                        $member_name = explode(',', $member);
                        $member_Model->n_fileas = $member;
                        $member_Model->n_family = $member_name['0'];
                        $member_Model->n_given = $member_name['1'];
                        $user_id = Addressbook_Controller_Contact::getInstance()->create($member_Model)->getId();
                    }
                }
            }

            $attender = new Calendar_Model_Attender();

            $attender->user_id = $user_id;
            $attender->user_type = Calendar_Model_Attender::USERTYPE_USER;
            $attender->cal_event_id = $importedRecord['id'];
            $attender->status_authkey = Tinebase_Record_Abstract::generateUID();

            Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($attender, 'create');

            $cal_Backend_Sql = new Calendar_Backend_Sql();
            $cal_Backend_Sql->createAttendee($attender);
        }

    }

    protected function _createGroupAttender($importedRecord)
    {
        if (empty($this->_groups)) {
            return;
        }
        $groups = explode(';', $this->_groups);
        $tinebase_Group = Tinebase_Group::getInstance();
        foreach ($groups as $group) {
            try {
                $group_Data = $tinebase_Group->getGroupByName($group);
                $group_id = $group_Data['id'];
            } catch (Exception $e) {
                $group_Model = new Tinebase_Model_Group();
                $group_Model->name = $group;
                $group_Model->description = "import DemoData Csv";
                $group_id = Admin_Controller_Group::getInstance()->create($group_Model)->getId();
            }

            $attender = new Calendar_Model_Attender();

            $attender->user_id = $group_id;
            $attender->user_type = Calendar_Model_Attender::USERTYPE_GROUP;
            $attender->cal_event_id = $importedRecord['id'];
            $attender->status_authkey = Tinebase_Record_Abstract::generateUID();

            Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($attender, 'create');

            $cal_Backend_Sql = new Calendar_Backend_Sql();
            $cal_Backend_Sql->createAttendee($attender);
        }

    }
}