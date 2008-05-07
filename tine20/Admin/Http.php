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
        
        $includeFiles = Tinebase_Http::getAllInclueFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
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
        
        $includeFiles = Tinebase_Http::getAllInclueFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
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
        
        $includeFiles = Tinebase_Http::getAllInclueFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    
    /**
     * display edit tag dialog
     *
     * @param   integer     tag id
     * 
     */
    public function editTag($tagId)
    {
        if(empty($tagId)) {
            $encodedTag = Zend_Json::encode(array());
        } else {
            $tag = Admin_Controller::getInstance()->getTag($tagId);

            $tag->rights = $tag->rights->toArray();
            
            $tag->rights = Admin_Json::resolveAccountName($tag->rights, true);
            $encodedTag = Zend_Json::encode($tag->toArray());
        }

        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
        
        $appList = Zend_Json::encode(Tinebase_Application::getInstance()->getApplications('%')->toArray());
        $view->jsExecute = "Tine.Admin.Tags.EditDialog.display($encodedTag, $appList);";

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit tag";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllInclueFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    
    /**
     * display edit role dialog
     *
     * @param   integer  $roleId   role id
     * 
     * @todo    create generic "edit" function with edit tag/group/role?
     * 
     */
    public function editRole($roleId)
    {
        $json = new Admin_Json();
        
        if(empty($roleId)) {
            $encodedRole = Zend_Json::encode(array());
            $encodedRoleMembers = Zend_Json::encode(array());
            $encodedRoleRights = Zend_Json::encode(array());
        } else {
            $role = Admin_Controller::getInstance()->getRole($roleId);         
            $encodedRole = Zend_Json::encode($role->toArray());           
            $encodedRoleMembers = Zend_Json::encode($json->getRoleMembers($roleId));            
            $encodedRoleRights = Zend_Json::encode($json->getRoleRights($roleId));
        }
        
        // @todo add function to json and comment testing arra
        //$encodedAllRights = Zend_Json::encode($json->getAllRoleRights();
        $encodedAllRights = Zend_Json::encode(array (
            array(
                "application_id" => 4,
                "text"      => "Addressbook",
                "children"  => array (
                    array (
                        "text"  => Tinebase_Acl_Rights::ADMIN,
                        "qtip"  => "admin right",
                    ),
                    array (
                        "text"  => Tinebase_Acl_Rights::RUN,
                        "qtip"  => "run right",
                    ),
                ),
            ),
            array(
                "application_id" => 2,
                "text"      => "Crm",
                "children"  => array (
                    array (
                        "text"  => Tinebase_Acl_Rights::ADMIN,
                        "qtip"  => "admin right",
                    ),
                    array (
                        "text"  => Tinebase_Acl_Rights::RUN,
                        "qtip"  => "admin right",
                    ),
                ),
            ),
        ));

        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
        
        //@todo move Roles.js to Admin.js later
        $view->jsExecute = 'Tine.Admin.Roles.EditDialog.display(' . 
            $encodedRole . ', ' . 
            $encodedRoleMembers . ', ' . 
            $encodedRoleRights . ', ' .
            $encodedAllRights .  
        ');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit role";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllInclueFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    
    /**
     * overwrite getJsFilesToInclude from abstract class to add groups js file
     *
     * @return array with js filenames
     */
    public function getJsFilesToInclude() {
        return array(
            'Admin/js/Admin.js',
            'Admin/js/Groups.js',
            'Admin/js/Tags.js',
            'Admin/js/Roles.js'
        );
    }
}