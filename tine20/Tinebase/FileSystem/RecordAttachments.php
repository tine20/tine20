<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * filesystem attachments for records
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_RecordAttachments
{
    /**
     * filesystem controller
     * 
     * @var Tinebase_FileSystem
     */
    protected $_fsController = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_FileSystem_RecordAttachments
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     */
    protected function __construct()
    {
        $this->_fsController  = Tinebase_FileSystem::getInstance();
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_FileSystem_RecordAttachments
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_FileSystem_RecordAttachments();
        }
        
        return self::$_instance;
    }
    
    /**
     * fetch all file attachments of a record
     * 
     * @param Tinebase_Record_Interface $record
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function getRecordAttachments(Tinebase_Record_Interface $record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Fetching attachments of ' . get_class($record) . ' record with id ' . $record->getId() . ' ...');
        
        $parentPath = $this->getRecordAttachmentPath($record);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' Looking in path ' . $parentPath);
        
        try {
            $record->attachments = $this->_fsController->scanDir($parentPath);
            foreach($record->attachments as $node) {
                $nodePath = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_fsController->getPathOfNode($node,
                    true));
                $node->path = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($nodePath->flatpath,
                    $record->getApplication());
            }

            // to resolve grants... but as not needed currently we save the effort
            //Filemanager_Controller_Node::getInstance()->resolveGrants($record->attachments);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $record->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && count($record->attachments) > 0) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Found ' . count($record->attachments) . ' attachment(s).');
        
        return $record->attachments;
    }
    
    /**
     * fetches attachments for multiple records at once
     * 
     * @param Tinebase_Record_RecordSet $records
     * @return Tinebase_Record_RecordSet
     *
     * @todo maybe this should be improved
     */
    public function getMultipleAttachmentsOfRecords($records)
    {
        if ($records instanceof Tinebase_Record_Interface) {
            $records = new Tinebase_Record_RecordSet(get_class($records), array($records));
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Fetching attachments for ' . $records->count() . ' record(s)');
        if ($records->count() === 0) {
            return new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }

        $recordNodeMapping = array();
        $className = $records->getRecordClassName();
        $recordIds = [];
        
        foreach ($records as $record) {
            $recordIds[] = $record->getId();
            
            $record->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }
        

        $classPathName = $this->_fsController->getApplicationBasePath($record->getApplication(),
                Tinebase_FileSystem::FOLDER_TYPE_RECORDS) . '/' . $className;

        // top folder for record attachments
        try {
            $classPathNode = $this->_fsController->stat($classPathName);

        } catch (Tinebase_Exception_NotFound $tenf) {
            return new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }

        // subfolders for all records attachments
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => 'equals',
                'value'     => $classPathNode->getId()
            ),
            array(
                'field'     => 'name',
                'operator'  => 'in',
                'value'     => $recordIds
            )
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
        $recordNodes = $this->_fsController->searchNodes($searchFilter);
        if ($recordNodes->count() === 0) {
            // nothing to be done
            return new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }
        foreach ($recordNodes as $recordNode) {
            $recordNodeMapping[$recordNode->getId()] = $recordNode->name;
        }

        // get attachments
        $attachmentNodes = $this->_fsController->getTreeNodeChildren($recordNodes);

        // add attachments to records
        foreach ($attachmentNodes as $attachmentNode) {
            $record = $records->getById($recordNodeMapping[$attachmentNode->parent_id]);
            $nodePath = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_fsController->getPathOfNode($attachmentNode,true));
            $attachmentNode->path = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($nodePath->flatpath, $record->getApplication());

            $record->attachments->addRecord($attachmentNode);
        }

        return $attachmentNodes;
    }
    
    /**
     * set file attachments of a record
     * 
     * @param Tinebase_Record_Interface $record
     */
    public function setRecordAttachments(Tinebase_Record_Interface $record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' Record: ' . print_r($record->toArray(), TRUE));
        
        $currentAttachments = ($record->getId()) ? $this->getRecordAttachments(clone $record) : new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $attachmentsToSet = ($record->attachments instanceof Tinebase_Record_RecordSet) 
            ? $record->attachments
            : new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', (array)$record->attachments, TRUE);
        
        $attachmentDiff = $currentAttachments->diff($attachmentsToSet);

        foreach ($attachmentDiff->removed as $removed) {
            $this->_fsController->deleteFileNode($removed);
        }

        foreach ($attachmentDiff->modified as $modified) {
            $this->_fsController->update($attachmentsToSet->getById($modified->getId()));
        }

        foreach ($attachmentDiff->added as $added) {
            try {
                $this->addRecordAttachment($record, $added->name, $added);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                    ' Could not add new attachment ' . print_r($added->toArray(), TRUE) . ' to record: ' . $record->getId()
                    . ' / Error Message: ' . $teia->getMessage());
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                    ' Could not add new attachment ' . print_r($added->toArray(), TRUE) . ' to record: ' . $record->getId()
                    . ' / Error Message: ' . $tenf->getMessage());
            }
        }
    }
    
    /**
     * add attachement to record
     * 
     * @param  Tinebase_Record_Interface $record
     * @param  string $name
     * @param  mixed $attachment
         @see Tinebase_FileSystem::copyTempfile
     * @return null|Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_Duplicate
     */
    public function addRecordAttachment(Tinebase_Record_Interface $record, $name, $attachment)
    {
        // only occurs via unittests
        if (!$name && isset($attachment->tempFile) && ! is_resource($attachment->tempFile)) {
            $attachment = Tinebase_TempFile::getInstance()->getTempFile($attachment->tempFile);
            $name = $attachment->name;
        }

        if ($attachment instanceof Tinebase_Model_Tree_Node && !isset($attachment->tempFile)) {
            if (isset($attachment->id)) {
                try {
                    $tmpNode = $this->_fsController->get($attachment->id, true);
                    $tmpPath = $this->_fsController->getPathOfNode($tmpNode, true);
                    $attachment = $this->_fsController->stat($tmpPath, null, true);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                            __LINE__ . ' could not find attachment record with id: ' . $attachment->id);
                }
            } else {
                // this comes from \Calendar_Frontend_CalDAV_PluginManagedAttachments::httpPOSTHandler
                // it sends an filenode with only hash and name and a bit set
                if (empty($attachment->hash)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                            __LINE__ . ' attachment record is missing an id');
                    return null;
                }
            }
        }

        if ($attachment instanceof Tinebase_Model_Tree_Node && empty($name)) {
            $name = $attachment->name;
        }

        if (empty($name)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                ' Could not evaluate attachment name.');
            return null;
        }
        
        $attachmentsDir = $this->getRecordAttachmentPath($record, TRUE);
        $attachmentPath = $attachmentsDir . '/' . $name;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Creating new record attachment ' . $attachmentPath);
        if ($this->_fsController->fileExists($attachmentPath)) {
            throw new Tinebase_Exception_Duplicate('File already exists');
        }
        
        $this->_fsController->copyTempfile($attachment, $attachmentPath);
        
        $node = $this->_fsController->stat($attachmentPath);
        return $node;
    }
    
    /**
     * delete attachments of record
     * 
     * @param Tinebase_Record_Interface $record
     */
    public function deleteRecordAttachments($record)
    {
        $attachments = ($record->attachments instanceof Tinebase_Record_RecordSet) ? $record->attachments : $this->getRecordAttachments($record);
        foreach ($attachments as $node) {
            $this->_fsController->deleteFileNode($node);
        }
    }
    
    /**
     * get path for record attachments
     * 
     * @param Tinebase_Record_Interface $record
     * @param boolean $createDirIfNotExists
     * @throws Tinebase_Exception_InvalidArgument
     * @return string
     */
    public function getRecordAttachmentPath(Tinebase_Record_Interface $record, $createDirIfNotExists = false)
    {
        if (! $record->getId()) {
            throw new Tinebase_Exception_InvalidArgument('record needs an identifier');
        }
        
        $parentPath = $this->_fsController->getApplicationBasePath($record->getApplication(),
            Tinebase_FileSystem::FOLDER_TYPE_RECORDS);
        $recordPath = $parentPath . '/' . get_class($record) . '/' . $record->getId();
        if ($createDirIfNotExists && ! $this->_fsController->fileExists($recordPath)) {
            $this->_fsController->mkdir($recordPath);
        }
        
        return $recordPath;
    }

    /**
     * get base path for record attachments (without the record id)
     *
     * @param Tinebase_Record_Interface $record
     * @param boolean $createDirIfNotExists
     * @return string
     */
    public function getRecordAttachmentBasePath(Tinebase_Record_Interface $record, $createDirIfNotExists = false)
    {
        $parentPath = $this->_fsController->getApplicationBasePath($record->getApplication(),
            Tinebase_FileSystem::FOLDER_TYPE_RECORDS);
        $recordPath = $parentPath . '/' . get_class($record);
        if ($createDirIfNotExists && ! $this->_fsController->fileExists($recordPath)) {
            $this->_fsController->mkdir($recordPath);
        }

        return $recordPath;
    }
}
