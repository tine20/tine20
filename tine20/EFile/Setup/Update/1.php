<?php
/**
 * Tine 2.0
 *
 * @package     EFile
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class EFile_Setup_Update_1 extends Setup_Update_Abstract
{
    const RELEASE001_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE001_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE   => [
            self::RELEASE001_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE001_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update001()
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName(EFile_Config::APP_NAME);
        if (($basePath = $this->getDb()->query('SELECT value FROM ' . SQL_TABLE_PREFIX . 'config WHERE application_id = "' .
                $app->getId() . '" AND name = "' . EFile_Config::BASE_PATH . '"')->fetchColumn())) {
            $this->getDb()->query('update ' . SQL_TABLE_PREFIX . 'config SET value = \'["' . $basePath .
                '"]\' WHERE application_id = "' . $app->getId() . '" AND name = "' . EFile_Config::BASE_PATH . '"');
        }

        try {
            $newCounterNode = EFile_Controller::getApplicationNode();
            $oldCounterNode = Tinebase_FileSystem::getInstance()->stat(Filemanager_Controller_Node::getInstance()
                ->addBasePath(current(EFile_Config::getInstance()->{EFile_Config::BASE_PATH})));
            $counter = $oldCounterNode->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER};

            if (!empty($counter) && isset($counter[EFile_Model_EFileTierType::TIER_TYPE_CASE])) {
                foreach (array_keys($counter) as $key) {
                    if ($key !== EFile_Model_EFileTierType::TIER_TYPE_CASE) {
                        unset($counter[$key]);
                    }
                }

                if (!empty($counter)) {
                    Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->updateMultiple([$newCounterNode->getId()], [
                        EFile_Config::TREE_NODE_FLD_TIER_COUNTER => json_encode($counter)
                    ]);
                    $counter = $oldCounterNode->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER};
                    unset($counter[EFile_Model_EFileTierType::TIER_TYPE_CASE]);
                    Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->updateMultiple([$oldCounterNode->getId()], [
                        EFile_Config::TREE_NODE_FLD_TIER_COUNTER => json_encode($counter)
                    ]);
                }
            }
        } catch (Tinebase_Exception_NotFound $tenf) {}
        
        $this->addApplicationUpdate(EFile_Config::APP_NAME, '1.1', self::RELEASE001_UPDATE001);
    }

    public function update002()
    {
        $efileCfg = EFile_Config::getInstance();

        $basePaths = $efileCfg->{EFile_Config::BASE_PATH};
        foreach ($basePaths as &$path) {
            if (strrpos($path, '/') !== (strlen($path) - 1)) {
                $path .= '/';
            }
        }
        $efileCfg->{EFile_Config::BASE_PATH} = $basePaths;

        $this->addApplicationUpdate(EFile_Config::APP_NAME, '1.2', self::RELEASE001_UPDATE002);
    }
}
