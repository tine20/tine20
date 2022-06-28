<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * class for Tinebase initialization
 *
 * @package     OnlyOfficeIntegrator
 */
class OnlyOfficeIntegrator_Setup_Initialize extends Setup_Initialize
{
    public function addMissingInitializeCF()
    {
        $this->_initializeCustomFields();
    }

    protected function _initializeQuarantine()
    {
        // this is only done on primary and then replicated to the secondaries
        if (!Tinebase_Core::isReplicationPrimary()) {
            return;
        }

        $group = Tinebase_Group::getInstance()->create(new Tinebase_Model_Group([
            'name' => 'OnlyOfficeIntegratorQuarantine'
        ]));
        $grants = [
            'account_id'                           => $group->getId(),
            'account_type'                         => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            Tinebase_Model_Grants::GRANT_READ      => true,
            Tinebase_Model_Grants::GRANT_ADD       => true,
            Tinebase_Model_Grants::GRANT_EDIT      => true,
            Tinebase_Model_Grants::GRANT_DELETE    => true,
            Tinebase_Model_Grants::GRANT_EXPORT    => true,
            Tinebase_Model_Grants::GRANT_SYNC      => true,
            Tinebase_Model_Grants::GRANT_ADMIN     => true,
        ];

        try {
            $path = Tinebase_Model_Tree_Node_Path::createFromRealPath('/shared/OOIQuarantine',
                Tinebase_Application::getInstance()->getApplicationByName('Filemanager'));
            Tinebase_FileSystem::getInstance()->createAclNode($path->statpath, new Tinebase_Record_RecordSet(
                Tinebase_Model_Grants::class, [$grants]));
        } catch (Tinebase_Exception_SystemGeneric $e) {
            // node already exits
        }
    }

    protected function _initializeCustomFields()
    {
        // this is only done on primary and then replicated to the secondaries
        if (!Tinebase_Core::isReplicationPrimary()) {
            return;
        }
        
        $appId = Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId();

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => OnlyOfficeIntegrator_Config::FM_NODE_EDITING_CFNAME,
            'application_id' => $appId,
            'model' => Tinebase_Model_Tree_Node::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'Editing', // _('Editing')
                    TMCC::TYPE              => TMCC::TYPE_BOOLEAN,
                    TMCC::IS_VIRTUAL        => true,
                    TMCC::CONVERTERS        => [
                        OnlyOfficeIntegrator_Model_Converter_FMNodeEditing::class,
                    ],
                ],
            ]
        ], true));

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME,
            'application_id' => $appId,
            'model' => Tinebase_Model_Tree_Node::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'Current Editors', // _('Current Editors')
                    TMCC::TYPE              => TMCC::TYPE_VIRTUAL,
                    TMCC::IS_VIRTUAL        => true,
                    TMCC::CONFIG            => [
                        TMCC::FUNCTION          => [OnlyOfficeIntegrator_Model_Node::class, 'resolveTBTreeNode'],
                    ]
                ],
            ]
        ], true));
    }

    /**
     * create new document templates path and put the templates there
     */
    public function _initializeNewTemplateFiles()
    {
        // this is only done on primary and then replicated to the secondaries
        if (Tinebase_Core::isReplicationSlave()) {
            return;
        }

        $basePath = OnlyOfficeIntegrator_Controller::getNewTemplatePath();
        try {
            Tinebase_FileSystem::getInstance()->createAclNode($basePath);
        }  catch (Tinebase_Exception_SystemGeneric $e) {
            // node already exits
        }
        $dir = dir(dirname(__DIR__) . '/templates');
        while (false !== ($file = $dir->read())) {
            if (strpos($file, 'new.') === 0) {
                $src = fopen($dir->path . '/' . $file, 'r');
                $trgt = fopen('tine20://' . $basePath . '/' . $file, 'w');
                stream_copy_to_stream($src, $trgt);
                fclose($trgt);
                fclose($src);
            }
        }

        $dir->close();
    }

    /**
     * create revisions changes path
     */
    protected function _initializeCreateRevisionsChangesPath()
    {
        // this is only done on master and then replicated to the slave
        if (Tinebase_Core::isReplicationSlave()) {
            return;
        }

        Tinebase_FileSystem::getInstance()->createAclNode(OnlyOfficeIntegrator_Controller::getRevisionsChangesPath());
    }

    public function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        if ($scheduler->hasTask('OOI_ForceSave')) {
            return;
        }

        $scheduler->create(new Tinebase_Model_SchedulerTask([
            'name'          => 'OOI_ForceSave',
            'config'        => new Tinebase_Scheduler_Task([
                'cron'      => Tinebase_Scheduler_Task::TASK_TYPE_MINUTELY,
                'callables' => [[
                    Tinebase_Scheduler_Task::CONTROLLER    => OnlyOfficeIntegrator_Controller_AccessToken::class,
                    Tinebase_Scheduler_Task::METHOD_NAME   => 'scheduleForceSaves',
                ]]
            ]),
            'next_run'      => new Tinebase_DateTime('2001-01-01 01:01:01')
        ]));
    }
}
