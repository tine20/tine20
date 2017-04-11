<?php
/**
 * Filemanager Http frontend
 *
 * This class handles all Http requests for the Filemanager application
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Filemanager_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * download file
     * 
     * @param string $path
     * @param string $id
     * @param string $revision
     * @throws Tinebase_Exception_InvalidArgument
     * @todo allow to download a folder as ZIP file
     */
    public function downloadFile($path, $id, $revision = null)
    {
        $revision = $revision ?: null;
        
        $nodeController = Filemanager_Controller_Node::getInstance();
        if ($path) {
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($path));
            $node = $nodeController->getFileNode($pathRecord);
        } elseif ($id) {
            $node = $nodeController->get($id);
            $nodeController->resolveMultipleTreeNodesPath($node);
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));
        } else {
            throw new Tinebase_Exception_InvalidArgument('Either a path or id is needed to download a file.');
        }

        $this->_downloadFileNode($node, $pathRecord->streamwrapperpath, $revision);

        exit;
    }

    /**
     * download file
     *
     * @param string $_path
     * @param string $_type
     * @param int $_num
     * @param string $_revision
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function downloadPreview($_path, $_type, $_num = 0, $_revision = null)
    {
        $_revision = $_revision ?: null;

        $nodeController = Filemanager_Controller_Node::getInstance();
        if ($_path) {
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($_path));
            $node = $nodeController->getFileNode($pathRecord, $_revision);
        } else {
            throw new Tinebase_Exception_InvalidArgument('A path is needed to download a preview file.');
        }

        $this->_downloadPreview($node, $_type, $_num);

        exit;
    }
}
