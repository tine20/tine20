<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * This class handles all Http requests for the admin application
 *
 * @package     Admin
 */
class Admin_Http extends Tinebase_Application_Http_Abstract
{
    /**
     * the application name
     *
     * @var string
     */
    protected $_appname = 'Admin';
    
    /**
     * display edit account dialog
     *
     * @param   integer     account id
     * 
     */
    public function editAccountDialog($accountId)
    {
        if(!empty($accountId)) {
            $account = Tinebase_Account::getInstance()->getFullAccountById($accountId);
            $account->setTimezone(Zend_Registry::get('userTimeZone'));
            $arrayAccount = $account->toArray();
            
            // add primary group to account for the group selection combo box
            $group = $account->accountPrimaryGroup = Tinebase_Group::getInstance()->getGroupById($account->accountPrimaryGroup);
        } else {
            $arrayAccount = array('accountStatus' => 'enabled');
            
            // get default primary group for the group selection combo box
            $group = $account->accountPrimaryGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        }

        // encode the account array
        $arrayAccount['accountPrimaryGroup'] = $group->toArray();
        $encodedAccount = Zend_Json::encode($arrayAccount);                   
        
        $currentAccount = Zend_Registry::get('currentAccount');
                
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
        
        $view->jsExecute = 'Tine.Admin.Accounts.EditDialog.display(' . $encodedAccount .');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit account";

        $view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Tinebase_Http::getJsFilesToInclude(), $this->getJsFilesToInclude());
        $view->cssIncludeFiles = array_merge(Tinebase_Http::getCssFilesToInclude(), $this->getCssFilesToInclude());
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }

    /**
     * display edit group dialog
     *
     * @param   integer     group id
     * 
     */
    public function editGroup($groupId)
    {
        if(empty($groupId)) {
        	$encodedGroup = Zend_Json::encode(array());
        	$encodedGroupMembers = Zend_Json::encode(array());
        } else {
            $group = Admin_Controller::getInstance()->getGroup($groupId);        	
            $encodedGroup = Zend_Json::encode($group->toArray());
            $json = new Admin_Json();
            $encodedGroupMembers = Zend_Json::encode($json->getGroupMembers($groupId));
        }

        $currentAccount = Zend_Registry::get('currentAccount');
        
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
        
        //@todo move Groups.js to Admin.js later
        $view->jsExecute = 'Tine.Admin.Groups.EditDialog.display(' . $encodedGroup . ', ' . $encodedGroupMembers . ');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit group";

        $view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Tinebase_Http::getJsFilesToInclude(), $this->getJsFilesToInclude());
        $view->cssIncludeFiles = array_merge(Tinebase_Http::getCssFilesToInclude(), $this->getCssFilesToInclude());
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }

    /**
     * display edit application dialog
     *
     * @param   integer $appId    application id
     * 
     */
    public function editApplicationPermissions($appId)
    {
        $application = Admin_Controller::getInstance()->getApplication($appId);           
        $encodedApplication = Zend_Json::encode($application->toArray());
        
        // add accounts
        $json = new Admin_Json();
        $encodedPermissions = Zend_Json::encode($json->getApplicationPermissions($appId));
        
        // add all rights for this application
        $encodedRights = Zend_Json::encode(Tinebase_Application::getInstance()->getAllRights($appId));

        $currentAccount = Zend_Registry::get('currentAccount');
        
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
        
        $view->jsExecute = 'Tine.Admin.Applications.EditPermissionsDialog.display(' . 
            $encodedApplication . ', ' . $encodedPermissions . ', ' . $encodedRights . ');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit application permissions";

        $view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Tinebase_Http::getJsFilesToInclude(), $this->getJsFilesToInclude());
        $view->cssIncludeFiles = array_merge(Tinebase_Http::getCssFilesToInclude(), $this->getCssFilesToInclude());
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }    
    /**
     * overwrite getJsFilesToInclude from abstract class to add groups js file
     *
     * @return array with js filenames
     * 
     * @todo   remove later and add Group.js to Admin.js
     */
    public function getJsFilesToInclude() {
        $jsFiles = parent::getJsFilesToInclude();
        
        $jsFiles[] = self::_appendFileTime('Admin/js/Groups.js');
        
        return $jsFiles;
    }
}