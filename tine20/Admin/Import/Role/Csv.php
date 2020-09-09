<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Admin
 *
 * @package     Admin
 * @subpackage  Import
 *
 */
class Admin_Import_Role_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
    );
    /**
     * @var string
     */
    protected $_members;

    /**
     * @var string
     */
    protected $_groups;

    protected $_rights;

    protected $_rightsFile;


    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        try {
            $role = Tinebase_Role::getInstance()->getRoleByName($_record['name']);
            $this->_importResult['duplicatecount']++;
            return $role;
        } catch (Tinebase_Exception $e) {
            return parent::_importRecord($_record, $_resolveStrategy, $_recordData);
        }

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
        $this->_members = $result['members'];
        $this->_groups = $result['groups'];
        $this->_rights = $result['rights'];

        return $result;
    }

    protected function addRights($rightsName)
    {
        if (!$rightsName) {
            return null;
        }
        if (!extension_loaded('yaml')) {
            throw new Tinebase_Exception('yaml extension required');
        }

        $importDir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR
            . 'Admin' . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'Rights';

        $files = array_diff(scandir($importDir), array('..', '.'));

        foreach ($files as $file) {
            $path = $importDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Importing DemoData set from file ' . $path);
                $setData = yaml_parse_file($path);

                if ($setData[$rightsName]) {
                    $set = $setData[$rightsName];

                    $roleRights = [];
                    // resolve rights
                    foreach ($set as $data) {
                        if ($data == 'AllAdmin') {
                            $enabledApps = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
                            foreach ($enabledApps as $application) {
                                $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
                                foreach ($allRights as $right) {
                                    $roleRights[] = array(
                                        'application_id' => $application->getId(),
                                        'right' => $right,
                                    );
                                }
                            }
                            return $roleRights;
                        }
                        $data = explode('/', $data);
                        try {
                            $appId = Tinebase_Application::getInstance()->getApplicationByName($data[0])->getId();
                            $rightsData = explode(',', $data[1]);
                            foreach ($rightsData as $right) {
                                $roleRights[] = array('application_id' => $appId, 'right' => $right);
                            }

                        } catch (Exception $e) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice
                            (__METHOD__ . '::' . __LINE__ . 'Application "' . $data[0] .
                                '" not installed. Skipping...' . PHP_EOL);

                        }
                    }
                }
            }
            return $roleRights;
        }
    }


    /**
     *   add role-account for user and group
     * @param $importedRecord
     */
    protected function _inspectAfterImport($importedRecord)
    {
        $role_id = $importedRecord['id'];
        $ids = array();
        if (!empty($this->_members)) {
            $members_list = explode(';', $this->_members);
            foreach ($members_list as $member) {
                $member_id = Tinebase_FullUser::getInstance()->getFullUserByLoginName($member)['accountId'];
                $ids['type'] = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
                $ids['id'] = $member_id;
                Tinebase_Role::getInstance()->addRoleMember($role_id, $ids);
            }
        }
        if (!empty($this->_groups)) {
            $groups = explode(';', $this->_groups);
            foreach ($groups as $group) {
                $group_id = Tinebase_Group::getInstance()->getGroupByName($group)['id'];
                $ids['type'] = Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP;
                $ids['id'] = $group_id;
                Tinebase_Role::getInstance()->addRoleMember($role_id, $ids);
            }
        }

        if(!empty($this->_rights)) {
            Tinebase_Role::getInstance()->setRoleRights($role_id, $this->addRights($this->_rights));
        }
    }
}
