<?php
/**
 * Filemanager public download frontend
 *
 * This class handles all public download requests for the Filemanager application
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Filemanager_Frontend_Download extends Tinebase_Frontend_Http_Abstract
{
    /**
     * download file
     * 
     * @param string $id
     * 
     * @todo allow to download a folder as ZIP file
     */
    public function downloadFile($id)
    {
        try {
            $download = Filemanager_Controller_DownloadLink::getInstance()->get($id);
        } catch (Tinebase_Exception_NotFound $tenf) {
            header('HTTP/1.0 404 Not found');
            
            die("File not found!");
        }
        
        $nodeBackend = new Tinebase_Tree_Node();
        
        $node = $nodeBackend->get($download->node_id);
        
        $nodeController = Filemanager_Controller_Node::getInstance();
        $nodeController->resolveMultipleTreeNodesPath($node);
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));
        
        $this->_downloadFileNode($node, $pathRecord->streamwrapperpath);
        
        exit;
    }
}
