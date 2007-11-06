<?php
/**
 * HTTP interface to Egwbase
 *
 * @author Lars Kneschke <l.kneschke@metaways.de>
 * @package Egwbase
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
        $userApplications = array('Addressbook'/*, 'Felamimail'*/, 'Admin', 'Calendar');
        $view = new Zend_View();

        $view->setScriptPath('Egwbase/views');

        //$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-de-min.js');
        $view->jsIncludeFiles = array();
        $view->cssIncludeFiles = array();
        $view->initialTree = array();
        
        foreach($userApplications as $applicationName) {
            $jsonAppName = $applicationName . '_Json';
            $application = new $jsonAppName;
            
            $view->jsIncludeFiles = array_merge($view->jsIncludeFiles, (array)$application->getJsFilesToInclude());
            $view->cssIncludeFiles = array_merge($view->cssIncludeFiles, (array)$application->getCssFilesToInclude());

            $view->initialTree[$applicationName] = $application->getInitialTree();
        }
        
        $translatedTimeZones = Zend_Registry::get('locale')->getTranslationList('timezone');
        
        $timeZoneData = array(
            'name'           => Zend_Registry::get('userTimeZone'),
            'translatedName' => $translatedTimeZones[Zend_Registry::get('userTimeZone')]
        );
        
        $view->configData = array(
            'timeZone' => $timeZoneData
        );
        
        
        $view->title="eGroupWare 2.0";

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
}