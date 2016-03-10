<?php

/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * Node controller for Expressodriver
 *
 * @package     Expressodriver
 * @subpackage  Controller
 */
class Expressodriver_Controller_Node
    implements Tinebase_Controller_SearchInterface, Tinebase_Controller_Record_Interface
{

    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Expressodriver';

    /**
     * Storage adapters backends
     *
     * @var array
     */
    protected static $_backends = array();

    /**
     * the model handled by this controller
     * @var string
     */
    protected $_modelName = 'Expressodriver_Model_Node';

    /**
     * TODO handle modlog
     * @var boolean
     */
    protected $_omitModLog = TRUE;

    /**
     * holds the total count of the last recursive search
     * @var integer
     */
    protected $_recursiveSearchTotalCount = 0;

    /**
     * holds the total count of result search
     *
     * @var integer
     */
    protected $_searchTotalCount = 0;

    /**
     * holds the instance of the singleton
     *
     * @var Expressodriver_Controller_Node
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        stream_wrapper_register('fakedir', 'Expressodriver_Backend_Storage_StreamDir');
        stream_wrapper_register('external', 'Expressodriver_Backend_Storage_StreamWrapper');
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
     * @return Expressodriver_Controller_Node
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressodriver_Controller_Node();
        }

        return self::$_instance;
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {

    }

    /**
     * get multiple tree nodes
     * @see Tinebase_Controller_Record_Abstract::getMultiple()
     * @param array $_ids Ids of tree nodes
     * @return  Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids)
    {
        // replace objects with their id's
        foreach ($_ids as &$id) {
            if ($id instanceof Tinebase_Record_Interface) {
                $id = $id->getId();
            }
        }

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array(), TRUE);
        foreach ($_ids as $id) {
            $result->addRecord($this->get($id));
        }

        return $result;
    }

    /**
     * Resolve path of multiple tree nodes
     *
     * @param Tinebase_Record_RecordSet|Tinebase_Model_Tree_Node $_records
     */
    public function resolveMultipleTreeNodesPath($_records)
    {

    }

    /**
     * Get tree node
     * @see Tinebase_Controller_Record_Abstract::get()
     * @param string $_id id for tree node
     * @param string $_containerId id for container
     */
    public function get($_id, $_containerId = NULL)
    {
        $path = base64_decode($_id);
        $node = $this->stat($path);
        if (!$node) {
            throw new Tinebase_Exception_NotFound('Node not found.');
        }
        return $this->stat($path);
    }

    /**
     * search tree nodes
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string|optional $_action
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $query = '';
        $path = null;

        if ($_filter->getFilter('query') && $_filter->getFilter('query')->getValue()) {
            $query = $_filter->getFilter('query')->getValue();
        }
        if ($_filter->getFilter('path') && $_filter->getFilter('path')->getValue()) {
            $path = $_filter->getFilter('path')->getValue();
        }

        if (($path === '/') || ($path == NULL)) {
            return $this->_getRootAdapterNodes();
        }

        $backend = $this->getAdapterBackend($path);
        $folderFiles = $backend->search($query, $this->removeUserBasePath($path));

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array(), TRUE);
        foreach ($folderFiles as $folderFile) {
            $result->addRecord($this->_createNodeFromRawData($folderFile, $backend->getName()));
        }

        if ($_filter->getFilter('type') && $_filter->getFilter('type')->getValue()) {
            $result = $result->filter('type', $_filter->getFilter('type')->getValue());
        }

        $this->_searchTotalCount = $result->count();

        $result->limitByPagination($_pagination);
        $result->sort($_pagination->sort, $_pagination->dir);

        return $result;
    }

    /**
     *  return root node with an adapter
     *
     * @return Tinebase_Record_RecordSet
     */
    private function _getRootAdapterNodes()
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array(), TRUE);
        $config = Expressodriver_Controller::getInstance()->getConfigSettings();
        foreach ($config['adapters'] as $adapter) {
            $node = array(
                'name' => $adapter['name'],
                'path' => '/' . $adapter['name'],
                'id' => base64_encode('/' . $adapter['name']),
                'type' => Tinebase_Model_Tree_Node::TYPE_FOLDER,
                'contenttype' => 'application/octet-stream',
                'account_grants' => array('readGrant' => true, 'addGrant' => true),
            );
            $result->addRecord(new Tinebase_Model_Tree_Node($node, TRUE));
        }

        $this->_searchTotalCount = count($config['adapters']);
        return $result;
    }

    /**
     * returns a node by path
     *
     * @param string $_path
     * @return Tinebase_Model_Tree_Node record of tree node
     */
    public function stat($_path)
    {
        $backend = $this->getAdapterBackend($_path);

        if ($this->removeUserBasePath($_path) === '/') {
            $data = array(
                'name' => $backend->getName(),
                'type' => Tinebase_Model_Tree_Node::TYPE_FOLDER
            );
        } else {
            $data = $backend->stat($this->removeUserBasePath($_path));
        }
        return $this->_createNodeFromRawData($data, $backend->getName());
    }

    /**
     * create a node from raw data sent by backend
     *
     * @param array $data
     * @return Tinebase_Model_Tree_Node
     */
    private function _createNodeFromRawData($data, $adapterName)
    {
        $node = null;
        if (!empty($data)) {
            $data['path'] = '/' . $adapterName . (substr($data['path'], 0, 1) === '/' ? '' : '/') . $data['path'];
            $data['id'] = base64_encode($data['path']);
            $data['object_id'] = base64_encode($data['path']);

            $node = new Tinebase_Model_Tree_Node($data, TRUE);
        }
        return $node;
    }

    /**
     * remove user base path
     *
     * @param string $_path path
     * @return string path
     */
    public function removeUserBasePath($_path)
    {
        $pathParts = explode('/', $_path);
        $adapterName = $pathParts[1];
        $completeBasePath = '/' . $adapterName;

        if (strcmp($_path, $completeBasePath) === 0) {
            $path = '/';
        } else if (strpos($_path, $completeBasePath) === 0) {
            $path = substr($_path, strlen($completeBasePath) + 1);
        }
        return $path;
    }

    /**
     * checks filter acl and adds base path
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * @return Tinebase_Model_Tree_Node_Path
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if ($_filter === NULL) {
            $_filter = new Expressodriver_Model_NodeFilter();
        }

        $pathFilters = $_filter->getFilter('path', TRUE);
        if (count($pathFilters) !== 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . 'Exactly one path filter required.');
            $pathFilter = (count($pathFilters) > 1) ? $pathFilters[0] : new Tinebase_Model_Tree_Node_PathFilter(array(
                'field' => 'path',
                'operator' => 'equals',
                'value' => '/',)
            );
            $_filter->removeFilter('path');
            $_filter->addFilter($pathFilter);
        } else {
            $pathFilter = $pathFilters[0];
        }

        // add base path and check grants
        try {
            $path = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($pathFilter->getValue()));
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Could not determine path, setting root path (' . $e->getMessage() . ')');
            $path = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath('/'));
        }
        $pathFilter->setValue($path);

        $this->_checkPathACL($path, $_action);

        return $path;
    }

    /**
     * get file node
     *
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @return Tinebase_Model_Tree_Node
     */
    public function getFileNode($_path)
    {
        $backend = $this->getAdapterBackend($_path);

        if (!$backend->fileExists($this->removeUserBasePath($_path))) {
            throw new Expressodriver_Exception('File does not exist,');
        }

        $node = $this->stat($_path);
        if ($node->type === Tinebase_Model_Tree_Node::TYPE_FOLDER) {
            throw new Expressodriver_Exception('Is a directory');
        }

        return $node;
    }

    /**
     * add base path
     *
     * @param Tinebase_Model_Tree_Node_PathFilter $_pathFilter
     * @return string
     */
    public function addBasePath($_path)
    {
        $basePath = $this->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName));
        $basePath .= '/folders';

        $path = (strpos($_path, '/') === 0) ? $_path : '/' . $_path;
        // only add base path once
        $result = (!preg_match('@^' . preg_quote($basePath) . '@', $path)) ? $basePath . $path : $path;

        return $result;
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string|optional $_action
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        //$path = $this->_checkFilterACL($_filter, $_action);
        return $this->_searchTotalCount;
    }

    /**
     * create node(s)
     *
     * @param array $_filenames
     * @param string $_type directory or file
     * @param array $_tempFileIds
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function createNodes($_filenames, $_type, $_tempFileIds = array(), $_forceOverwrite = FALSE)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $nodeExistsException = NULL;

        foreach ($_filenames as $idx => $filename) {
            $tempFileId = (isset($_tempFileIds[$idx])) ? $_tempFileIds[$idx] : NULL;

            try {
                $node = $this->_createNode($filename, $_type, $tempFileId, $_forceOverwrite);
                $result->addRecord($node);
            } catch (Expressodriver_Exception_NodeExists $fene) {
                $nodeExistsException = $this->_handleNodeExistsException($fene, $nodeExistsException);
            }
        }

        if ($nodeExistsException) {
            throw $nodeExistsException;
        }
        return $result;
    }

    /**
     * collect information of a Expressodriver_Exception_NodeExists in a "parent" exception
     *
     * @param Expressodriver_Exception_NodeExists $_fene
     * @param Expressodriver_Exception_NodeExists|NULL $_parentNodeExistsException
     */
    protected function _handleNodeExistsException($_fene, $_parentNodeExistsException = NULL)
    {
        // collect all nodes that already exist and add them to exception info
        if (!$_parentNodeExistsException) {
            $_parentNodeExistsException = new Expressodriver_Exception_NodeExists();
        }

        $nodesInfo = $_fene->getExistingNodesInfo();
        if (count($nodesInfo) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Adding node info to exception.');
            $_parentNodeExistsException->addExistingNodeInfo($nodesInfo->getFirstRecord());
        } else {
            return $_fene;
        }

        return $_parentNodeExistsException;
    }

    /**
     * create new node
     *
     * @param string $_path
     * @param string $_type
     * @param string $_tempFileId
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _createNode($_path, $_type, $_tempFileId = NULL, $_forceOverwrite = FALSE)
    {
        if (!in_array($_type, array(Tinebase_Model_Tree_Node::TYPE_FILE, Tinebase_Model_Tree_Node::TYPE_FOLDER))) {
            throw new Tinebase_Exception_InvalidArgument('Type ' . $_type . 'not supported.');
        }

        try {
            $this->_checkIfExists($_path);
        } catch (Expressodriver_Exception_NodeExists $fene) {
            if ($_forceOverwrite) {
                $existingNode = $this->stat($_path);
                if (!$_tempFileId) {
                    return $existingNode;
                }
            } else if (!$_forceOverwrite) {
                throw $fene;
            }
        }
        $newNode = $this->_createNodeInBackend($_path, $_type, $_tempFileId);
        $backend = $this->getAdapterBackend($_path);

        if ($newNode === NULL) {
            switch ($_type) {
                case Tinebase_Model_Tree_Node::TYPE_FILE:
                    // on upload, if file node was not created in backend we create a virtual node as expected by frontend
                    $newNode = $this->_createNodeFromRawData(
                            array(
                        'path' => $this->removeUserBasePath($_path),
                        'name' => urldecode(basename($_path)),
                        'type' => $_type,
                        'size' => 0,
                        'contenttype' => 'inode/x-empty',
                            ), $backend->getName()
                    );
                    break;
                case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                    throw new Tinebase_Exception_NotFound('Node not created.');
                    break;
            }
        }

        return $newNode;
    }

    /**
     * create node in backend
     *
     * @param string $_statpath
     * @param type
     * @param string $_tempFileId
     * @return Tinebase_Model_Tree_Node
     */
    protected function _createNodeInBackend($_statpath, $_type, $_tempFileId = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Creating new path ' . $_statpath . ' of type ' . $_type);

        $backend = $this->getAdapterBackend($_statpath);
        switch ($_type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                if ($_tempFileId !== NULL) {
                    $tempFile = ($_tempFileId instanceof Tinebase_Model_TempFile) ? $_tempFileId : Tinebase_TempFile::getInstance()->getTempFile($_tempFileId);
                    $backend->uploadFile($tempFile->path, $this->removeUserBasePath($_statpath));
                }
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                $backend->mkdir($this->removeUserBasePath($_statpath));
                break;
        }
        return $this->stat($_statpath);
    }

    /**
     * check file existance
     *
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param Tinebase_Model_Tree_Node $_node
     * @throws Expressodriver_Exception_NodeExists
     */
    protected function _checkIfExists($_path, $_node = NULL)
    {
        $backend = $this->getAdapterBackend($_path);
        if ($backend->fileExists($this->removeUserBasePath($_path))) {
            $existsException = new Expressodriver_Exception_NodeExists();
            if ($_node === NULL) {
                $existsException->addExistingNodeInfo($this->stat($_path));
            } else {
                $existsException->addExistingNodeInfo($_node);
            }
            throw $existsException;
        }
    }

    /**
     * check acl of path
     *
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param string $_action
     * @param boolean $_topLevelAllowed
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkPathACL(Tinebase_Model_Tree_Node_Path $_path, $_action = 'get', $_topLevelAllowed = TRUE)
    {
        $hasPermission = FALSE;

        if ($_path->container) {
            $hasPermission = $this->_checkACLContainer($_path->container, $_action);
        } else if ($_topLevelAllowed) {
            switch ($_path->containerType) {
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    if ($_path->containerOwner) {
                        $hasPermission = ($_path->containerOwner === Tinebase_Core::getUser()->accountLoginName || $_action === 'get');
                    } else {
                        $hasPermission = ($_action === 'get');
                    }
                    break;
                case Tinebase_Model_Container::TYPE_SHARED:
                    $hasPermission = ($_action !== 'get') ? $this->checkRight(Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS, FALSE) : TRUE;
                    break;
                case Tinebase_Model_Tree_Node_Path::TYPE_ROOT:
                    $hasPermission = ($_action === 'get');
                    break;
                default :
                    $hasPermission = TRUE;
            }
        } else {
            // @todo: check acl for path
            $hasPermission = TRUE;
        }

        if (!$hasPermission) {
            throw new Tinebase_Exception_AccessDenied('No permission to ' . $_action . ' nodes in path ' . $_path->flatpath);
        }
    }

    /**
     * copy nodes
     *
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function copyNodes($_sourceFilenames, $_destinationFilenames, $_forceOverwrite = FALSE)
    {
        return $this->_copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, 'copy', $_forceOverwrite);
    }

    /**
     * copy or move an array of nodes identified by their path
     *
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param string $_action copy|move
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, $_action, $_forceOverwrite = FALSE)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $nodeExistsException = NULL;

        foreach ($_sourceFilenames as $idx => $sourcePathRecord) {
            $destinationPathRecord = $this->_getDestinationPath($_destinationFilenames, $idx, $sourcePathRecord);

            try {
                if ($_action === 'move') {
                    $node = $this->_moveNode($sourcePathRecord, $destinationPathRecord, $_forceOverwrite);
                } else if ($_action === 'copy') {
                    $node = $this->_copyNode($sourcePathRecord, $destinationPathRecord, $_forceOverwrite);
                }
                $result->addRecord($node);
            } catch (Expressodriver_Exception_NodeExists $fene) {
                $nodeExistsException = $this->_handleNodeExistsException($fene, $nodeExistsException);
            }
        }

        if ($nodeExistsException) {
            // @todo add correctly moved/copied files here?
            throw $nodeExistsException;
        }
        return $result;
    }

    /**
     * get single destination from an array of destinations and an index + $_sourcePathRecord
     *
     * @param string|array $_destinationFilenames
     * @param int $_idx
     * @param Tinebase_Model_Tree_Node_Path $_sourcePathRecord
     * @return Tinebase_Model_Tree_Node_Path
     * @throws Expressodriver_Exception
     *
     * @todo add Tinebase_FileSystem::isDir() check?
     */
    protected function _getDestinationPath($_destinationFilenames, $_idx, $_sourcePathRecord)
    {
        if (is_array($_destinationFilenames)) {
            $isdir = FALSE;
            if (isset($_destinationFilenames[$_idx])) {
                $destination = $_destinationFilenames[$_idx];
            } else {
                throw new Expressodriver_Exception('No destination path found.');
            }
        } else {
            $isdir = TRUE;
            $destination = $_destinationFilenames;
        }

        if ($isdir) {
            $destination = $destination . '/' . $_sourcePathRecord;
        }
        return $destination;
    }

    /**
     * copy single node
     *
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _copyNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination, $_forceOverwrite = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Copy Node ' . $_source->flatpath . ' to ' . $_destination->flatpath);

        $newNode = NULL;

        $this->_checkPathACL($_source, 'get', FALSE);

        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $path = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($_source->flatpath, $app);
        $sourceNode = $this->stat($path);

        switch ($sourceNode->type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                $newNode = $this->_copyOrMoveFileNode($_source, $_destination, 'copy', $_forceOverwrite);
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                $newNode = $this->_copyFolderNode($_source, $_destination);
                break;
        }

        return $newNode;
    }

    /**
     * copy file node
     *
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param string $_action
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _copyOrMoveFileNode($_source, $_destination, $_action, $_forceOverwrite = FALSE)
    {
        $destinationPath = $_destination;

        $backend = $this->getAdapterBackend($_destination);

        try {
            $this->_checkIfExists($destinationPath);
        } catch (Expressodriver_Exception_NodeExists $fene) {
            if ($_forceOverwrite && $_source->statpath !== $_destination->statpath) {
                // delete old node
                $backend->unlink($this->removeUserBasePath($destinationPath));
            } elseif (!$_forceOverwrite) {
                throw $fene;
            }
        }

        $sourcePath = $_source;
        switch ($_action) {
            case 'copy':

                $backend->copy($this->removeUserBasePath($sourcePath->path), $this->removeUserBasePath($destinationPath));
                break;
            case 'move':
                $backend->rename($this->removeUserBasePath($sourcePath->path), $this->removeUserBasePath($destinationPath));
                break;
        }
        $newNode = $this->stat($destinationPath);
        if (!$newNode) {
            throw new Tinebase_Exception_AccessDenied('Operation failed');
        }
        return $newNode;
    }

    /**
     * copy folder node
     *
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @return Tinebase_Model_Tree_Node
     * @throws Expressodriver_Exception_NodeExists
     *
     * @todo add $_forceOverwrite?
     */
    protected function _copyFolderNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination)
    {
        $newNode = $this->_createNode($_destination, Tinebase_Model_Tree_Node::TYPE_FOLDER);

        // recursive copy for (sub-)folders/files
        $filter = new Tinebase_Model_Tree_Node_Filter(array(array(
                'field' => 'path',
                'operator' => 'equals',
                'value' => Tinebase_Model_Tree_Node_Path::removeAppIdFromPath(
                        $_source->flatpath, Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)
                ),
        )));
        $result = $this->search($filter);
        if (count($result) > 0) {
            $this->copyNodes($result->path, $newNode->path);
        }

        return $newNode;
    }

    /**
     * move nodes
     *
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function moveNodes($_sourceFilenames, $_destinationFilenames, $_forceOverwrite = FALSE)
    {
        return $this->_copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, 'move', $_forceOverwrite);
    }

    /**
     * move single node
     *
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _moveNode($_source, $_destination, $_forceOverwrite = FALSE)
    {
        $sourceNode = $this->stat($_source);

        if (!$sourceNode) {
            throw new Tinebase_Exception_NotFound('Node not moved. Maybe the node was removed.');
        }

        switch ($sourceNode->type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                $movedNode = $this->_copyOrMoveFileNode($sourceNode, $_destination, 'move', $_forceOverwrite);
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                $movedNode = $this->_moveFolderNode($_source, $sourceNode, $_destination, $_forceOverwrite);
                break;
        }

        return $movedNode;
    }

    /**
     * move folder node
     *
     * @param Tinebase_Model_Tree_Node_Path $source
     * @param Tinebase_Model_Tree_Node $sourceNode [unused]
     * @param Tinebase_Model_Tree_Node_Path $destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _moveFolderNode($source, $sourceNode, $destination, $_forceOverwrite = FALSE)
    {
        $backend = $this->getAdapterBackend($destination);

        try {
            $this->_checkIfExists($destination);
        } catch (Expressodriver_Exception_NodeExists $fene) {
            if ($_forceOverwrite && $source !== $destination) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' Removing folder node ' . $destination->statpath);
                $backend->rmdir($this->removeUserBasePath($destination), TRUE);
            } else if (!$_forceOverwrite) {
                throw $fene;
            }
        }

        $backend->rename($this->removeUserBasePath($source), $this->removeUserBasePath($destination));

        $movedNode = $this->stat($destination);


        if (!$movedNode) {
            throw new Tinebase_Exception_AccessDenied('Operation failed');
        }

        return $movedNode;
    }

    /**
     * move folder container
     *
     * @param Tinebase_Model_Tree_Node_Path $source
     * @param Tinebase_Model_Tree_Node_Path $destination
     * @param boolean $forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _moveFolderContainer($source, $destination, $forceOverwrite = FALSE)
    {
        if ($source->isToplevelPath()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Moving container ' . $source->container->name . ' to ' . $destination->flatpath);

            $this->_checkACLContainer($source->container, 'update');
            $backend = $this->getAdapterBackend($destination->statpath);
            $container = $source->container;
            if ($container->name !== $destination->name) {
                try {
                    $existingContainer = Tinebase_Container::getInstance()->getContainerByName(
                            $this->_applicationName, $destination->name, $destination->containerType, Tinebase_Core::getUser()
                    );
                    if (!$forceOverwrite) {
                        $fene = new Expressodriver_Exception_NodeExists('container exists');
                        $fene->addExistingNodeInfo($backend->stat($destination->statpath));
                        throw $fene;
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                    . ' Removing existing folder node and container ' . $destination->flatpath);
                        $backend->rmdir($destination->statpath, TRUE);
                    }
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // ok
                }

                $container->name = $destination->name;
                $container = Tinebase_Container::getInstance()->update($container);
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Creating container ' . $destination->name);
            $container = $this->_createContainer($destination->name, $destination->containerType);
        }

        $destination->setContainer($container);
    }

    /**
     * delete nodes
     *
     * @param array $_filenames string->single file, array->multiple
     * @return int delete count
     *
     * @todo add recursive param?
     */
    public function deleteNodes($_filenames)
    {
        $deleteCount = 0;
        foreach ($_filenames as $filename) {
            if ($this->_deleteNode($filename)) {
                $deleteCount++;
            }
        }

        return $deleteCount;
    }

    /**
     * delete node
     *
     * @param string $_flatpath
     * @return boolean
     * @throws Tinebase_Exception_NotFound
     */
    protected function _deleteNode($_flatpath)
    {
        $success = $this->_deleteNodeInBackend($_flatpath);
        // @todo: some improvement here if we have container as parent folder
        return $success;
    }

    /**
     * delete node in backend
     *
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @return boolean
     */
    protected function _deleteNodeInBackend($_path)
    {
        $success = FALSE;

        $node = $this->stat($_path);
        $backend = $this->getAdapterBackend($_path);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Removing path ' . $_path->flatpath . ' of type ' . $node->type);

        switch ($node->type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                $success = $backend->unlink($this->removeUserBasePath($_path));
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                $success = $backend->rmdir($this->removeUserBasePath($_path), TRUE);
                break;
        }

        return $success;
    }

    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $nodes = $this->getMultiple($_ids);
        foreach ($nodes as $node) {
            $checkACL = true; // @todo: check node delete acl
            if ($checkACL) {
                $this->_deleteNode($node->path);
            } else {
                $nodes->removeRecord($node);
            }
        }

        return $nodes;
    }

    /**
     * get application base path
     *
     * @param Tinebase_Model_Application|string $_application
     * @param string $_type
     * @return string
     */
    public function getApplicationBasePath($_application, $_type = NULL)
    {
        $application = $_application instanceof Tinebase_Model_Application ? $_application : Tinebase_Application::getInstance()->getApplicationById($_application);

        $result = '/' . $application->getId();

        if ($_type !== NULL) {
            if (!in_array($_type, array(Tinebase_Model_Container::TYPE_SHARED, Tinebase_Model_Container::TYPE_PERSONAL, self::FOLDER_TYPE_RECORDS))) {
                throw new Tinebase_Exception_UnexpectedValue('Type can only be shared or personal.');
            }

            $result .= '/folders/' . $_type;
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::update()
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        return $_record;
    }

    /**
     *Create tree node
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean $_duplicateCheck
     * @param boolean $_getOnReturn
     * @return Tinebase_Record_Interface
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE, $_getOnReturn = TRUE)
    {
        return $_record;
    }

    /**
     * get an adapter instance according to the path
     *
     * pathParts:
     * [0] =>
     * [1] => external
     * [2] => accountLogin
     * [3] => adapterName
     * [4..] => path in backend
     *
     * @param string $_path
     * @return Expressodriver_Backend_Adapter_Interface
     * @throws Expressodriver_Exception
     */
    public function getAdapterBackend($_path)
    {
        $pathParts = explode('/', $_path);
        $adapterName = $pathParts[1];

        if (!isset(self::$_backends[$adapterName])) {

            $adapter = null;
            $config = Expressodriver_Controller::getInstance()->getConfigSettings();
            foreach ($config['adapters'] as $adapterConfig) {
                if ($adapterName === $adapterConfig['name']) {
                    $adapter = $adapterConfig;
                }
            }
            if (!is_null($adapter)) {

                $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
                $userCredentialCache = Tinebase_Core::getUserCredentialCache();
                $credentialsBackend->getCachedCredentials($userCredentialCache);

                $password = !(empty($userCredentialCache->password)) ?
                        $userCredentialCache->password :
                        Expressodriver_Session::getSessionNamespace()->password[$adapterName];

                if (empty($password)) {
                    $exception = new Expressodriver_Exception_CredentialsRequired();
                    $exception->setAdapterName($adapterName);
                    throw $exception;
                }

                $username = $adapter['useEmailAsLoginName']
                        ? Tinebase_Core::getUser()->accountEmailAddress
                        : Tinebase_Core::getUser()->accountLoginName;

                $options = array(
                    'host' => $adapter['url'],
                    'user' => $username,
                    'password' => $password,
                    'root' => '/',
                    'name' => $adapter['name'],
                    'useCache' => $config['default']['useCache'],
                    'cacheLifetime' => $config['default']['cacheLifetime'],
                );

                self::$_backends[$adapterName] = Expressodriver_Backend_Storage_Abstract::factory($adapter['adapter'], $options);
            } else {
                throw new Expressodriver_Exception('Adapter config does not exists');
            }
        }
        return self::$_backends[$adapterName];
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Controller/Record/Interface::getAll()
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Controller/Record/Interface::updateMultiple()
     */
    public function updateMultiple($_what, $_data)
    {
    }

}
