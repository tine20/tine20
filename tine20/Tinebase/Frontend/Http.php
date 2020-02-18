<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * HTTP interface to Tine
 *
 * ATTENTION all public methods in this class are reachable without tine authentification
 * use $this->checkAuth(); if method requires authentification
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    const REQUEST_TYPE = 'HttpPost';
    
    /**
     * get json-api service map
     * 
     * @return string
     */
    public static function getServiceMap()
    {
        $smd = Tinebase_Server_Json::getServiceMap();
        
        $smdArray = $smd->toArray();
        unset($smdArray['methods']);
        
        if (! isset($_REQUEST['method']) || $_REQUEST['method'] != 'Tinebase.getServiceMap') {
            return $smdArray;
        }
        
        header('Content-type: application/json');
        echo '_smd = ' . json_encode($smdArray);
        die();
    }
    
    /**
     * return xrds file
     * used to autodiscover openId servers
     * 
     * @return void
     */
    public function getXRDS()
    {
        // selfUrl == http://servername/pathtotine20/users/loginname
        $url = dirname(dirname(Zend_OpenId::selfUrl())) . '/index.php?method=Tinebase.openId';
        
        header('Content-type: application/xrds+xml');
        
        echo '<?xml version="1.0"?>
            <xrds:XRDS xmlns="xri://$xrd*($v*2.0)" xmlns:xrds="xri://$xrds">
              <XRD>
                <Service priority="0">
                  <Type>http://specs.openid.net/auth/2.0/signon</Type>
                  <URI>' . $url . '</URI>
                </Service>
                <Service priority="1">
                  <Type>http://openid.net/signon/1.1</Type>
                  <URI>' . $url . '</URI>
                </Service>
                <Service priority="2">
                  <Type>http://openid.net/signon/1.0</Type>
                  <URI>' . $url . '</URI>
                </Service>
              </XRD>
            </xrds:XRDS>';
    }
    
    /**
     * display user info page
     * 
     * in the future we can display public informations about the user here too
     * currently it is only used as entry point for openId
     * 
     * @param string $username the username
     * @return void
     */
    public function userInfoPage($username)
    {
        // selfUrl == http://servername/pathtotine20/users/loginname
        $openIdUrl = dirname(dirname(Zend_OpenId::selfUrl())) . '/index.php?method=Tinebase.openId';
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $view->openIdUrl = $openIdUrl;
        $view->username = $username;

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('userInfoPage.php');
    }
    
    /**
     * handle all kinds of openId requests
     * 
     * @return void
     */
    public function openId()
    {
        Tinebase_Core::startCoreSession();
        $server = new Tinebase_OpenId_Provider(
            null,
            null,
            new Tinebase_OpenId_Provider_User_Tine20(),
            new Tinebase_OpenId_Provider_Storage()
        );
        $server->setOpEndpoint(dirname(Zend_OpenId::selfUrl()) . '/index.php?method=Tinebase.openId');
        
        // handle openId login form
        if (isset($_POST['openid_action']) && $_POST['openid_action'] === 'login') {
            $server->login($_POST['openid_identifier'], $_POST['password'], $_POST['username']);
            unset($_GET['openid_action']);
            $this->_setJsonKeyCookie();
            Zend_OpenId::redirect(dirname(Zend_OpenId::selfUrl()) . '/index.php', $_GET);

        // display openId login form
        } else if (isset($_GET['openid_action']) && $_GET['openid_action'] === 'login') {
            $view = new Zend_View();
            $view->setScriptPath('Tinebase/views');
            
            $view->openIdIdentity = $_GET['openid_identity'];
            $view->loginName = $_GET['openid_identity'];
    
            header('Content-Type: text/html; charset=utf-8');
            echo $view->render('openidLogin.php');

        // handle openId trust form
        } else if (isset($_POST['openid_action']) && $_POST['openid_action'] === 'trust') {
            if (isset($_POST['allow'])) {
                if (isset($_POST['forever'])) {
                    $server->allowSite($server->getSiteRoot($_GET));
                }
                $server->respondToConsumer($_GET);
            } else if (isset($_POST['deny'])) {
                if (isset($_POST['forever'])) {
                    $server->denySite($server->getSiteRoot($_GET));
                }
                Zend_OpenId::redirect($_GET['openid_return_to'],
                                      array('openid.mode'=>'cancel'));
            }

        // display openId trust form
        } else if (isset($_GET['openid_action']) && $_GET['openid_action'] === 'trust') {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Display openId trust screen");
            $view = new Zend_View();
            $view->setScriptPath('Tinebase/views');
            
            $view->openIdConsumer = $server->getSiteRoot($_GET);
            $view->openIdIdentity = $server->getLoggedInUser();
            
            header('Content-Type: text/html; charset=utf-8');
            echo $view->render('openidTrust.php');

        // handle all other openId requests
        } else {
            $result = $server->handle();

            if (is_string($result)) {
                echo $result;
            } elseif ($result !== true) {
                header('HTTP/1.0 403 Forbidden');
                return;
            }
        }
    }
    
    /**
     * checks if a user is logged in. If not we redirect to login
     */
    protected function checkAuth()
    {
        try {
            if (!Tinebase_Core::getUser() instanceof Tinebase_Model_User) {
                header('HTTP/1.0 403 Forbidden');
                exit;
            }
        } catch (Exception $e) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
    }
    
    /**
     * renders the login dialog
     *
     * @todo perhaps we could add a config option to display the update dialog if it is set
     */
    public function login()
    {
        if ($this->_redirect()) {
            return;
        }

        // check if setup/update required
        if (Setup_Controller::getInstance()->updateNeeded()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' updates required');
            return $this->setupRequired();
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' rendering mainscreen');

        return $this->mainScreen();
    }

    protected function _redirect()
    {
        // redirect to REDIRECTURL if set
        $redirectUrl = Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL, '');

        if ($redirectUrl !== '' && Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTALWAYS, FALSE)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' redirecting to ' . $redirectUrl);
            header('Location: ' . $redirectUrl);
            return true;
        }

        return false;
    }

    /**
     * renders the setup/update required dialog
     */
    public function setupRequired()
    {
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__CLASS__ . '::' . __METHOD__ . ' (' . __LINE__ .') Update/Setup required!');

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('update.php');
        exit();
    }

    /**
     * login from HTTP post 
     * 
     * redirects the tine main screen if authentication is successful
     * otherwise redirects back to login url 
     */
    public function loginFromPost($username, $password)
    {
        Tinebase_Core::startCoreSession();
        
        if (!empty($username)) {
            // try to login user
            $success = (Tinebase_Controller::getInstance()->login(
                $username,
                $password,
                Tinebase_Core::get(Tinebase_Core::REQUEST),
                self::REQUEST_TYPE
            ) === TRUE);
        } else {
            $success = FALSE;
        }
        
        if ($success === TRUE) {
            $this->_setJsonKeyCookie();

            $ccAdapter = Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter();
            if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
                $ccAdapter->setCache(Tinebase_Core::getUserCredentialCache());
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Something went wrong with the CredentialCache / no CC registered.');
                $success = FALSE;
                $ccAdapter->resetCache();
            }
        
        }

        $request = new Sabre\HTTP\Request();
        $redirectUrl = str_replace('index.php', '', $request->getAbsoluteUri());

        // authentication failed
        if ($success !== TRUE) {
            $_SESSION = array();
            Tinebase_Session::destroyAndRemoveCookie();
            
            // redirect back to loginurl if needed
            $redirectUrl = Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL, $redirectUrl);
        }

        // load the client with GET
        header('Location: ' . $redirectUrl);
    }

    /**
     * put jsonKey into separate cookie
     *
     * this is needed if login is not done by the client itself
     */
    protected function _setJsonKeyCookie()
    {
        // SSO Login
        $cookie_params = session_get_cookie_params();

        // don't issue errors in unit tests
        @setcookie(
            'TINE20JSONKEY',
            Tinebase_Core::get('jsonKey'),
            0,
            $cookie_params['path'],
            $cookie_params['domain'],
            $cookie_params['secure']
        );
    }

    /**
     * display Tine 2.0 main screen
     */
    public function mainScreen()
    {
        $locale = Tinebase_Core::getLocale();

        $jsFiles = ['Tinebase/js/fatClient.js'];
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all";

        $customJSFiles = Tinebase_Config::getInstance()->get(Tinebase_Config::FAT_CLIENT_CUSTOM_JS);
        if (! empty($customJSFiles)) {
            $jsFiles[] = "index.php?method=Tinebase.getCustomJsFiles";
        }

        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, 'Tinebase/views/FATClient.html.twig');
    }

    /**
     * returns javascript of translations for the currently configured locale
     *
     * @param  string $locale
     * @param  string $app
     * @return string (javascript)
     */
    public function getJsTranslations($locale = null, $app = 'all')
    {
        if (! in_array(TINE20_BUILDTYPE, array('DEBUG', 'RELEASE'))) {
            $translations = Tinebase_Translation::getJsTranslations($locale, $app);
            header('Content-Type: application/javascript');
            die($translations);
        }

        // production
        $filesToWatch = $this->_getFilesToWatch('lang', array($app));
        $this->_deliverChangedFiles('lang', $filesToWatch);
    }

    /**
     * check if js files have changed and return all js as one big file or return "HTTP/1.0 304 Not Modified" if files don't have changed
     * 
     * @param string $_fileType
     * @param array $filesToWatch
     * @throws Tinebase_Exception
     */
    protected function _deliverChangedFiles($_fileType, $filesToWatch=null)
    {
        // close session to allow other requests
        Tinebase_Session::writeClose(true);

        $filesToWatch = $filesToWatch ? $filesToWatch : $this->_getFilesToWatch($_fileType);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__
            . ' (' . __LINE__ .') Got files to watch: ' . print_r($filesToWatch, true));

        // cache for one day
        $maxAge = 86400;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        
        // remove Pragma header from session
        header_remove('Pragma');

        $clientETag = isset($_SERVER['If_None_Match'])
            ? $_SERVER['If_None_Match']
            : (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '');

        if (preg_match('/[a-f0-9]{40}/', $clientETag, $matches)) {
            $clientETag = $matches[0];
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__
            . ' (' . __LINE__ .') $clientETag: ' . $clientETag);

        $serverETag = Tinebase_Frontend_Http_SinglePageApplication::getAssetHash();

        if ($clientETag == $serverETag) {
            header("HTTP/1.0 304 Not Modified");
        } else {
            header('Content-Type: application/javascript');
            header('Etag: "' . $serverETag . '"');

            // send files to client
            foreach ($filesToWatch as $file) {
                if (file_exists($file)) {
                    readfile($file);
                } else {
                    if (preg_match('/^Tinebase/', $file)) {
                        // this is critical!
                        throw new Tinebase_Exception('client file does not exist: ' . $file);
                    } else if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(
                            __CLASS__ . '::' . __METHOD__
                            . ' (' . __LINE__ .') File ' . $file . ' does not exist');
                    }
                }
            }
            if ($_fileType != 'lang') {
                // adds new server version etag for client version check
                echo "Tine = Tine || {}; Tine.clientVersion = Tine.clientVersion || {};";
                echo "Tine.clientVersion.assetHash = '$serverETag';";
            }
        }
    }

    /**
     * @param string $_fileType
     * @param array  $apps
     * @return array
     * @throws Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getFilesToWatch($_fileType, $apps = array())
    {
        $requiredApplications = array('Setup', 'Tinebase', 'Admin', 'Addressbook');
        if (! Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            $orderedApplications = $requiredApplications;
        } else {
            $installedApplications = Tinebase_Application::getInstance()->getApplications(null, /* sort = */ 'order')->name;
            $orderedApplications = array_merge($requiredApplications, array_diff($installedApplications, $requiredApplications));
        }

        $filesToWatch = array();

        foreach ($orderedApplications as $application) {
            if (! empty($apps) && $apps[0] != 'all' && ! in_array($application, $apps)) continue;
            switch($_fileType) {
                case 'js':
                    $filesToWatch[] = "{$application}/js/{$application}";
                    break;
                case 'lang':
                    $fileName = "{$application}/js/{$application}-lang-" . Tinebase_Core::getLocale() . (TINE20_BUILDTYPE == 'DEBUG' ? '-debug' : null) . '.js';
                    $lang = Tinebase_Core::getLocale();
                    $customPath = Tinebase_Config::getInstance()->translations;
                    $basePath = is_readable("$customPath/$lang/$fileName") ?  "$customPath/$lang" : '.';

                    $langFile = "{$basePath}/{$application}/js/{$application}-lang-" . Tinebase_Core::getLocale() . (TINE20_BUILDTYPE == 'DEBUG' ? '-debug' : null) . '.js';
                    $filesToWatch[] = $langFile;
                    break;
                default:
                    throw new Exception('no such fileType');
                    break;
            }
        }

        return $filesToWatch;
    }

    /**
     * dev mode custom js delivery
     */
    public function getCustomJsFiles()
    {
        try {
            $customJSFiles = Tinebase_Config::getInstance()->get(Tinebase_Config::FAT_CLIENT_CUSTOM_JS);
            if (! empty($customJSFiles)) {
                $this->_deliverChangedFiles('js', $customJSFiles);
            }
        } catch (Exception $exception) {
            Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . " can't deliver custom js: \n" . $exception);

        }
    }

    /**
     * return last modified timestamp formated in gmt
     * 
     * @param  array  $_files
     * @return array
     */
    protected function _getLastModified(array $_files)
    {
        $timeStamp = null;
        
        foreach ($_files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $timeStamp) {
                $timeStamp = $mtime;
            }
        }

        return gmdate("D, d M Y H:i:s", $timeStamp) . " GMT";
    }

    /**
     * receives file uploads and stores it in the file_uploads db
     * 
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_NotFound
     */
    public function uploadTempFile()
    {
        $this->checkAuth();
        $this->_uploadTempFile();
    }
    
    /**
     * downloads an image/thumbnail at a given size
     *
     * @param unknown_type $application
     * @param string $id
     * @param string $location
     * @param int $width
     * @param int $height
     * @param int $ratiomode
     */
    public function getImage($application, $id, $location, $width, $height, $ratiomode)
    {
        $this->checkAuth();

        // close session to allow other requests
        Tinebase_Session::writeClose(true);
        
        $clientETag      = null;
        $ifModifiedSince = null;
        
        if (isset($_SERVER['If_None_Match'])) {
            $clientETag     = trim($_SERVER['If_None_Match'], '"');
            $ifModifiedSince = trim($_SERVER['If_Modified_Since'], '"');
        } elseif (isset($_SERVER['HTTP_IF_NONE_MATCH']) && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientETag     = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            $ifModifiedSince = trim($_SERVER['HTTP_IF_MODIFIED_SINCE'], '"');
        }

        try {
            $image = Tinebase_Controller::getInstance()->getImage($application, $id, $location);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            $this->_handleFailure(404);
        }

        $serverETag = sha1($image->blob . $width . $height . $ratiomode);
        
        // cache for 3600 seconds
        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        
        // overwrite Pragma header from session
        header("Pragma: cache");
        
        // if the cache id is still valid
        if ($clientETag == $serverETag) {
            header("Last-Modified: " . $ifModifiedSince);
            header("HTTP/1.0 304 Not Modified");
            header('Content-Length: 0');
        } else {
            if ($width != -1 && $height != -1) {
                Tinebase_ImageHelper::resize($image, $width, $height, $ratiomode);
            }

            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header('Content-Type: '. $image->mime);
            header('Etag: "' . $serverETag . '"');
            
            flush();
            
            die($image->blob);
        }
    }
    
    /**
     * crops a image identified by an imgageURL and returns a new tempFileImage
     * 
     * @param  string $imageurl imageURL of the image to be croped
     * @param  int    $left     left position of crop window
     * @param  int    $top      top  position of crop window
     * @param  int    $widht    widht  of crop window
     * @param  int    $height   heidht of crop window
     * @return string imageURL of new temp image
     * 
     */
    public function cropImage($imageurl, $left, $top, $widht, $height)
    {
        $this->checkAuth();
        
        $image = Tinebase_Model_Image::getImageFromImageURL($imageurl);
        Tinebase_ImageHelper::crop($image, $left, $top, $widht, $height);
        
    }
    
    /**
     * download file attachment
     * 
     * @param string $nodeId
     * @param string $recordId
     * @param string $modelName
     */
    public function downloadRecordAttachment($nodeId, $recordId, $modelName)
    {
        $this->checkAuth();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Downloading attachment of ' . $modelName . ' record with id ' . $recordId);
        
        $recordController = Tinebase_Core::getApplicationInstance($modelName);
        $record = $recordController->get($recordId);
        
        $node = Tinebase_FileSystem::getInstance()->get($nodeId);
        $node->grants = null;
        $path = Tinebase_Model_Tree_Node_Path::STREAMWRAPPERPREFIX
            . Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($record)
            . '/' . $node->name;
        
        $this->_downloadFileNode($node, $path, /* revision */ null, /* $ignoreAcl */ true);
        exit;
    }

    /**
     * Download temp file to review
     *
     * @param $tmpfileId
     */
    public function downloadTempfile($tmpfileId)
    {
        $this->checkAuth();

        $tmpFile = Tinebase_TempFile::getInstance()->getTempFile($tmpfileId);

        // some grids can house tempfiles and filemanager nodes, therefor first try tmpfile and if no tmpfile try filemanager
        if (!$tmpFile && Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            $filemanagerNodeController = Filemanager_Controller_Node::getInstance();
            $file = $filemanagerNodeController->get($tmpfileId);

            $filemanagerHttpFrontend = new Filemanager_Frontend_Http();
            $filemanagerHttpFrontend->downloadFile($file->path, null);
        }

        $this->_downloadTempFile($tmpFile, $tmpFile->path);
        exit;
    }

    public function getPostalXWindow()
    {
        $context = [
            'path' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
        ];
        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML(
           'Tinebase/js/postal-xwindow-client.js',
            'Tinebase/views/XWindowClient.html.twig',
            $context
        );
    }

    /**
     * download file
     *
     * @param string $_path
     * @param string $_appId
     * @param string $_type
     * @param int $_num
     * @param string $_revision
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function downloadPreview($_path, $_appId, $_type, $_num = 0, $_revision = null)
    {
        $this->checkAuth();
        
        $_revision = $_revision ?: null;

        if ($_path) {
            $path = ltrim($_path, '/');

            if (strpos($path, 'records/') === 0) {
                $pathParts = explode('/', $path);
                $controller = Tinebase_Core::getApplicationInstance($pathParts[1]);

                // ACL Check
                $controller->get($pathParts[2]);

                //$pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath();
                $node = Tinebase_FileSystem::getInstance()->stat('/' . $_appId . '/folders/' . $path, $_revision);
            } else {
                $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath('/' . $_appId . '/folders/' . $path);
                $node = Filemanager_Controller_Node::getInstance()->getFileNode($pathRecord, $_revision);
            }
        } else {
            throw new Tinebase_Exception_InvalidArgument('A path is needed to download a preview file.');
        }

        $this->_downloadPreview($node, $_type, $_num);

        exit;
    }

    /**
     * openIDCLogin
     *
     * @return boolean
     */
    public function openIDCLogin()
    {
        $oidc = Tinebase_Auth_Factory::factory('OpenIdConnect');
        // request gets redirected to login page on success
        return $oidc->providerAuthRequest();
    }
}
