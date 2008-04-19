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
        return array(
            // base framework
            //self::_appendFileTime("../ExtJS/adapter/ext/ext-base.js"),
            //self::_appendFileTime("../ExtJS/ext-all-debug.js"),
            self::_appendFileTime("Tinebase/js/ExtFixes.js"),
            // Tinebase
            //self::_appendFileTime("Tinebase/js/Tinebase.js"),
            self::_appendFileTime("Tinebase/js/Models.js"),
            self::_appendFileTime("Tinebase/js/Container.js"),
            // Ext user extensions
            self::_appendFileTime("Tinebase/js/ExtUx.js"),
            self::_appendFileTime("Tinebase/js/ux/ButtonLockedToggle.js"),
            self::_appendFileTime("Tinebase/js/ux/Percentage.js"),
            self::_appendFileTime("Tinebase/js/ux/PopupWindow.js"),
            self::_appendFileTime("Tinebase/js/ux/Wizard.js"),
            self::_appendFileTime("Tinebase/js/ux/ExpandPanel.js"),
            self::_appendFileTime("Tinebase/js/ux/grid/CheckColumn.js"),
            self::_appendFileTime("Tinebase/js/ux/grid/QuickaddGridPanel.js"),
            self::_appendFileTime("Tinebase/js/ux/form/IconTextField.js"),
            self::_appendFileTime("Tinebase/js/ux/form/MirrorTextField.js"),
            self::_appendFileTime("Tinebase/js/ux/layout/ColumnForm.js"),
            self::_appendFileTime("Tinebase/js/ux/layout/HorizontalFitLayout.js"),
            self::_appendFileTime("Tinebase/js/DatepickerRange.js"),
            // Tine 2.0 specific widgets
            self::_appendFileTime("Tinebase/js/Widgets.js"),
            self::_appendFileTime("Tinebase/js/AccountpickerPanel.js"),
            self::_appendFileTime("Tinebase/js/widgets/ContainerSelect.js"),
            self::_appendFileTime("Tinebase/js/widgets/ContainerGrants.js"),
            self::_appendFileTime("Tinebase/js/widgets/ContainerTree.js"),
            self::_appendFileTime("Tinebase/js/widgets/GroupSelect.js"),
            self::_appendFileTime("Tinebase/js/widgets/TagsPanel.js"),
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
    	   self::_appendFileTime("Tinebase/css/ux/Wizard.css"),
    	   self::_appendFileTime("Tinebase/css/ux/grid/IconTextField.css"),
    	   self::_appendFileTime("Tinebase/css/Tinebase.css"),
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
                
                $view->jsIncludeFiles = array_merge($view->jsIncludeFiles, (array) $application_http->getJsFilesToInclude());
                $view->cssIncludeFiles = array_merge($view->cssIncludeFiles, (array) $application_http->getCssFilesToInclude());
                
                $view->initialData[ucfirst((string) $application)] = $application_http->getInitialMainScreenData();
                $view->initialData[ucfirst((string) $application)]['rights'] = Zend_Registry::get('currentAccount')->getRights((string) $application);
            }
        }
        
        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
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
}