<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$

/**
 * HTTP interface to Egwbase
 *
 */
class Egwbase_Http
{
    /**
     * displays the login dialog
     *
     */
    public function login()
    {
        $view = new Zend_View();

        $view->setScriptPath('Egwbase/views');

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('login.php');
    }

    public function mainScreen()
    {
        $accountId   = Zend_Registry::get('currentAccount')->account_id;
        $userApplications = Egwbase_Acl_Rights::getInstance()->getApplications($accountId);

        $view = new Zend_View();

        $view->setScriptPath('Egwbase/views');

        //$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-de-min.js');
        $view->jsIncludeFiles = array();
        $view->cssIncludeFiles = array();
        $view->initialData = array();
        
        foreach($userApplications as $application) {
            $applicationName = $application->app_name;
            $httpAppName = ucfirst($applicationName) . '_Http';
            $application = new $httpAppName;
            
            $view->jsIncludeFiles = array_merge($view->jsIncludeFiles, (array)$application->getJsFilesToInclude());
            $view->cssIncludeFiles = array_merge($view->cssIncludeFiles, (array)$application->getCssFilesToInclude());
            
            $view->initialData[ucfirst($applicationName)] = $application->getInitialMainScreenData();
        }
        
        // NOTE there is no 1:1 mapping of timezones:translation
        /*$translatedTimeZones = Zend_Registry::get('locale')->getTranslationList('timezone');
        
        $timeZoneData = array(
            'name'           => Zend_Registry::get('userTimeZone'),
            'translatedName' => $translatedTimeZones[Zend_Registry::get('userTimeZone')]
        );*/
        
        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        
        $view->title="eGroupWare 2.0";

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
}