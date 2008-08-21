<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
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
class Tinebase_Http extends Tinebase_Application_Http_Abstract
{
    /**
     * checes if a user is loged in. If not we redirect to login
     */
    protected function checkAuth()
    {
        try {
            Zend_Registry::get('currentAccount');
        } catch (Exception $e) {
            header("Location: .");
            exit;
        }
    }
    
    /**
     * displays the login dialog
     *
     */
    public function login()
    {
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        // default credentials
        if(isset(Zend_Registry::get('configFile')->login)) {
            $loginConfig = Zend_Registry::get('configFile')->login;
            $defaultUsername = (isset($loginConfig->username)) ? $loginConfig->username : '';
            $defaultPassword = (isset($loginConfig->password)) ? $loginConfig->password : '';
        } else {
            $defaultUsername = '';
            $defaultPassword = '';
        }

        // check if registration is active
        if(isset(Zend_Registry::get('configFile')->login)) {
            $registrationConfig = Zend_Registry::get('configFile')->registration;
            $view->userRegistration = (isset($registrationConfig->active)) ? $registrationConfig->active : '';
        } else {
            $view->userRegistration = 0;
        }
        
        $view->title="Tine 2.0";
        $view->configData = self::getRegistryData();
        $view->jsExecute =  "
           Tine.Login.showLoginDialog('$defaultUsername', '$defaultPassword');
        ";
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
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
            'Tinebase/js/ExtFixes.js',
            // general helpers
            'Tinebase/js/Sprintf.js',
            // Locale support
            'Tinebase/js/Locale.js',
            'Tinebase/js/Locale/Gettext.js',
            // Tinebase
            'Tinebase/js/Tinebase.js',
            'Tinebase/js/Models.js',
            'Tinebase/js/MainScreen.js',
            'Tinebase/js/Login.js',
            'Tinebase/js/ExceptionDialog.js',
            'Tinebase/js/Container.js',
            // Ext user extensions
            'Tinebase/js/ux/ButtonLockedToggle.js',
            'Tinebase/js/ux/Percentage.js',
            'Tinebase/js/ux/PopupWindow.js',
            'Tinebase/js/ux/PopupWindowManager.js',
            'Tinebase/js/ux/Wizard.js',
            'Tinebase/js/ux/SearchField.js',
            'Tinebase/js/ux/grid/CheckColumn.js',
            'Tinebase/js/ux/grid/QuickaddGridPanel.js',
            'Tinebase/js/ux/grid/RowExpander.js',
            'Tinebase/js/ux/file/Uploader.js',
            'Tinebase/js/ux/form/IconTextField.js',
            'Tinebase/js/ux/form/MirrorTextField.js',
            'Tinebase/js/ux/form/ColumnFormPanel.js',
            'Tinebase/js/ux/form/ExpandFieldSet.js',
            'Tinebase/js/ux/form/ClearableComboBox.js',
            'Tinebase/js/ux/form/DateTimeField.js',
            'Tinebase/js/ux/form/ClearableDateField.js',
            'Tinebase/js/ux/form/ImageField.js',
            'Tinebase/js/ux/form/ImageCropper.js',
            'Tinebase/js/ux/form/BrowseButton.js',
            'Tinebase/js/ux/LockCombo.js',
            'Tinebase/js/ux/layout/HorizontalFitLayout.js',
            'Tinebase/js/ux/layout/AppLeftLayout.js',
            'Tinebase/js/ux/GMapPanel.js',
            'Tinebase/js/ux/DatepickerRange.js',
            // Tine 2.0 specific widgets
            'Tinebase/js/widgets/LangChooser.js',
            'Tinebase/js/widgets/ActionUpdater.js',
            'Tinebase/js/widgets/EditRecord.js',
            'Tinebase/js/widgets/Priority.js',
            'Tinebase/js/widgets/AccountpickerPanel.js',
            'Tinebase/js/widgets/account/PickerPanel.js',
            'Tinebase/js/widgets/account/ConfigGrid.js',
            'Tinebase/js/widgets/container/ContainerSelect.js',
            'Tinebase/js/widgets/container/ContainerGrants.js',
            'Tinebase/js/widgets/container/ContainerTree.js',
            'Tinebase/js/widgets/grid/FilterModel.js',
            'Tinebase/js/widgets/grid/FilterToolbar.js',
            'Tinebase/js/widgets/tags/TagsPanel.js',
            'Tinebase/js/widgets/tags/TagCombo.js',
            'Tinebase/js/widgets/tags/TagFilter.js',
            'Tinebase/js/widgets/GroupSelect.js',
            'Tinebase/js/widgets/CountryCombo.js',
            'Tinebase/js/widgets/GridPicker.js',
            'Tinebase/js/widgets/ActivitiesPanel.js',        
            // yui stuff
            //'../yui/build/dragdrop/dragdrop-min.js',
            //'../yui/build/resize/resize-beta-min.js',
            //'../yui/build/imagecropper/imagecropper-beta-min.js',
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
    	   'Tinebase/css/Tinebase.css',
    	   'Tinebase/css/SmallForms.css',
    	   'Tinebase/css/ux/Wizard.css',
    	   'Tinebase/css/ux/Percentage.css',
    	   'Tinebase/css/ux/grid/QuickaddGridPanel.css',
    	   'Tinebase/css/ux/grid/IconTextField.css',
    	   'Tinebase/css/ux/form/ExpandFieldSet.css',                  
    	   'Tinebase/css/ux/LockCombo.css',           
    	   'Tinebase/css/widgets/TagsPanel.css',
    	   'Tinebase/css/widgets/FilterToolbar.css',
    	   // yui stuff
    	   //'../yui/build/assets/skins/sam/resize.css',
    	   //'../yui/build/assets/skins/sam/imagecropper.css',
    	);
    }
    
	/**
	 * renders the tine main screen 
	 */
    public function mainScreen()
    {
        $this->checkAuth();
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $userApplications = Zend_Registry::get('currentAccount')->getApplications();
        $view->initialData = array();
        
        foreach($userApplications as $application) {
            $httpAppName = ucfirst((string) $application) . '_Http';
            if(class_exists($httpAppName)) {
                $application_http = new $httpAppName;
                
                $view->initialData[ucfirst((string) $application)] = $application_http->getInitialMainScreenData();
                $view->initialData[ucfirst((string) $application)]['rights'] = Zend_Registry::get('currentAccount')->getRights((string) $application);
            }
        }
        
        $view->configData = self::getRegistryData();
        $view->configData['userApplications'] = $userApplications->toArray();
        
        $view->title="Tine 2.0";
        $view->jsExecute =  "
            Tine.Tinebase.MainScreen = new Tine.Tinebase.MainScreenClass();
            Tine.Tinebase.MainScreen.render();
        ";
        // temporary tweak to fill generalised popup
        // todo: move js code to ext.ux.popupwindow!
        /*
        if (isset($_GET['isPopup']) && $_GET['isPopup']) {
            $view->isPopup = true;
            $view->jsExecute = "
                var viewport = new Ext.Viewport({
                    layout: opener.Ext.ux.PopupWindowMgr.get(window).layout,
                    items:  opener.Ext.ux.PopupWindowMgr.get(window).items,
                    onRender: function(viewport){
                        //window.setTimeout(function() {opener.Ext.ux.PopupWindowMgr.get(window).fireEvent('render', window);}, 3000);
                        opener.Ext.ux.PopupWindowMgr.get(window).fireEvent('render', window);
                    }
                });
            ";
        }
        */
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    
    /**
     * returns registry data
     * 
     * @return array
     */
    public static function getRegistryData()
    {
        $locale = Zend_Registry::get('locale');
        
        $registryData =  array(
            'timeZone'         => Zend_Registry::get('userTimeZone'),
            'locale'           => array(
                'locale'   => $locale->toString(), 
                'language' => $locale->getLanguageTranslation($locale->getLanguage()),
                'region'   => $locale->getCountryTranslation($locale->getRegion())
        ));

        if (Zend_Registry::isRegistered('currentAccount')) {
            $registryData += array(    
                'currentAccount'   => Zend_Registry::get('currentAccount')->toArray(),
                'accountBackend'   => Tinebase_User::getConfiguredBackend(),
            );
        }
        return $registryData;
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

		Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' activated account for ' . $account['accountLoginName']);
		
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
	 * @todo: move db storage into seperate tmp_file class
	 */
	public function uploadTempFile()
	{
	    $this->checkAuth();
	    
	    $uploadedFile = $_FILES['file'];
	    
	    $path = tempnam(session_save_path(), 'tine_tempfile_');
	    if (!$path) {
	        throw new Exception('Can not upload file, tempnam could not return a valid filename!');
	    }
	    if (! move_uploaded_file($uploadedFile['tmp_name'], $path)) {
	        throw new Exception('No valid upload file found!');
	    }
	    
	    $id = Tinebase_Model_TempFile::generateUID();
	    $tempFile = new Tinebase_Model_TempFile(array(
	       'id'          => $id,
           'session_id'  => session_id(),
           'time'        => Zend_Date::now()->getIso(),
           'path'        => $path,
           'name'        => $uploadedFile['name'],
           'type'        => $uploadedFile['type'],
           'error'       => $uploadedFile['error'],
           'size'        => $uploadedFile['size'],
	    ));
	    
	    $db = Zend_Registry::get('dbAdapter');
	    $db->insert(SQL_TABLE_PREFIX . 'temp_files', $tempFile->toArray());
	    
	    die(Zend_Json::encode(array(
	       'status'   => 'success',
	       'tempFile' => $tempFile->toArray(),
	    )));
	}
	
	/**
     * downloads an image/thumbnail at a given size
     * 
     * @todo move db stuff into seperate class
     * @param  string $id
     */
	public function getImage($application, $id, $location, $width, $height, $ratiomode)
	{
	    $this->checkAuth();
	    
	    if ($application == 'Tinebase' && $location=='tempFile') {
	        $db = Zend_Registry::get('dbAdapter');
            $select = $db->select()
               ->from(SQL_TABLE_PREFIX . 'temp_files')
               ->where($db->quoteInto('id = ?', $id))
               ->where($db->quoteInto('session_id = ?', session_id()));
            $tempFile = $db->fetchRow($select, '', Zend_Db::FETCH_ASSOC);

            $imgInfo = Tinebase_ImageHelper::getImageInfoFromBlob(file_get_contents($tempFile['path']));
            $image = new Tinebase_Model_Image($imgInfo + array(
                'application' => $application,
                'id'          => $id,
                'location'    => $location
            ));
    	} else {
    	    $image = Tinebase_Controller::getInstance()->getImage($application, $id, $location);
    	}
    	
    	Tinebase_ImageHelper::resize($image, $width, $height, $ratiomode);
    	
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
	    $tine20path = dirname(dirname(__FILE__));
	    
	    // Tinebase first
	    $tinebaseHttp = new Tinebase_Http();
        $cssFiles = $tinebaseHttp->getCssFilesToInclude();
        $jsFiles  = $tinebaseHttp->getJsFilesToInclude();
        
        $_exclude[]  = '.';
        $_exclude[]  = 'Tinebase';
        	    
        $d = dir($tine20path);
	    while (false !== ($appName = $d->read())) {
            if (is_dir("$tine20path/$appName") && !in_array($appName, $_exclude)) {
                if (file_exists("$tine20path/$appName/Http.php")) {
                    $httpClass = $appName . "_Http";
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
            'js'  => $jsFiles,
            'css' => $cssFiles
        );
	}
}