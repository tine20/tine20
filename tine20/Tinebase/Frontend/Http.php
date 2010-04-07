<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
            null,
            new Tinebase_OpenId_Provider_Storage
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
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " display openId trust screen");
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
     * @todo $this->sessionTimedOut(); does not exist anymore
     */
    protected function checkAuth()
    {
        try {
            Tinebase_Core::getUser();
        } catch (Exception $e) {
            $this->sessionTimedOut();
            exit;
        }
    }
    
    /**
     * Returns all JS files which must be included for Tinebase
     *
     * @todo refactor js stuff so that all js files could be included
     * before registry gets included!
     * 
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        //'extjs/build/locale/ext-lang-'.$locale->getLanguage().'.js';
        return array(
        // base framework fixes
            'Tinebase/js/extFixes.js',
        // gears
            'Tinebase/js/gears_init.js',
        // generic init to be included before parsing of the tine code
            'Tinebase/js/extInit.js',
        // Locale support
            'Tinebase/js/Locale.js',
            'Tinebase/js/Locale/Gettext.js',
        // mappanel / geoext
            'library/OpenLayers/OpenLayers.js', 
            'library/GeoExt/script/GeoExt.js', 
            'Tinebase/js/widgets/MapPanel.js',
        // Ext user extensions
            'Tinebase/js/ux/ConnectionStatus.js',
            'Tinebase/js/ux/Direct/ZendFrameworkProvider.js',
            'Tinebase/js/ux/DatePickerWeekPlugin.js',
            'Tinebase/js/ux/ButtonLockedToggle.js',
            'Tinebase/js/ux/Percentage.js',
            'Tinebase/js/ux/PopupWindow.js',
            'Tinebase/js/ux/PopupWindowManager.js',
            'Tinebase/js/ux/Notification.js',
            'Tinebase/js/ux/WindowFactory.js',
            'Tinebase/js/ux/SliderTip.js',
            'Tinebase/js/ux/Wizard.js',
            'Tinebase/js/ux/SearchField.js',
            'Tinebase/js/ux/BrowseButton.js',
            'Tinebase/js/ux/grid/CheckColumn.js',
            'Tinebase/js/ux/grid/QuickaddGridPanel.js',
            'Tinebase/js/ux/grid/RowExpander.js',
            'Tinebase/js/ux/grid/PagingToolbar.js',
            'Tinebase/js/ux/grid/GridViewMenuPlugin.js',
            'Tinebase/js/ux/file/Uploader.js',
            'Tinebase/js/ux/file/BrowsePlugin.js',
            'Tinebase/js/ux/file/Download.js',
            'Tinebase/js/ux/form/ColorField.js',
            'Tinebase/js/ux/form/IconTextField.js',
            'Tinebase/js/ux/form/MirrorTextField.js',
            'Tinebase/js/ux/form/ColumnFormPanel.js',
            'Tinebase/js/ux/form/ExpandFieldSet.js',
            'Tinebase/js/ux/form/LayerCombo.js',
            'Tinebase/js/ux/form/ClearableComboBox.js',
            'Tinebase/js/ux/form/ClearableTextField.js',
            'Tinebase/js/ux/form/RecordsComboBox.js',
            'Tinebase/js/ux/form/DateTimeField.js',
            'Tinebase/js/ux/form/ClearableDateField.js',
            'Tinebase/js/ux/form/ImageField.js',
            'Tinebase/js/ux/form/ImageCropper.js',
            'Tinebase/js/ux/form/Spinner.js',
            'Tinebase/js/ux/form/SpinnerStrategy.js',
            'Tinebase/js/ux/form/LockCombo.js',
            'Tinebase/js/ux/form/LockTextfield.js',
            'Tinebase/js/ux/form/HtmlEditor.js',
            'Tinebase/js/ux/layout/HorizontalFitLayout.js',
            'Tinebase/js/ux/layout/CenterLayout.js',
            'Tinebase/js/ux/layout/RowLayout.js',
            'Tinebase/js/ux/GMapPanel.js',
            'Tinebase/js/ux/DatepickerRange.js',
            'Tinebase/js/ux/tree/CheckboxSelectionModel.js',
            'Tinebase/js/ux/display/DisplayPanel.js',
            'Tinebase/js/ux/display/DisplayField.js',
            'Tinebase/js/ux/layout/Display.js',
            'Tinebase/js/ux/MessageBox.js',
        // Tine 2.0 specific widgets
            'Tinebase/js/widgets/LangChooser.js',
            'Tinebase/js/widgets/ActionUpdater.js',
            'Tinebase/js/widgets/EditRecord.js',
            'Tinebase/js/widgets/Priority.js',
            'Tinebase/js/widgets/VersionCheck.js',
            'Tinebase/js/widgets/dialog/AlarmPanel.js',
            'Tinebase/js/widgets/dialog/EditDialog.js',
            'Tinebase/js/widgets/dialog/CredentialsDialog.js',
            'Tinebase/js/widgets/dialog/PreferencesDialog.js',
            'Tinebase/js/widgets/dialog/PreferencesTreePanel.js',
            'Tinebase/js/widgets/dialog/PreferencesPanel.js',
            'Tinebase/js/widgets/dialog/ImportDialog.js',
            'Tinebase/js/widgets/dialog/LinkPanel.js',
            'Tinebase/js/widgets/dialog/MultiOptionsDialog.js',
			'Tinebase/js/widgets/customfields/CustomfieldSearchCombo.js',
            'Tinebase/js/widgets/customfields/CustomfieldsPanel.js',
			'Tinebase/js/widgets/customfields/CustomfieldsCombo.js',
            'Tinebase/js/widgets/tree/Loader.js',
            'Tinebase/js/widgets/tree/ContextMenu.js',
            'Tinebase/js/widgets/grid/DetailsPanel.js',
            'Tinebase/js/widgets/grid/FilterModel.js',
            'Tinebase/js/widgets/grid/FilterPlugin.js',
            'Tinebase/js/widgets/grid/FilterButton.js',
            'Tinebase/js/widgets/grid/ExportButton.js',
            'Tinebase/js/widgets/grid/FilterToolbar.js',
            'Tinebase/js/widgets/grid/FilterToolbarQuickFilterPlugin.js',
            'Tinebase/js/widgets/grid/FilterSelectionModel.js',
            'Tinebase/js/widgets/grid/ForeignRecordFilter.js',
            'Tinebase/js/widgets/grid/PersistentFilterPicker.js',
            'Tinebase/js/widgets/grid/QuickaddGridPanel.js',
            'Tinebase/js/widgets/grid/FileUploadGrid.js',
            'Tinebase/js/widgets/grid/PickerGridPanel.js',
            'Tinebase/js/widgets/account/PickerGridPanel.js',
            'Tinebase/js/widgets/container/ContainerSelect.js',
            'Tinebase/js/widgets/container/GrantsDialog.js',
            'Tinebase/js/widgets/container/ContainerTree.js',
            'Tinebase/js/widgets/container/FilterModel.js',
            'Tinebase/js/widgets/tags/TagsPanel.js',
            'Tinebase/js/widgets/tags/TagCombo.js',
            'Tinebase/js/widgets/tags/TagFilter.js',
            'Tinebase/js/widgets/tags/TagsMassAttachAction.js',
            'Tinebase/js/widgets/app/MainScreen.js',
            'Tinebase/js/widgets/app/GridPanel.js',
            'Tinebase/js/widgets/CountryCombo.js',
            'Tinebase/js/widgets/ActivitiesPanel.js',        
            'Tinebase/js/widgets/form/RecordPickerComboBox.js',
            'Tinebase/js/widgets/form/ConfigPanel.js',
        // yui stuff
            //'../yui/build/dragdrop/dragdrop-min.js',
            //'../yui/build/resize/resize-beta-min.js',
            //'../yui/build/imagecropper/imagecropper-beta-min.js',
        // Tinebase
        // NOTE: All the data stuff is going to and extra worker build
            //'Tinebase/js/data/sync/Ping.js',
            //'Tinebase/js/data/schemaProc/sqlGenerator.js',
            //'Tinebase/js/data/schemaProc/xmlReader.js',
            'Tinebase/js/data/Record.js',
            'Tinebase/js/data/RecordStore.js',
            'Tinebase/js/data/RecordProxy.js',
            'Tinebase/js/data/AbstractBackend.js',
        //'Tinebase/js/data/MemoryBackend.js',
            'Tinebase/js/StateProvider.js',
            'Tinebase/js/ExceptionHandler.js',
            'Tinebase/js/ExceptionDialog.js',
            'Tinebase/js/Container.js',
            'Tinebase/js/Models.js',
            'Tinebase/js/Application.js',
            'Tinebase/js/AppManager.js',
            'Tinebase/js/AppPile.js',
            'Tinebase/js/AppTabsPanel.js',
            'Tinebase/js/MainMenu.js',
            'Tinebase/js/MainScreen.js',
            'Tinebase/js/LoginPanel.js',
            'Tinebase/js/common.js',
            'Tinebase/js/tineInit.js',
        );
    }
    
	/**
	 * returns the css include files for Tinebase 
	 *
	 * @return array Array of filenames
	 *  
	 */
    public function getCssFilesToInclude()
    {
        return array(
    	   'Tinebase/css/ExtFixes.css',
    	   'Tinebase/css/Tinebase.css',
    	   'Tinebase/css/SmallForms.css',
    	   'Tinebase/css/ux/ConnectionStatus.css',
    	   'Tinebase/css/ux/Wizard.css',
    	   'Tinebase/css/ux/Percentage.css',
    	   'Tinebase/css/ux/DatePickerWeekPlugin.css',
    	   'Tinebase/css/ux/grid/QuickaddGridPanel.css',
    	   'Tinebase/css/ux/grid/IconTextField.css',
    	   'Tinebase/css/ux/grid/PagingToolbar.css',
    	   'Tinebase/css/ux/grid/GridViewMenuPlugin.css',
    	   'Tinebase/css/ux/form/ExpandFieldSet.css',
    	   'Tinebase/css/ux/form/ImageField.css',
    	   'Tinebase/css/ux/form/Spinner.css',
    	   'Tinebase/css/ux/form/HtmlEditor.css',
    	   'Tinebase/css/ux/form/layerCombo.css',
    	   'Tinebase/css/ux/display/DisplayPanel.css',
    	   'Tinebase/css/ux/LockCombo.css',
    	   'Tinebase/css/ux/Menu.css',
    	   'Tinebase/css/ux/MessageBox.css',
    	   'Tinebase/css/widgets/EditRecord.css',
    	   'Tinebase/css/widgets/TagsPanel.css',
    	   'Tinebase/css/widgets/FilterToolbar.css',
    	   'Tinebase/css/widgets/AccountPicker.css',
    	   'Tinebase/css/widgets/PreviewPanel.css',
           'Tinebase/css/widgets/PreferencesPanel.css',
        // geoext
            'library/GeoExt/resources/css/geoext-all.css',
    	// yui stuff
    	   //'../yui/build/assets/skins/sam/resize.css',
    	   //'../yui/build/assets/skins/sam/imagecropper.css',
    	);
    }
    
    /**
     * renders the login dialog
     *
     * @todo perhaps we could add a config option to display the update dialog if it is set
     */
    public function login()
    {
        // redirect to REDIRECTURL if set
        $redirectUrl = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::REDIRECTURL, NULL, '')->value;

        if ($redirectUrl !== '' && Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::REDIRECTALWAYS, NULL, FALSE)->value) {
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
        $view->setScriptPath('Tinebase/views');
        
        $view->registryData = array();

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('jsclient.php');
        
    }
    
    /**
     * renders the setup/update required dialog
     *
     */
    public function setupRequired()
    {
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');

        Tinebase_Core::getLogger()->DEBUG(__CLASS__ . '::' . __METHOD__ . ' (' . __LINE__ .') Update/Setup required!');

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
            // strip of everything after @ from username
            list($username) = explode('@', $username);
            
            // try to login user
            $success = (Tinebase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']) === TRUE); 
        } else {
            $success = FALSE;
        }
        
        if ($success === TRUE) {
            if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
                $cacheId = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE)->getCacheId();
                setcookie('usercredentialcache', base64_encode(Zend_Json::encode($cacheId)));
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Something went wrong with the CredentialCache / no CC registered.');
                $success = FALSE;
                // reset credentials cache
                setcookie('usercredentialcache', '', time() - 3600);
            }
        
        }

        // authentication failed
        // redirect back to loginurl
        if ($success !== TRUE) {
            $redirectUrl = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::REDIRECTURL, NULL, $_SERVER["HTTP_REFERER"])->value;
            header('Location: ' . $redirectUrl);
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
     * hanlde session exception for http requests
     * 
     * we force the client to delete session cookie, but we don't destroy
     * the session on server side. This way we prevent session DOS from thrid party
     *
     *
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
     *
     */
    public function exception()
    {
        ob_start();
        $this->login();
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
        $locale = Tinebase_Core::get('locale');
        $translations = Tinebase_Translation::getJsTranslations($locale);
        header('Content-Type: application/javascript');
        die($translations);
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

		Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' activated account for ' . $account['accountLoginName']);
		
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
        
    	    $tempfileBackend = new Tinebase_TempFile();
    	    $tempFile = $tempfileBackend->uploadTempFile();
    	    
    	    die(Zend_Json::encode(array(
    	       'status'   => 'success',
    	       'tempFile' => $tempFile->toArray(),
    	    )));
	    } catch (Tinebase_Exception $exception) {
	        Tinebase_Core::getLogger()->WARN("File upload could not done, due to the following exception: \n" . $exception);
	        
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
	    
	    if ($application == 'Tinebase' && $location == 'tempFile') {
	        
	        $tempfileBackend = new Tinebase_TempFile();
	        $tempFile = $tempfileBackend->getTempFile($id);

            $imgInfo = Tinebase_ImageHelper::getImageInfoFromBlob(file_get_contents($tempFile->path));
            $image = new Tinebase_Model_Image($imgInfo + array(
                'application' => $application,
                'id'          => $id,
                'location'    => $location
            ));
    	} else {
    	    $image = Tinebase_Controller::getInstance()->getImage($application, $id, $location);
    	}
    	
    	if ($width != -1 && $height != -1) {
    	   Tinebase_ImageHelper::resize($image, $width, $height, $ratiomode);
    	}
    	
    	header('Content-Type: '. $image->mime);
    	die($image->blob);
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
	 * returns an array with all css and js files which needs to be included
	 * all over Tine 2.0
	 * 
	 * NOTE: As the HTML servers have no singletons, we need to create a unique
	 * instance here. This might get relaxed wiht php 5.3 with static functions
	 * 
	 * @param  array apps to exclude
	 * @return array 
	 */
	public static function getAllIncludeFiles(array $_exclude=array())
	{
	    $tine20path = dirname(dirname(dirname(__FILE__)));
	    
	    // Tinebase first
	    $tinebaseHttp = new Tinebase_Frontend_Http();
        $cssFiles = $tinebaseHttp->getCssFilesToInclude();
        $jsFiles  = $tinebaseHttp->getJsFilesToInclude();
        
        $_exclude[]  = '.';
        $_exclude[]  = 'Tinebase';
        $_exclude[]  = 'Setup';
        
        	    
        $d = dir($tine20path);
	    while (false !== ($appName = $d->read())) {
            if ($appName{0} != '.' && is_dir("$tine20path/$appName") && !in_array($appName, $_exclude)) {
                if (file_exists("$tine20path/$appName/Frontend/Http.php")) {
                    
                    $httpClass = $appName . "_Frontend_Http";
                    $instance = new $httpClass();
                    if (method_exists($instance, 'getCssFilesToInclude')) {
                        $cssFiles = array_merge($cssFiles, call_user_func(array($instance, 'getCssFilesToInclude')));
                    }
                    if (method_exists($instance, 'getJsFilesToInclude')) {
                        $jsFiles = array_merge($jsFiles, call_user_func(array($instance, 'getJsFilesToInclude')));
                    }
                }
            }
           
        }
        $d->close();
        
        return array(
            'js'  => array_unique($jsFiles),
            'css' => $cssFiles
        );
	}
}