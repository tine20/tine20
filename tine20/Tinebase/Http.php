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
     * displays the login dialog
     *
     */
    public function login()
    {
        $view = new Zend_View();
        $view->title="Tine 2.0";

        $view->setScriptPath('Tinebase/views');

        try {
            $loginConfig = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'login');
            $view->defaultUsername = (isset($loginConfig->username)) ? $loginConfig->username : '';
            $view->defaultPassword = (isset($loginConfig->password)) ? $loginConfig->password : '';
        } catch (Zend_Config_Exception $e) {
            $view->defaultUsername = '';
            $view->defaultPassword = '';
        }

        // check if registration is active
        try {
            $registrationConfig = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'registration');
            $view->userRegistration = (isset($registrationConfig->active)) ? $registrationConfig->active : '';
        } catch (Zend_Config_Exception $e) {
            $view->userRegistration = 0;
        }
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('login.php');
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
            // Tinebase
            'Tinebase/js/Tinebase.js',
            'Tinebase/js/Models.js',
            'Tinebase/js/Container.js',
            // Ext user extensions
            'Tinebase/js/ux/ButtonLockedToggle.js',
            'Tinebase/js/ux/Percentage.js',
            'Tinebase/js/ux/PopupWindow.js',
            'Tinebase/js/ux/Wizard.js',
            'Tinebase/js/ux/SearchField.js',
            'Tinebase/js/ux/grid/CheckColumn.js',
            'Tinebase/js/ux/grid/QuickaddGridPanel.js',
            'Tinebase/js/ux/file/Uploader.js',
            'Tinebase/js/ux/form/IconTextField.js',
            'Tinebase/js/ux/form/MirrorTextField.js',
            'Tinebase/js/ux/form/ColumnFormPanel.js',
            'Tinebase/js/ux/form/ExpandFieldSet.js',
            'Tinebase/js/ux/form/ClearableComboBox.js',
            'Tinebase/js/ux/form/ClearableDateField.js',
            'Tinebase/js/ux/form/ImageField.js',
            'Tinebase/js/ux/form/ImageCropper.js',
            'Tinebase/js/ux/form/BrowseButton.js',
            'Tinebase/js/ux/layout/HorizontalFitLayout.js',
            'Tinebase/js/DatepickerRange.js',
            // Tine 2.0 specific widgets
            'Tinebase/js/Widgets.js',
            'Tinebase/js/AccountpickerPanel.js',
            'Tinebase/js/widgets/account/PickerPanel.js',
            'Tinebase/js/widgets/account/ConfigGrid.js',
            'Tinebase/js/widgets/ContainerSelect.js',
            'Tinebase/js/widgets/ContainerGrants.js',
            'Tinebase/js/widgets/ContainerTree.js',
            'Tinebase/js/widgets/GroupSelect.js',
            'Tinebase/js/widgets/TagsPanel.js',
            'Tinebase/js/widgets/TagCombo.js',
            'Tinebase/js/widgets/CountryCombo.js',
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
    	   'Tinebase/css/ux/Wizard.css',
    	   'Tinebase/css/ux/grid/IconTextField.css',
    	   'Tinebase/css/ux/form/ExpandFieldSet.css',
    	   'Tinebase/css/widgets/TagsPanel.css',
    	);
    }
    
	/**
	 * renders the tine main screen 
	 *
	 * 
	 */
    public function mainScreen()
    {
        $userApplications = Zend_Registry::get('currentAccount')->getApplications();

        $view = new Zend_View();

        $view->setScriptPath('Tinebase/views');

        //$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-de-min.js');
        $view->jsIncludeFiles = $this->getJsFilesToInclude();
        $view->cssIncludeFiles = $this->getCssFilesToInclude();
        $view->initialData = array();
        
        foreach($userApplications as $application) {
            $httpAppName = ucfirst((string) $application) . '_Http';
            if(class_exists($httpAppName)) {
                $application_http = new $httpAppName;
                
                $view->initialData[ucfirst((string) $application)] = $application_http->getInitialMainScreenData();
                $view->initialData[ucfirst((string) $application)]['rights'] = Zend_Registry::get('currentAccount')->getRights((string) $application);
            }
        }
        
        $includeFiles = self::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        $view->configData = array(
            'timeZone'        => Zend_Registry::get('userTimeZone'),
            'currentAccount'  => Zend_Registry::get('currentAccount')->toArray(),
            'accountBackend'  => Tinebase_Account::getConfiguredBackend()
        );
        
        
        $view->title="Tine 2.0";

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }

	/**
	 * activate user account
	 *
	 * @param 	string $id
	 * 
	 */
	public function activateAccount ( $id ) 
	{
				
		// update registration table and get username / account values
		$account = Tinebase_Account_Registration::getInstance()->activateAccount ( $id );

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
		$captcha = Tinebase_Account_Registration::getInstance()->generateCaptcha();
		
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
	    $uploadedFile = $_FILES['file'];
	    
	    $path = tempnam('/tmp', 'tine_tempfile_');
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
	 * returns an array with all css and js files which needs to be included
	 * all over Tine 2.0
	 * 
	 * NOTE: As the HTML servers have no singletons, we need to create a unique
	 * instance here. This might get relaxed wiht php 5.3 with static functions
	 * 
	 * @return array 
	 */
	public static function getAllIncludeFiles()
	{
	    $tine20path = dirname(dirname(__FILE__));
	    
	    // Tinebase first
        $cssFiles = Tinebase_Http::getCssFilesToInclude();
        $jsFiles  = Tinebase_Http::getJsFilesToInclude();
	    
        $d = dir($tine20path);
	    while (false !== ($appName = $d->read())) {
            if (is_dir("$tine20path/$appName") && $appName{0} != '.' && $appName != 'Tinebase') {
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