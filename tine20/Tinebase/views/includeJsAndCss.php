<?php
/**
 * Tine 2.0 main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        check if build script puts the translation files in build dir $tineBuildPath
 */

echo "\n<!-- Tine 2.0 static files -->";

// this variable gets replaced by the buildscript
$tineBuildPath = '';


$locale = Zend_Registry::get('locale');

switch(TINE20_BUILDTYPE) {
    case 'DEVELOPMENT':
        $requiredApplications = array('Tinebase', 'Admin', 'Addressbook');
        $enabledApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->name;
        $orderedApplications = array_merge($requiredApplications, array_diff($enabledApplications, $requiredApplications));
        
        foreach ($orderedApplications as $application) {
            $className = $application . '_Frontend_Http';
            $httpFrontend = new $className;
            
            // css files
            foreach ($httpFrontend->getCssFilesToInclude() as $name) {
                echo "\n    ". '<link rel="stylesheet" type="text/css" href="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'" />';
            }
            
            // js files
            foreach ($httpFrontend->getJsFilesToInclude() as $name) {
                echo "\n    ". '<script type="text/javascript" src="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'"></script>';
            }
        }
        // laguage file
        echo "\n    ". '<script type="text/javascript" src="index.php?method=Tinebase.getJsTranslations&' . time() . '"></script>';
        break;

    case 'DEBUG':
        echo "\n    <link rel='stylesheet' type='text/css' href='index.php?method=Tinebase.getCssFiles&mode=debug' />";
        echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getJsFiles&mode=debug\"></script>";
        echo "\n    <script type=\"text/javascript\" src=\"" . Tinebase_Frontend_Http_Abstract::_appendFileTime("Tinebase/js/Locale/build/" . (string)$locale . "-all-debug.js") ."\"></script>";
        break;
        
    case 'RELEASE':
        echo "\n    <link rel='stylesheet' type='text/css' href='index.php?method=Tinebase.getCssFiles&mode=release' />";
        echo "\n    <script type=\"text/javascript\" src=\"index.php?method=Tinebase.getJsFiles&mode=release\"></script>";
        echo "\n    <script type=\"text/javascript\" src=\"Tinebase/js/Locale/build/" . (string)$locale . "-all.js\"></script>";
        break;
}

if (Tinebase_Core::getConfig()->customMainscreenHeaders) {echo "\n" . Tinebase_Core::getConfig()->customMainscreenHeaders;}
