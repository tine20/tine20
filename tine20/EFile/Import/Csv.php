<?php
/**
 * Tine 2.0
 * 
 * @package     EFile
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the efile structures
 * 
 * @package     EFile
 * @subpackage  Import
 *
 */
class EFile_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    protected $treeNodeForcedBackend = null;

    protected $history = [];

    protected $basePath = '';

    /**
     * constructs a new importer from given config
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        $_options['model'] = Tinebase_Model_Tree_Node::class;

        parent::__construct($_options);

        $this->treeNodeForcedBackend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME      => Tinebase_Model_Tree_Node::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME      => 'tree_nodes',
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE   => false,
        ]);

        $this->basePath = Filemanager_Controller_Node::getInstance()
            ->addBasePath(EFile_Config::getInstance()->{EFile_Config::BASE_PATH}[0]);
    }

    protected function _processRawData($_data)
    {
        // FIXME log ignored lines
        if (count($_data) < 5) {
            return [];
        }
        for ($i = 0; $i < 5; ++$i) {
            if (!isset($_data[$i])) {
                return [];
            }
        }
        if (0 == strlen($_data[4])) {
            return [];
        }
        return [$_data];
    }

    protected function _createRecordToImport($_recordData)
    {
        return $_recordData;
    }

    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        $parent = null;
        $history = &$this->history;
        $counterValue = null;
        /* attention $i usage further down below! */
        for ($i = 0; $i < 3; ++$i) {
            if (0 === strlen($_record[$i])) {
                // FIXME LOG
                return;
            }
            if (isset($history[(int)$_record[$i]])) {
                $parent = $history[(int)$_record[$i]]['parent'];
                $history = &$history[(int)$_record[$i]]['history'];
            } else {
                $counterValue = (int)$_record[$i];
                break;
            }
        }
        if (isset($_record[3]) && strlen($_record[3]) > 0) {
            /* beware of the $i */
            if (3 !== $i) {
                // FIXME LOG
                return;
            }
            $counterValue = (int)$_record[3];
            $tierType = EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP;
        } else {
            /* beware of the $i */
            if (3 === $i) {
                // FIXME LOG
                return;
            }
            $tierType = EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN;
        }
        if (null === $parent) {
            $parent = Tinebase_FileSystem::getInstance()->stat($this->basePath);
            $parentPath = $this->basePath;
        } else {
            $parentPath = Tinebase_FileSystem::getInstance()->getPathOfNode($parent, true) . '/';
        }
        // reload!
        $parent = Tinebase_FileSystem::getInstance()->get($parent->getId());
        $newNodePath = $parentPath . $_record[4];

        $counter = $parent->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER};
        if (!is_array($counter)) $counter = [];
        if (isset($counter[$tierType]) && $counter[$tierType] >= $counterValue) {
            throw new Tinebase_Exception_UnexpectedValue('counterValue ' . $counterValue . ' to low for path ' . $newNodePath);
        }
        $counter[$tierType] = $counterValue - 1;
        $parent->{EFile_Config::TREE_NODE_FLD_TIER_COUNTER} = $counter;
        $this->treeNodeForcedBackend->updateMultiple([$parent->getId()], [
            EFile_Config::TREE_NODE_FLD_TIER_COUNTER => json_encode($counter)
        ]);
        Tinebase_FileSystem::getInstance()->clearStatCache($parentPath);

        $node = EFile_Controller::getInstance()->createEFileFolder($newNodePath, $tierType);
        /* beware of the $i */
        if (3 !== $i) {
            $history[$counterValue]['history'] = [];
            $history[$counterValue]['parent'] = $node;
        }
        return true;
    }
}
