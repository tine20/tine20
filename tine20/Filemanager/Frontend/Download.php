<?php
/**
 * Filemanager public download frontend
 *
 * This class handles all public download requests for the Filemanager application
 * 
 * Apache rewrite rules
 * # Anonymous downloads
 * RewriteRule ^download/get/(.*)  index.php?method=Download.downloadNode&path=$1 [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
 * RewriteRule ^download/show/(.*) index.php?method=Download.displayNode&path=$1  [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @todo        allow to download a folder as ZIP file
 */
class Filemanager_Frontend_Download extends Tinebase_Frontend_Http_Abstract
{
    /**
     * @var Tinebase_Tree_Node
     */
    protected $_treeNodeBackend;
    
    /**
     * display download
     * 
     * @param string $path
     */
    public function displayNode($path)
    {
        try {
            $splittedPath = explode('/', trim($path, '/'));
            
            $downloadId = array_shift($splittedPath);
            
            $download = $this->_getDownload($downloadId);
            
            $node = $this->_getRootNode($download);
    
            foreach ($splittedPath as $subPath) {
                $node = $this->_getTreeNodeBackend()->getChild($node, $subPath);
            }
            
            switch ($node->type) {
                case Tinebase_Model_Tree_Node::TYPE_FILE:
                    $this->_displayFile($download, $node, $splittedPath);
                    break;
                    
                case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                    $this->_listDirectory($download, $node, $splittedPath);
                    break;
            }
            
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(
                __METHOD__ . '::' . __LINE__ . ' exception: ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' exception: ' . $e->getTraceAsString());
            
            header('HTTP/1.0 404 Not found');
            
            $view = new Zend_View();
            $view->setScriptPath('Filemanager/views');
            
            header('Content-Type: text/html; charset=utf-8');
            die($view->render('notfound.phtml'));
        }
        
        exit;
    }
    
    /**
     * download file
     * 
     * @param string $path
     */
    public function downloadNode($path)
    {
        try {
            $splittedPath = explode('/', trim($path, '/'));
            
            $downloadId = array_shift($splittedPath);
            
            $download = $this->_getDownload($downloadId);
            
            $node = $this->_getRootNode($download);
            
            foreach ($splittedPath as $subPath) {
                $node = $this->_getTreeNodeBackend()->getChild($node, $subPath);
            }
            
            if ($node->type === Tinebase_Model_Tree_Node::TYPE_FILE) {
                $nodeController = Filemanager_Controller_Node::getInstance();
                $nodeController->resolveMultipleTreeNodesPath($node);
                $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));
                
                $this->_downloadFileNode($node, $pathRecord->streamwrapperpath);
            }
            
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(
                __METHOD__ . '::' . __LINE__ . ' exception: ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' exception: ' . $e->getTraceAsString());
            
            header('HTTP/1.0 404 Not found');
            
            $view = new Zend_View();
            $view->setScriptPath('Filemanager/views');
            
            header('Content-Type: text/html; charset=utf-8');
            die($view->render('notfound.phtml'));
        }
        
        exit;
    }
    
    /**
     * resolve download id
     * 
     * @param  string $id the download id
     * @return Filemanager_Model_DownloadLink
     */
    protected function _getDownload($id)
    {
        $download = Filemanager_Controller_DownloadLink::getInstance()->get($id);
        
        return $download;
    }
    
    /**
     * resolve root tree node
     * 
     * @param  Filemanager_Model_DownloadLink $download
     * @return Tinebase_Model_Tree_Node
     */
    protected function _getRootNode(Filemanager_Model_DownloadLink $download)
    {
        $node = $this->_getTreeNodeBackend()->get($download->node_id);
        
        return $node;
    }
    
    /**
     * get tree node backend instance
     * 
     * @return Tinebase_Tree_Node
     */
    protected function _getTreeNodeBackend()
    {
        if (!$this->_treeNodeBackend) {
            $this->_treeNodeBackend = new Tinebase_Tree_Node();
        }
        
        return $this->_treeNodeBackend;
    }
    
    /**
     * generate directroy listing
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @param Tinebase_Model_Tree_Node       $node
     * @param array                          $path
     */
    protected function _listDirectory(Filemanager_Model_DownloadLink $download, Tinebase_Model_Tree_Node $node, $path)
    {
        $view = new Zend_View();
        $view->setScriptPath('Filemanager/views');
        
        $view->path = '/' . implode('/', $path);
        
        $basePath = '/download/show/' . $download->getId() . '/';
        
        if (count($path) > 0) {
             $basePath .= implode('/', $path) . '/';
        }
        
        $children = $this->_getTreeNodeBackend()->getChildren($node);
        foreach ($children as $child) {
            $child->path = $basePath . $child->name;
        }
        
        $view->files = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        if (count($path) > 0) {
            $parent = $this->_getTreeNodeBackend()->get($node->parent_id);
            $parent->name = '..';
            $parent->path = $basePath . '..';
            
            $view->files->addRecord($parent);
        }
        $view->files->merge($children->filter('type', Tinebase_Model_Tree_Node::TYPE_FOLDER)->sort('name'));
        $view->files->merge($children->filter('type', Tinebase_Model_Tree_Node::TYPE_FILE)->sort('name'));
        
        header('Content-Type: text/html; charset=utf-8');
        die($view->render('folder.phtml'));
    }
    
    /**
     * generate file overview
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @param Tinebase_Model_Tree_Node       $node
     * @param array                          $path
     */
    protected function _displayFile(Filemanager_Model_DownloadLink $download, Tinebase_Model_Tree_Node $node, $path)
    {
        $view = new Zend_View();
        $view->setScriptPath('Filemanager/views');
        
        $view->path = '/' . implode('/', $path);
        
        $view->file = $node;
        $view->file->path = '/download/get/' . $download->getId() . '/' . implode('/', $path);
        
        header('Content-Type: text/html; charset=utf-8');
        die($view->render('file.phtml'));
    }
}
