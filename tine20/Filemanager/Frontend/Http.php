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
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(0);
        $nodeController = Filemanager_Controller_Node::getInstance();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
            . ' Download file ' . $path ? $path : $id
        );
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
        
        // cache for 3600 seconds
        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        
        // overwrite Pragma header from session
        header("Pragma: cache");
        
        header('Content-Disposition: attachment; filename="' . $node->name . '"');
        header("Content-Type: " . $node->contenttype);
        
        $handle = fopen($pathRecord->streamwrapperpath, 'r');
        fpassthru($handle);
        fclose($handle);

        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        exit;
    }
}
