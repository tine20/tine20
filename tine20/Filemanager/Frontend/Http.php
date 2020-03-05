<?php
/**
 * Filemanager Http frontend
 *
 * This class handles all Http requests for the Filemanager application
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $this->_downloadFileNodeByPathOrId($path, $id, $revision);
        exit;
    }

    /**
     * _downloadFileNodeByPathOrId
     *
     * @param      $path
     * @param      $id
     * @param null $revision
     * @throws Filemanager_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _downloadFileNodeByPathOrId($path, $id, $revision = null)
    {
        $revision = $revision ?: null;

        $nodeController = Filemanager_Controller_Node::getInstance();
        if ($path) {
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($path));
            try {
                $node = $nodeController->getFileNode($pathRecord);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_FORBIDDEN);
            }
        } elseif ($id) {
            $node = $nodeController->get($id);
            $nodeController->resolveMultipleTreeNodesPath($node);
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));
        } else {
            throw new Tinebase_Exception_InvalidArgument('Either a path or id is needed to download a file.');
        }

        $this->_downloadFileNode($node, $pathRecord->streamwrapperpath, $revision);
    }
}
