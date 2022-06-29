<?php
/**
 * Tine 2.0
 * 
 * @package     EFile
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * class for EFile initialization
 * 
 * @package     Setup
 */
class EFile_Setup_Initialize extends Setup_Initialize
{
    /**
     * init system customfields
     */
    protected function _initializeSystemCFs()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }
        $appId = Tinebase_Core::getTinebaseId();
        $fmAppId = Tinebase_Application::getInstance()->getApplicationByName(Filemanager_Config::APP_NAME)->getId();


        $cf = new Tinebase_Model_CustomField_Config([
            'name' => EFile_Config::TREE_NODE_FLD_TIER_TYPE,
            'application_id' => $appId,
            'model' => Tinebase_Model_Tree_Node::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [EFile_Controller::class, 'registerTreeNodeHooks'],
                ],
                Tinebase_Model_CustomField_Config::CONTROLLER_HOOKS => [
                    '_jsonExpander' => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            EFile_Config::TREE_NODE_FLD_FILE_METADATA => [
                                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                    EFile_Model_FileMetadata::FLD_FINAL_DECREE_BY => [],
                                ]
                            ],
                        ]
                    ]
                ],
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::NAME              => EFile_Model_EFileTierType::MODEL_NAME_PART,
                    TMCC::LABEL             => 'eFile Tier Type', //_('eFile Tier Type')
                    TMCC::TYPE              => TMCC::TYPE_KEY_FIELD,
                    TMCC::SHY               => true,
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                    TMCC::NULLABLE          => true,
                    TMCC::OWNING_APP        => EFile_Config::APP_NAME,
                    TMCC::CONFIG            => [
                        TMCC::APPLICATION       => EFile_Config::APP_NAME,
                        TMCC::APP_NAME          => EFile_Config::APP_NAME,
                        TMCC::MODEL_NAME        => EFile_Model_EFileTierType::MODEL_NAME_PART,
                    ],
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);
        $cf->setId(null);
        $cf->application_id = $fmAppId;
        $cf->model = Filemanager_Model_Node::class;
        Tinebase_CustomField::getInstance()->addCustomField($cf);


        $cf = new Tinebase_Model_CustomField_Config([
            'name' => EFile_Config::TREE_NODE_FLD_FILE_METADATA,
            'application_id' => $appId,
            'model' => Tinebase_Model_Tree_Node::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'eFile File Metadata', //_('eFile File Metadata')
                    TMCC::TYPE              => TMCC::TYPE_RECORD,
                    TMCC::DOCTRINE_IGNORE   => true,
                    TMCC::DISABLED          => true,
                    TMCC::NULLABLE          => true,
                    TMCC::OMIT_MOD_LOG      => true,
                    TMCC::OWNING_APP        => EFile_Config::APP_NAME,
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                    TMCC::CONFIG            => [
                        TMCC::APPLICATION       => EFile_Config::APP_NAME,
                        TMCC::APP_NAME          => EFile_Config::APP_NAME,
                        TMCC::MODEL_NAME        => EFile_Model_FileMetadata::MODEL_NAME_PART,
                        TMCC::DEPENDENT_RECORDS => true,
                        TMCC::REF_ID_FIELD      => EFile_Model_FileMetadata::FLD_NODE_ID,
                    ],
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);
        $cf->setId(null);
        $cf->application_id = $fmAppId;
        $cf->model = Filemanager_Model_Node::class;
        Tinebase_CustomField::getInstance()->addCustomField($cf);


        $cf = new Tinebase_Model_CustomField_Config([
            'name' => EFile_Config::TREE_NODE_FLD_TIER_TOKEN,
            'application_id' => $appId,
            'model' => Tinebase_Model_Tree_Node::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'eFile Tier Token', //_('eFile Tier Token')
                    TMCC::TYPE              => TMCC::TYPE_STRING,
                    TMCC::DISABLED          => true,
                    TMCC::OWNING_APP        => EFile_Config::APP_NAME,
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                    TMCC::NULLABLE          => true,
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);
        $cf->setId(null);
        $cf->application_id = $fmAppId;
        $cf->model = Filemanager_Model_Node::class;
        Tinebase_CustomField::getInstance()->addCustomField($cf);


        $cf = new Tinebase_Model_CustomField_Config([
            'name' => EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER,
            'application_id' => $appId,
            'model' => Tinebase_Model_Tree_Node::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'eFile Tier Reference Number', //_('eFile Tier Reference Number')
                    TMCC::TYPE              => TMCC::TYPE_STRING,
                    TMCC::SHY               => true,
                    TMCC::OWNING_APP        => EFile_Config::APP_NAME,
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                    TMCC::NULLABLE          => true,
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);
        $cf->setId(null);
        $cf->application_id = $fmAppId;
        $cf->model = Filemanager_Model_Node::class;
        Tinebase_CustomField::getInstance()->addCustomField($cf);


        $cf = new Tinebase_Model_CustomField_Config([
            'name' => EFile_Config::TREE_NODE_FLD_TIER_COUNTER,
            'application_id' => $appId,
            'model' => Tinebase_Model_Tree_Node::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'eFile Tier Counter', //_('eFile Tier Counter')
                    TMCC::TYPE              => TMCC::TYPE_JSON,
                    TMCC::DISABLED          => true,
                    TMCC::NULLABLE          => true,
                    TMCC::OMIT_MOD_LOG      => true,
                    TMCC::OWNING_APP        => EFile_Config::APP_NAME,
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);
        $cf->setId(null);
        $cf->application_id = $fmAppId;
        $cf->model = Filemanager_Model_Node::class;
        Tinebase_CustomField::getInstance()->addCustomField($cf);
    }
}
