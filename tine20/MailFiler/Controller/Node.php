<?php
/**
 * Tine 2.0
 *
 * @package     MailFiler
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Node controller for MailFiler
 *
 * @package     MailFiler
 * @subpackage  Controller
 */
class MailFiler_Controller_Node extends Filemanager_Controller_Node
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'MailFiler';

    /**
     * the model handled by this controller
     *
     * @var string
     */
    protected $_modelName = 'MailFiler_Model_Node';

    /**
     * holds the instance of the singleton
     *
     * @var MailFiler_Controller_Node
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_backend = Tinebase_FileSystem::getInstance();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return MailFiler_Controller_Node
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new MailFiler_Controller_Node();
        }

        return self::$_instance;
    }

    /**
     * search tree nodes
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string|optional $_action
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $result = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);

        $nodes = $this->_convertToMailFilerModelNodeRecordSet($result);
        return $nodes;
    }

    /**
     * we need to have the correct model
     *
     * @param $records
     * @return Tinebase_Record_RecordSet
     *
     * TODO improve this: parent get/search should already return the correct model...
     */
    protected function _convertToMailFilerModelNodeRecordSet($records)
    {
        $nodes = new Tinebase_Record_RecordSet($this->_modelName, array());
        foreach ($records as $record) {
            $recordArray = $record->toArray();
            $node = new $this->_modelName($recordArray, /* $_bypassFilters */ true);
            foreach ($recordArray as $key => $value) {
                if ($record->{$key} instanceof Tinebase_Record_RecordSet) {
                    $node->{$key} = $record->{$key};
                }
            }
            $nodes->addRecord($node);
        }
        return $nodes;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::get()
     */
    public function get($_id, $_containerId = NULL)
    {
        $result = parent::get($_id, $_containerId);
        return $this->_convertToMailFilerModelNodeRecordSet(array($result))->getFirstRecord();
    }

    /**
     * file message
     *
     * @param                          $targetPath
     * @param Felamimail_Model_Message $message
     * @returns Filemanager_Model_Node
     * @throws
     * @throws Filemanager_Exception_NodeExists
     * @throws Tinebase_Exception_AccessDenied
     * @throws null
     */
    public function fileMessage($targetPath, Felamimail_Model_Message $message)
    {
        $node = parent::fileMessage($targetPath, $message);

        $this->_createMessageInMailFiler($message, $node);

        return $node;
    }

    /**
     * @param $message
     * @param $node
     */
    protected function _createMessageInMailFiler($message, $node)
    {
        // unset id to prevent duplicate constraint problems on messages added multiple times
        unset($message->id);

        $mailFilerMessage = new MailFiler_Model_Message();
        $mailFilerMessage->setFromJsonInUsersTimezone($message->toArray());
        $mailFilerMessage->structure = $message->structure;
        $mailFilerMessage->node_id = $node->getId();
        $to = $mailFilerMessage->to;
        sort($to);
        $mailFilerMessage->to_flat = join(', ', $to);

        MailFiler_Controller_Message::getInstance()->create($mailFilerMessage);
    }
}
