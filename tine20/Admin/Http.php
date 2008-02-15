<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
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
class Admin_Http extends Egwbase_Application_Http_Abstract
{
    protected $_appname = 'Admin';
    
    public function editAccountDialog($accountId)
    {
        if(!empty($accountId)) {
            $account = Egwbase_Account::getInstance()->getFullAccountById($accountId);
            $account->setTimezone(Zend_Registry::get('userTimeZone'));
            $account = Zend_Json::encode($account->toArray());
        } else {
            $account = Zend_Json::encode(array('accountStatus' => 'A'));
        }
        
        $locale = Zend_Registry::get('locale');
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $view = new Zend_View();
         
        $view->setScriptPath('Egwbase/views');
        $view->formData = array();
        $view->jsIncludeFiles = array();
        $view->cssIncludeFiles = array();
        
        $view->jsIncludeFiles[] = 'Admin/js/Admin.js';
        $view->cssIncludeFiles[] = 'Admin/css/Admin.css';
        $view->jsExecute = 'Egw.Admin.Accounts.EditDialog.display(' . $account . ');';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit account";

        $view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Egwbase_Http::getJsFilesToInclude(), $view->jsIncludeFiles);
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
}