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
 * @copyright   Copyright (c) 2014-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        allow to download a folder as ZIP file
 *
 * ATTENTION all public methods in this class are reachable without tine authentification
 */
class Filemanager_Frontend_Download extends Tinebase_Frontend_Http_Abstract
{
    /**
     * display download
     * 
     * @param string $path
     */
    public function displayNode($path)
    {
        try {
            $splittedPath = explode('/', trim($path, '/'));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__
                . ' Display download node with path ' . print_r($splittedPath, true));
            
            $downloadId = array_shift($splittedPath);
            $download = $this->_getDownloadLink($downloadId);

            if (! $this->_verfiyPassword($download)) {
                $this->_renderPasswordForm();
                exit;
            }

            $this->_setDownloadLinkOwnerAsUser($download);
            
            $node = Filemanager_Controller_DownloadLink::getInstance()->getNode($download, $splittedPath);
            
            switch ($node->type) {
                case Tinebase_Model_Tree_FileObject::TYPE_FILE:
                    $this->_displayFile($download, $node, $splittedPath);
                    break;
                    
                case Tinebase_Model_Tree_FileObject::TYPE_FOLDER:
                    $this->_listDirectory($download, $node, $splittedPath);
                    break;
            }
            
        } catch (Exception $e) {
            if ($e instanceof Tinebase_Exception_ProgramFlow) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            } else {
                Tinebase_Exception::log($e);
            }
            $this->_renderNotFoundPage();
        }
        
        exit;
    }

    protected function _verfiyPassword($download)
    {
        if (! Filemanager_Controller_DownloadLink::getInstance()->hasPassword($download)) {
            return true;
        }

        $password = $this->_getPassword();
        if (Filemanager_Controller_DownloadLink::getInstance()->validatePassword($download, $password)) {
            // save password in cookie / 1 hour lifetime
            setcookie('dlpassword', $password, time() + 3600, '/download');
            return true;
        }

        return false;
    }

    /**
     * fetch password from request
     *
     * @return string
     *
     * TODO improve this: maybe we can get the param from the Zend\Http\Request object
     *  -> $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
     */
    protected function _getPassword()
    {
        if (isset($_REQUEST['dlpassword'])) {
            return $_REQUEST['dlpassword'];
        } elseif (isset($_COOKIE['dlpassword'])) {
                return $_COOKIE['dlpassword'];
        } else {
            return '';
        }
    }

    /**
     * renderPasswordForm
     */
    protected function _renderPasswordForm()
    {
        $view = $this->_getView();
        $locale = new Zend_Locale();
        $translation = Tinebase_Translation::getTranslation('Filemanager', $locale);
        $view->pwtext = $translation->_('Password for download required');
        $view->title = $view->pwtext;
        $view->submittext = $translation->_('Submit');
        header('Content-Type: text/html; charset=utf-8');
        die($view->render('password.phtml'));
    }

    /**
     * renderNotFoundPage
     */
    protected function _renderNotFoundPage()
    {
        header('HTTP/1.0 404 Not found');
        $view = $this->_getView();
        $locale = new Zend_Locale();
        $translation = Tinebase_Translation::getTranslation('Filemanager', $locale);
        $view->notfound = $translation->_('File not found!');
        $view->title = $view->notfound;
        header('Content-Type: text/html; charset=utf-8');
        die($view->render('notfound.phtml'));
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
            $download = $this->_getDownloadLink($downloadId);

            if (! $this->_verfiyPassword($download)) {
                $this->_renderPasswordForm();
                exit;
            }

            $this->_setDownloadLinkOwnerAsUser($download);
            
            $node = Filemanager_Controller_DownloadLink::getInstance()->getNode($download, $splittedPath);
            
            if ($node->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                $nodeController = Filemanager_Controller_Node::getInstance();
                $nodeController->resolveMultipleTreeNodesPath($node);
                $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));

                Filemanager_Controller_DownloadLink::getInstance()->increaseAccessCount($download);
                $this->_downloadFileNode($node, $pathRecord->streamwrapperpath);
            }
            
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $this->_renderNotFoundPage();
        }
        
        exit;
    }
    
    /**
     * resolve download id
     * 
     * @param  string $id
     * @return Filemanager_Model_DownloadLink
     */
    protected function _getDownloadLink($id)
    {
        $download = Filemanager_Controller_DownloadLink::getInstance()->get($id);
        
        return $download;
    }
    
    /**
     * generate directory listing
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @param Tinebase_Model_Tree_Node       $node
     * @param array                          $path
     */
    protected function _listDirectory(Filemanager_Model_DownloadLink $download, Tinebase_Model_Tree_Node $node, $path)
    {
        $view = $this->_getView($path, $node);
        $view->files = Filemanager_Controller_DownloadLink::getInstance()->getFileList($download, $path, $node);

        $locale = new Zend_Locale();
        $translation = Tinebase_Translation::getTranslation('Filemanager', $locale);
        $view->name = $translation->_('Name');
        $view->size = $translation->_('Size');
        $view->lastmodified = $translation->_('Last Modified');
        $view->description = $translation->_('Description');
        $view->title = $translation->_('Folder');

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
        $view = $this->_getView($path, $node);

        $view->file = $node;
        $view->file->path = $download->getDownloadUrl('get') . '/' . implode('/', $path);

        // <tr><th>Name</th><th>letzte Änderung</th><th>Größe</th></tr>
        $locale = new Zend_Locale();
        $translation = Tinebase_Translation::getTranslation('Filemanager', $locale);
        $view->name = $translation->_('Name');
        $view->size = $translation->_('Size');
        $view->lastmodified = $translation->_('Last Modified');
        $view->title = $translation->_('File');
        
        header('Content-Type: text/html; charset=utf-8');
        die($view->render('file.phtml'));
    }

    protected function _getView($path = null, $node = null)
    {
        $view = new Zend_View();
        $view->setScriptPath('Filemanager/views');

        $view->logoPath = Tinebase_ImageHelper::getDataUrl(Tinebase_Core::getInstallLogo());

        if ($path !== null) {
            $view->path = (empty($path)) ? '/' . $node->name : '/' . implode('/', $path);
        }
        
        return $view;
    }
    
    /**
     * sets download link owner (creator) as current user to ensure ACL handling
     * 
     * @param Filemanager_Model_DownloadLink $download
     */
    protected function _setDownloadLinkOwnerAsUser(Filemanager_Model_DownloadLink $download)
    {
        $user = Tinebase_User::getInstance()->getFullUserById($download->created_by);
        Tinebase_Core::set(Tinebase_Core::USER, $user);
    }
}
