<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * HTTP interface to Tine
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
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
        
        return $smdArray;
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
        
        echo '
            <?xml version="1.0"?>
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
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " display openId trust screen");
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
            Tinebase_Core::getUser();
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
        // redirect to REDIRECTURL if set
        $redirectUrl = Tinebase_Config::getInstance()->getConfig(Tinebase_Config::REDIRECTURL, NULL, '')->value;

        if ($redirectUrl !== '' && Tinebase_Config::getInstance()->getConfig(Tinebase_Config::REDIRECTALWAYS, NULL, FALSE)->value) {
            header('Location: ' . $redirectUrl);
            return;
        }
        
        // check if setup/update required
        $setupController = Setup_Controller::getInstance();
        $applications = Tinebase_Application::getInstance()->getApplications();
        foreach ($applications as $application) {
            if ($application->status == 'enabled' && $setupController->updateNeeded($application)) {
                $this->setupRequired();
            }
        }
        
        $this->_renderMainScreen();
        
        /**
         * old code used to display user registration
         * @todo must be reworked
         * 
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');

        header('Content-Type: text/html; charset=utf-8');
        
        // check if registration is active
        if(isset(Tinebase_Core::getConfig()->login)) {
            $registrationConfig = Tinebase_Core::getConfig()->registration;
            $view->userRegistration = (isset($registrationConfig->active)) ? $registrationConfig->active : '';
        } else {
            $view->userRegistration = 0;
        }        
        
        echo $view->render('jsclient.php');
        */
    }
    
    /**
     * renders the main screen
     * 
     * @return void
     */
    protected function _renderMainScreen()
    {
        $view = new Zend_View();
        $baseDir = dirname(dirname(dirname(__FILE__)));
        $view->setScriptPath("$baseDir/Tinebase/views");
        
        $requiredApplications = array('Tinebase', 'Admin', 'Addressbook');
        $enabledApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->name;
        $orderedApplications = array_merge($requiredApplications, array_diff($enabledApplications, $requiredApplications));
        
        require_once 'jsb2tk/jsb2tk.php';
        $view->jsb2tk = new jsb2tk(array(
            'deploymode'    => jsb2tk::DEPLOYMODE_STATIC,
            'includemode'   => jsb2tk::INCLUDEMODE_INDIVIDUAL,
            'appendctime'   => TRUE,
            'htmlindention' => "    ",
        ));
        
        
        foreach($orderedApplications as $appName) {
            $view->jsb2tk->register("$baseDir/$appName/$appName.jsb2", $appName);
        }
        
        $view->registryData = array();
        
        $this->_setMainscreenHeaders();
        
        echo $view->render('jsclient.php');
    }
    
    /**
     * set headers for mainscreen
     * 
     * @todo think about CSP: is only supported by FF atm, which options/exceptions should we choose?
     * @todo allow to configure security headers?
     * @todo add violation report for CSP? @see https://developer.mozilla.org/en/Security/CSP/Using_CSP_violation_reports
     */
    protected function _setMainscreenHeaders()
    {
        header('Content-Type: text/html; charset=utf-8');
        
        // set x-frame header against clickjacking
        // @see https://developer.mozilla.org/en/the_x-frame-options_response_header
        header('X-Frame-Options: SAMEORIGIN');
        
        // set X-Content-Security-Policy header against clickjacking and XSS
        // @see https://developer.mozilla.org/en/Security/CSP/CSP_policy_directives
        //header("X-Content-Security-Policy: allow 'self' https://*.officespot20.com;");
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
     * renders the tine main screen if authentication is successfull
     * otherwise redirects back to login url 
     */
    public function loginFromPost($username, $password)
    {
        if (!empty($username)) {
            # removed this line on 09-06-2010 Lars
            #Tinebase_Config::getInstance()->getConfig(Tinebase_Config::USERBACKEND, null, $_SERVER["HTTP_REFERER"])->value;
            
            // try to login user
            $success = (Tinebase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR'], 'TineHttpPost') === TRUE); 
        } else {
            $success = FALSE;
        }
        
        if ($success === TRUE) {
            $ccAdapter = Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter();
            if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
                $ccAdapter->setCache(Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE));
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Something went wrong with the CredentialCache / no CC registered.');
                $success = FALSE;
                $ccAdapter->resetCache();
            }
        
        }

        // authentication failed
        // redirect back to loginurl
        if ($success !== TRUE) {
            $defaultUrl = (array_key_exists('HTTP_REFERER', $_SERVER)) ? $_SERVER['HTTP_REFERER'] : '';
            $redirectUrl = Tinebase_Config::getInstance()->getConfig(Tinebase_Config::REDIRECTURL, NULL, $defaultUrl)->value;
            if (! empty($redirectUrl)) {
                header('Location: ' . $redirectUrl);
            }
            return;
        }

        $this->_renderMainScreen();
    }
    
    /**
	 * checks authentication and display Tine 2.0 main screen 
	 */
    public function mainScreen()
    {
        $this->checkAuth();
        
        $this->_renderMainScreen();
    }
    
    /**
     * handle session exception for http requests
     * 
     * we force the client to delete session cookie, but we don't destroy
     * the session on server side. This way we prevent session DOS from thrid party
     */
    public function sessionException()
    {
        Zend_Session::expireSessionCookie();
        echo "
            <script type='text/javascript'>
                window.location.href = window.location.href;
            </script>
        ";
        /*
        ob_start();
        $html = $this->login();
        $html = ob_get_clean();
        
        $script = "
            <script type='text/javascript'>
                exception = {code: 401};
                Ext.onReady(function() {
                    Ext.MessageBox.show({
                        title: _('Authorisation Required'), 
                        msg: _('Your session is not valid. You need to login again.'),
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.WARNING
                    });
                });
            </script>";
        
        echo preg_replace('/<\/head.*>/', $script . '</head>', $html);
        */
    }
    
    /**
     * generic http exception occurred
     */
    public function exception()
    {
        ob_start();
        $this->_renderMainScreen();
        $html = ob_get_clean();
        
        $script = "
        <script type='text/javascript'>
            exception = {code: 400};
            Ext.onReady(function() {
                Ext.MessageBox.show({
                    title: _('Abnormal End'), 
                    msg: _('An error occurred, the program ended abnormal.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.WARNING
                });
            });
        </script>";
        
        echo preg_replace('/<\/head.*>/', $script . '</head>', $html);
    }
    
    /**
     * returns javascript of translations for the currently configured locale
     * 
     * Note: This function is only used in development mode. In debug/release mode
     * we can include the static build files in the view. With this we avoid the 
     * need to start a php process and stream static js files through it.
     * 
     * @return javascript
     *
     */
    public function getJsTranslations()
    {
        if (! in_array(TINE20_BUILDTYPE, array('DEBUG', 'RELEASE'))) {
            $locale = Tinebase_Core::get('locale');
            $translations = Tinebase_Translation::getJsTranslations($locale);
            header('Content-Type: application/javascript');
            die($translations);
        }
        
        $this->_deliverChangedFiles('lang');
        
        die();
    }
    
    /**
     * return javascript files if changed
     * 
     */
    public function getJsFiles()
    {
        $this->_deliverChangedFiles('js');
        
        die();
    }
      
    /**
     * return hash for js files
     * hash changes if files get changed or list of filesnames changes
     * 
     */
    public function getJsCssHash()
    {
        // hash for js files
        $filesToWatch = $this->_getFilesToWatch('js');
        
        $serverETag = hash('sha1', implode('', $filesToWatch));
        $lastModified = $this->_getLastModified($filesToWatch);
        
        $hash = hash('sha1', $serverETag . $lastModified);
        
        // hash for css files + hash of js files
        $filesToWatch = $this->_getFilesToWatch('css');
        
        $serverETag = hash('sha1', implode('', $filesToWatch));
        $lastModified = $this->_getLastModified($filesToWatch);
        
        $hash = hash('sha1', $hash . $serverETag . $lastModified);
        
        return $hash;
    }
    
    /**
     * return css files if changed
     * 
     */
    public function getCssFiles()
    {
        $this->_deliverChangedFiles('css');
        
        die();
    }
    
    /**
     * check if js or css files have changed and return all js/css as one big file or return "HTTP/1.0 304 Not Modified" if files don't have changed
     * 
     * @param string $_fileType
     */
    protected function _deliverChangedFiles($_fileType)
    {
	    // close session to allow other requests
        Zend_Session::writeClose(true);
        
        $cacheId         = null;
        $clientETag      = null;
        $ifModifiedSince = null;
        $filesToWatch    = array();
        
        if (isset($_SERVER['If_None_Match'])) {
            $clientETag     = trim($_SERVER['If_None_Match'], '"');
            $ifModifiedSince = trim($_SERVER['If_Modified_Since'], '"');
        } elseif (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $clientETag     = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            $ifModifiedSince = trim($_SERVER['HTTP_IF_MODIFIED_SINCE'], '"'); 
        }
        
        $filesToWatch = $this->_getFilesToWatch($_fileType);
        
        $serverETag = hash('sha1', implode('', $filesToWatch));
        
        $cache = new Zend_Cache_Frontend_File(array(
            'master_files' => $filesToWatch
        ));
        $cache->setBackend(Tinebase_Core::get(Tinebase_Core::CACHE)->getBackend());
        
        if ($clientETag && $ifModifiedSince) {
            $cacheId = __CLASS__ . "_". __FUNCTION__ . hash('sha1', $clientETag . $ifModifiedSince);
        }
        
        // cache for 60 seconds
        $maxAge = 60;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        
        // overwrite Pragma header from session
        header("Pragma: cache");
        
        // if the cache id is still valid, the files don't have changed on disk
        if ($clientETag == $serverETag && $cache->test($cacheId)) {
            header("Last-Modified: " . $ifModifiedSince);
            header("HTTP/1.0 304 Not Modified");
            header('Content-Length: 0');
        } else {
            // recalculate etag
            $lastModified = $this->_getLastModified($filesToWatch);
            
            $cacheId = __CLASS__ . "_". __FUNCTION__ . hash('sha1', $serverETag . $lastModified);
            
            // do we need to update the cache? maybe the client did not send an etag
            if (!$cache->test($cacheId)) {
                $cache->save(TINE20_BUILDTYPE, $cacheId, array(), null);
            }
            
            header("Last-Modified: " . $lastModified);
            header('Content-Type: ' . ($_fileType == 'css' ? 'text/css' : 'application/javascript'));
            header('Etag: "' . $serverETag . '"');
            
            flush();
            
            // send files to client
            foreach ($filesToWatch as $file) {
                readfile($file);
            }
        }
    }
    
    /**
     * @param string $_fileType
     * @return array
     */
    protected function _getFilesToWatch($_fileType)
    {
        $requiredApplications = array('Tinebase', 'Admin', 'Addressbook');
        $enabledApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->name;
        $orderedApplications = array_merge($requiredApplications, array_diff($enabledApplications, $requiredApplications));
        
        foreach ($orderedApplications as $application) {
            switch($_fileType) {
                case 'css':
                    $filesToWatch[] = "{$application}/css/{$application}-FAT.css.inc";
                    break;
                case 'js':
                    $filesToWatch[] = "{$application}/js/{$application}-FAT"  . (TINE20_BUILDTYPE == 'DEBUG' ? '-debug' : null) . '.js.inc';
                    break;
                case 'lang':
                    $fileName = "{$application}/js/{$application}-lang-" . Tinebase_Core::getLocale() . (TINE20_BUILDTYPE == 'DEBUG' ? '-debug' : null) . '.js';
                    $lang = Tinebase_Core::getLocale();
                    $customPath = Tinebase_Config::getInstance()->translations;
                    $basePath = is_readable("$customPath/$lang/$fileName") ?  "$customPath/$lang" : '.';
                    
                    $filesToWatch[] = "{$basePath}/{$application}/js/{$application}-lang-" . Tinebase_Core::getLocale() . (TINE20_BUILDTYPE == 'DEBUG' ? '-debug' : null) . '.js';
                    break;
                default:
                    throw new Exception('no such fileType');
                    break;
            }
        }
        
        return $filesToWatch;
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
	 * activate user account
	 *
	 * @param 	string $id
	 * 
	 */
	public function activateUser( $id ) 
	{
		// update registration table and get username / account values
		$account = Tinebase_User_Registration::getInstance()->activateUser( $id );

		if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' activated account for ' . $account['accountLoginName']);
		
		$view = new Zend_View();
        $view->title="Tine 2.0 User Activation";
        $view->username = $account['accountLoginName'];
        $view->loginUrl = $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

        $view->setScriptPath('Tinebase/views');
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('activate.php');
	
	}
	
	/**
	 * show captcha
	 *
	 * @todo	add to user registration process
	 */
	public function showCaptcha () 
	{	
		$captcha = Tinebase_User_Registration::getInstance()->generateCaptcha();
		
        //Tell the browser what kind of file is come in
        header("Content-Type: image/jpeg");

        //Output the newly created image in jpeg format
        ImageJpeg($captcha);		
		
	}

	/**
	 * receives file uploads and stores it in the file_uploads db
	 * 
	 * @throws Tinebase_Exception_UnexpectedValue
	 * @throws Tinebase_Exception_NotFound
	 */
	public function uploadTempFile()
	{
	    try {
    	    $this->checkAuth();
    	    
    	    // close session to allow other requests
            Zend_Session::writeClose(true);
        
    	    $tempFile = Tinebase_TempFile::getInstance()->uploadTempFile();
    	    
    	    die(Zend_Json::encode(array(
    	       'status'   => 'success',
    	       'tempFile' => $tempFile->toArray(),
    	    )));
	    } catch (Tinebase_Exception $exception) {
	        Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . " File upload could not be done, due to the following exception: \n" . $exception);
	        
	        if (! headers_sent()) {
	           header("HTTP/1.0 500 Internal Server Error");
	        }
	        die(Zend_Json::encode(array(
               'status'   => 'failed',
            )));
	    }
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
        Zend_Session::writeClose(true);
        
        $clientETag      = null;
        $ifModifiedSince = null;
        
        if (isset($_SERVER['If_None_Match'])) {
            $clientETag     = trim($_SERVER['If_None_Match'], '"');
            $ifModifiedSince = trim($_SERVER['If_Modified_Since'], '"');
        } elseif (isset($_SERVER['HTTP_IF_NONE_MATCH']) && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientETag     = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            $ifModifiedSince = trim($_SERVER['HTTP_IF_MODIFIED_SINCE'], '"'); 
        }
	    
	    if ($application == 'Tinebase' && $location == 'tempFile') {
	        
	        $tempFile = Tinebase_TempFile::getInstance()->getTempFile($id);

            $imgInfo = Tinebase_ImageHelper::getImageInfoFromBlob(file_get_contents($tempFile->path));
            $image = new Tinebase_Model_Image($imgInfo + array(
                'application' => $application,
                'id'          => $id,
                'location'    => $location
            ));
    	} else {
    	    $image = Tinebase_Controller::getInstance()->getImage($application, $id, $location);
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
            #$cache = Tinebase_Core::getCache();
            
            #if ($cache->test($serverETag) === true) {
            #    $image = $cache->load($serverETag);
            #} else {
            	if ($width != -1 && $height != -1) {
            	    Tinebase_ImageHelper::resize($image, $width, $height, $ratiomode);
            	}
            #    $cache->save($image, $serverETag);
            #}
    	
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
	
}
