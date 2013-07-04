<?php
/**
 * Filemanager Http frontend
 *
 * This class handles all Http requests for the Filemanager application
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * 
     * @todo allow to download a folder as ZIP file
     */
    public function downloadFile($path, $id)
    {
        $nodeController = Filemanager_Controller_Node::getInstance();
        if ($path) {
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($path));
            $node = $nodeController->getFileNode($pathRecord);
        } elseif ($id) {
            $node = $nodeController->get($id);
            $nodeController->resolveMultipleTreeNodesPath($node);
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));
        } else {
            Tinebase_Exception_InvalidArgument('Either a path or id is needed to download a file.');
        }
        
        $this->_downloadFileNode($node, $pathRecord->streamwrapperpath);
        exit;
    }
}
