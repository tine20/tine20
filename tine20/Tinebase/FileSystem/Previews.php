<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview images for file revisions
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Previews
{
    /**
     * @var Tinebase_FileSystem_Preview_ServiceInterface
     */
    protected $_previewService = NULL;

    /**
     * filesystem controller
     *
     * @var Tinebase_FileSystem
     */
    protected $_fsController = NULL;

    /**
     * @var string
     */
    protected $_basePath = NULL;

    /**
     * @var Tinebase_Model_Tree_Node
     */
    protected $_basePathNode = NULL;

    /**
     * @var array
     */
    protected $_supportedFileExtensions = array(
        'txt', 'rtf', 'odt', 'ods', 'odp', 'doc', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx', 'pdf', 'jpg', 'jpeg', 'gif', 'tiff', 'png'
    );

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_FileSystem_Previews
     */
    private static $_instance = NULL;

    /**
     * the constructor
     */
    protected function __construct()
    {
        $this->_fsController = Tinebase_FileSystem::getInstance();
        $this->_previewService = Tinebase_Core::getPreviewService();
    }

    /**
     * sets the preview service to be used. returns the old service
     *
     * @param Tinebase_FileSystem_Preview_ServiceInterface $_service
     * @return Tinebase_FileSystem_Preview_ServiceInterface
     */
    public function setPreviewService(Tinebase_FileSystem_Preview_ServiceInterface $_service)
    {
        $return = $this->_previewService;
        $this->_previewService = $_service;
        return $return;
    }

    /**
     * the singleton pattern
     *
     * @return Tinebase_FileSystem_Previews
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_FileSystem_Previews();
        }

        return self::$_instance;
    }

    /**
     * @return string
     */
    protected function _getBasePath()
    {
        if (null === $this->_basePath) {
            $this->_basePath = $this->_fsController->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), Tinebase_FileSystem::FOLDER_TYPE_PREVIEWS);
            if (!$this->_fsController->fileExists($this->_basePath)) {
                $this->_basePathNode = $this->_fsController->mkdir($this->_basePath);
            }
        }

        return $this->_basePath;
    }

    /**
     * @return Tinebase_Model_Tree_Node
     */
    public function getBasePathNode()
    {
        if (null === $this->_basePathNode) {
            $this->_basePathNode = $this->_fsController->stat($this->_getBasePath());
        }
        return $this->_basePathNode;
    }

    /**
     * @param string $_fileExtension
     * @return bool
     */
    public function isSupportedFileExtension($_fileExtension)
    {
        return in_array(mb_strtolower($_fileExtension), $this->_supportedFileExtensions);
    }

    protected function _getConfig()
    {
        return array(
            'thumbnail' => array(
                'firstPage' => true,
                'filetype'  => 'jpg',
                'x'         => 142,
                'y'         => 200,
                'color'     => 'white'
            ),
            'previews'  => array(
                'firstPage' => false,
                'filetype'  => 'jpg',
                'x'         => 708,
                'y'         => 1000,
                'color'     => 'white'
            )
        );
    }

    /**
     * @param string|Tinebase_Model_Tree_Node $_id
     * @param int $_revision
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function createPreviews($_id, $_revision = null)
    {
        $node = $_id instanceof Tinebase_Model_Tree_Node ? $_id : Tinebase_FileSystem::getInstance()->get($_id, $_revision);

        try {
            return $this->createPreviewsFromNode($node);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // this might throw Deadlock exceptions - ignore those
            if (strpos($zdse->getMessage(), 'Deadlock') !== false) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Ignoring deadlock / skipping preview generation - Error: '
                    . $zdse->getMessage());
                return false;
            } else {
                throw $zdse;
            }
        }
    }

    /**
     * @param Tinebase_Model_Tree_Node $node
     * @return bool
     */
    public function canNodeHavePreviews(Tinebase_Model_Tree_Node $node)
    {
        if ($node->type !== Tinebase_Model_Tree_FileObject::TYPE_FILE || empty($node->hash) || $node->size == 0 ||
                Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
                ->{Tinebase_Config::FILESYSTEM_PREVIEW_MAX_FILE_SIZE} < $node->size) {
            return false;
        }
        $fileExtension = pathinfo($node->name, PATHINFO_EXTENSION);

        return $this->isSupportedFileExtension($fileExtension);
    }

    /**
     * @param Tinebase_Model_Tree_Node $node
     * @return bool
     * @throws Tinebase_Exception
     */
    public function createPreviewsFromNode(Tinebase_Model_Tree_Node $node)
    {
        if (!$this->canNodeHavePreviews($node)) {
            return true;
        }

        $fileSystem = Tinebase_FileSystem::getInstance();
        $path = $fileSystem->getRealPathForHash($node->hash);
        $tempPath = Tinebase_TempFile::getTempPath() . '.' . pathinfo($node->name, PATHINFO_EXTENSION);
        if (!is_file($path)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' file ' . $node->getId() . ' ' . $node->name . ' is not present in filesystem: ' . $path);
            return false;
        }
        if (false === copy($path, $tempPath)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' could not copy file ' . $node->getId() . ' ' . $node->name . ' ' . $path . ' to temp path: '
                . $tempPath);
            return false;
        }

        try {
            $config = $this->_getConfig();

            if (false === ($result = $this->_previewService->getPreviewsForFile($tempPath, $config))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' preview creation for file ' . $node->getId() . ' ' . $node->name . ' failed');
                }
                return false;
            }
        } finally {
            unlink($tempPath);
        }

        foreach($config as $key => $cnf) {
            if (!isset($result[$key])) {
                return false;
            }
        }

        // reduce deadlock risk. We (remove and) create the base folder outside the transaction. This will fill
        // the stat cache and the update on the directory tree hashes will happen without prio read locks
        $basePath = $this->_getBasePath() . '/' . substr($node->hash, 0, 3) . '/' . substr($node->hash, 3);
        if (!$fileSystem->isDir($basePath)) {
            $fileSystem->mkdir($basePath);
        } else {
            if ($fileSystem->fileExists($basePath)) {
                $fileSystem->rmdir($basePath, true);
            }
            $fileSystem->mkdir($basePath);
        }
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {

            $fileSystem->acquireWriteLock();

            $files = array();
            $basePath = $this->_getBasePath() . '/' . substr($node->hash, 0, 3) . '/' . substr($node->hash, 3);
            if (!$fileSystem->isDir($basePath)) {
                $fileSystem->mkdir($basePath);
            }

            $maxCount = 0;
            foreach ($config as $key => $cnf) {
                $i = 0;
                foreach ($result[$key] as $blob) {
                    $files[$basePath . '/' . $key . '_' . ($i++) . '.' . $cnf['filetype']] = $blob;
                }
                if ($i > $maxCount) {
                    $maxCount = $i;
                }
            }

            unset($result);

            if ((int)$node->preview_count !== $maxCount) {
                $fileSystem->updatePreviewCount($node->hash, $maxCount);
            }

            foreach ($files as $name => &$blob) {
                $tempFile = Tinebase_TempFile::getTempPath();
                if (false === file_put_contents($tempFile, $blob)) {
                    throw new Tinebase_Exception('could not write content to temp file');
                }
                try {
                    $blob = null;
                    if (false === ($fh = fopen($tempFile, 'r'))) {
                        throw new Tinebase_Exception('could not open temp file for reading');
                    }

                    // this means we create a file node of type preview
                    $fileSystem->setStreamOptionForNextOperation(Tinebase_FileSystem::STREAM_OPTION_CREATE_PREVIEW,
                        true);
                    $fileSystem->copyTempfile($fh, $name);
                    fclose($fh);
                } finally {
                    unlink($tempFile);
                }
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

        } finally {
            if (null !== $transactionId) {
                // this only happens if an exception is thrown, no need to return false
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return true;
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_type
     * @param int $_num
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    public function getPreviewForNode(Tinebase_Model_Tree_Node $_node, $_type, $_num)
    {
        if (empty($_node->hash) || strlen($_node->hash) < 4) {
            throw new Tinebase_Exception_NotFound('node needs to have proper hash set');
        }

        $config = $this->_getConfig();
        if (!isset($config[$_type])) {
            throw new Tinebase_Exception_NotFound('type ' . $_type . ' not configured');
        }

        $fileSystem = Tinebase_FileSystem::getInstance();
        $path = $this->_getBasePath() . '/' . substr($_node->hash, 0, 3) . '/' . substr($_node->hash, 3)
                . '/' . $_type . '_' . $_num . '.' . $config[$_type]['filetype'];

        return $fileSystem->stat($path);
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function hasPreviews(Tinebase_Model_Tree_Node $_node)
    {
        return $_node->preview_count > 0;
    }

    /**
     * @param array $_hashes
     */
    public function deletePreviews(array $_hashes)
    {
        $fileSystem = Tinebase_FileSystem::getInstance();
        $basePath = $this->_getBasePath();
        foreach($_hashes as $hash) {
            try {
                $fileSystem->rmdir($basePath . '/' . substr($hash, 0, 3) . '/' . substr($hash, 3), true);
                // these hashes are unchecked, there may not be previews for them! => catch, no logging (debug at most)
            } catch(Tinebase_Exception_NotFound $tenf) {}
        }
    }

    /**
     * @return bool
     */
    public function deleteAllPreviews()
    {
        return Tinebase_FileSystem::getInstance()->rmdir($this->_getBasePath(), true);
    }
}