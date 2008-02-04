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
class Egwbase_Http extends Egwbase_Application_Http_Abstract
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
    
    /**
     * Returns all JS files which must be included for Egwbase
     *
     * @todo refactor js stuff so that all js files could be included
     * before regestry gets included!
     * 
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            // base framework
            //self::_appendFileTime("../ExtJS/adapter/ext/ext-base.js"),
            //self::_appendFileTime("../ExtJS/ext-all-debug.js"),
            // Egwbase
            //self::_appendFileTime("Egwbase/js/Egwbase.js"),
            self::_appendFileTime("Egwbase/js/Container.js"),
            // widgets
            self::_appendFileTime("Egwbase/js/ExtUx.js"),
            self::_appendFileTime("Egwbase/js/DatepickerRange.js"),
            self::_appendFileTime("Egwbase/js/Widgets.js"),
            self::_appendFileTime("Egwbase/js/AccountpickerPanel.js"),
            self::_appendFileTime("Egwbase/js/widgets/ContainerSelect.js"),
            self::_appendFileTime("Egwbase/js/widgets/ContainerGrants.js"),
            self::_appendFileTime("Egwbase/js/widgets/ContainerTree.js")
        );
    }
    
    public function mainScreen()
    {
        $userApplications = Zend_Registry::get('currentAccount')->getApplications();

        $view = new Zend_View();

        $view->setScriptPath('Egwbase/views');

        //$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-de-min.js');
        $view->jsIncludeFiles = $this->getJsFilesToInclude();
        $view->cssIncludeFiles = array();
        $view->initialData = array();
        
        foreach($userApplications as $application) {
            $applicationName = $application->app_name;
            $httpAppName = ucfirst($applicationName) . '_Http';
            if(class_exists($httpAppName)) {
                $application = new $httpAppName;
                
                $view->jsIncludeFiles = array_merge($view->jsIncludeFiles, (array)$application->getJsFilesToInclude());
                $view->cssIncludeFiles = array_merge($view->cssIncludeFiles, (array)$application->getCssFilesToInclude());
                
                $view->initialData[ucfirst($applicationName)] = $application->getInitialMainScreenData();
            }
        }
        
        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        
        $view->title="eGroupWare 2.0";

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
}