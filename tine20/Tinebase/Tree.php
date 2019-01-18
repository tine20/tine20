<?php
/**
 * Tine 2.0 tree fake controller
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * tree fake controller, so that Tinebase_Core::getApplicationInstance('Tinebase_Model_Tree_Node') will return this
 * @see Tinebase_Tree_Node::getInstance()
 *
 * @package     Tinebase
 */
class Tinebase_Tree implements Tinebase_Controller_Interface
{
    /**
     * holds the _instance of the singleton
     *
     * @var Tinebase_Tree
     */
    private static $_instance = NULL;

    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone()
    {}

    /**
     * the constructor
     *
     * disabled. use the singleton
     */
    private function __construct()
    {}

    /**
     * the singleton pattern
     *
     * @return Tinebase_Tree
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Tree;
        }

        return self::$_instance;
    }

    public function get($_id)
    {
        return Tinebase_FileSystem::getInstance()->get($_id);
    }

    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $_modification)
    {
        $treeBackend = Tinebase_FileSystem::getInstance()->_getTreeNodeBackend();
        switch($_modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $node = new Tinebase_Model_Tree_Node($diff->diff);
                $this->_prepareReplicationRecord($node);
                /**
                 * things that can go wrong:
                 * * name not unique...
                 * * parent_id was deleted
                 * * revisionProps, notificationProps, acl_node
                 */
                $treeBackend->create($node);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                /** @var Tinebase_Model_Tree_Node $record */
                $record = $treeBackend->get($_modification->record_id, true);
                if (isset($diff->diff['grants']) && $record->acl_node === $record->getId()) {
                    Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($record);
                }
                $record->applyDiff($diff);
                $this->_prepareReplicationRecord($record);
                $treeBackend->update($record);
                if (isset($diff->diff['grants']) && $record->acl_node === $record->getId()) {
                    Tinebase_FileSystem::getInstance()->setGrantsForNode($record, $record->grants);
                    //Tinebase_Tree_NodeGrants::getInstance()->setGrants($record);
                }
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                $treeBackend->softDelete(array($_modification->record_id));
                break;

            default:
                throw new Tinebase_Exception_UnexpectedValue('change_type ' . $_modification->change_type . ' unknown');
        }
    }

    /**
     * @param Tinebase_Model_Tree_Node $_record
     */
    protected function _prepareReplicationRecord(Tinebase_Model_Tree_Node $_record)
    {
        // unset properties that are maintained only locally
        $_record->preview_count = null;
    }

    /**
     * @param Tinebase_Model_ModificationLog $_modification
     * @param bool $_dryRun
     */
    public function undoReplicationModificationLog(Tinebase_Model_ModificationLog $_modification, $_dryRun)
    {
        $treeBackend = Tinebase_FileSystem::getInstance()->_getTreeNodeBackend();
        switch($_modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                if (true === $_dryRun) {
                    return;
                }
                $treeBackend->softDelete($_modification->record_id);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $node = $treeBackend->get($_modification->record_id);
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $node->undo($diff);

                if (true !== $_dryRun) {
                    $treeBackend->update($node);
                }
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                if (true === $_dryRun) {
                    return;
                }
                $node = $treeBackend->get($_modification->record_id, true);
                $node->is_deleted = false;
                $treeBackend->update($node);
                break;

            default:
                throw new Tinebase_Exception_UnexpectedValue('change_type ' . $_modification->change_type . ' unknown');
        }
    }
}