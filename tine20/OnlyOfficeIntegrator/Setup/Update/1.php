<?php

/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator_
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class OnlyOfficeIntegrator_Setup_Update_1 extends Setup_Update_Abstract
{
    const RELEASE001_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE001_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE001_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE001_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE001_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE001_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE001_UPDATE007 = __CLASS__ . '::update007';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE001_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE001_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE001_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE001_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE001_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE001_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE001_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update001()
    {
        Setup_SchemaTool::updateSchema([OnlyOfficeIntegrator_Model_History::class]);

        Tinebase_FileSystem::getInstance()->createAclNode(OnlyOfficeIntegrator_Controller::getRevisionsChangesPath());
        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.1', self::RELEASE001_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([OnlyOfficeIntegrator_Model_AccessToken::class]);
        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.2', self::RELEASE001_UPDATE002);
    }

    public function update003()
    {
        OnlyOfficeIntegrator_Controller_History::getInstance()->getBackend()->delete(
            array_keys(OnlyOfficeIntegrator_Controller_History::getInstance()->getBackend()->search(null, null, [
                Tinebase_Backend_Sql_Abstract::IDCOL, OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID
            ]))
        );
        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.3', self::RELEASE001_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([OnlyOfficeIntegrator_Model_History::class]);

        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.4', self::RELEASE001_UPDATE004);
    }

    public function update005()
    {
        (new OnlyOfficeIntegrator_Setup_Initialize())->addMissingInitializeCF();

        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.5', self::RELEASE001_UPDATE005);
    }

    public function update006()
    {
        // this is only done on primary and then replicated to the secondaries
        if (Tinebase_Core::isReplicationPrimary()) {
            $group = Tinebase_Group::getInstance()->create(new Tinebase_Model_Group([
                'name' => 'OnlyOfficeIntegratorQuarantine'
            ]));
            $grants = [
                'account_id' => $group->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_ADD => true,
                Tinebase_Model_Grants::GRANT_EDIT => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
                Tinebase_Model_Grants::GRANT_ADMIN => true,
            ];

            $path = Tinebase_Model_Tree_Node_Path::createFromRealPath('/shared/OOIQuarantine',
                Tinebase_Application::getInstance()->getApplicationByName('Filemanager'));
            Tinebase_FileSystem::getInstance()->createAclNode($path->statpath, new Tinebase_Record_RecordSet(
                Tinebase_Model_Grants::class, [$grants]));
        }

        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.6', self::RELEASE001_UPDATE006);
    }

    public function update007()
    {
        Setup_SchemaTool::updateSchema([OnlyOfficeIntegrator_Model_AccessToken::class]);
        (new OnlyOfficeIntegrator_Setup_Initialize())->_initializeSchedulerTasks();

        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.7', self::RELEASE001_UPDATE007);
    }
}
