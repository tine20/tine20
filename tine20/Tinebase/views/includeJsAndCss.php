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
        $includeFiles = Tinebase_Frontend_Http::getAllIncludeFiles();
        
        // css files
        foreach ($includeFiles['css'] as $name) {
            echo "\n    ". '<link rel="stylesheet" type="text/css" href="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'" />';
        }
        
        // js files
        foreach ($includeFiles['js'] as $name) {
            echo "\n    ". '<script type="text/javascript" src="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'"></script>';
        }
        
        // laguage file
        echo "\n    ". '<script type="text/javascript" src="index.php?method=Tinebase.getJsTranslations&' . time() . '"></script>';
        break;

    case 'DEBUG':
        echo "\n    <link rel='stylesheet' type='text/css' href='" . Tinebase_Frontend_Http_Abstract::_appendFileTime('Tinebase/css/' . $tineBuildPath . 'tine-all-debug.css') . "' />";
        echo "\n    <script type=\"text/javascript\" src=\"" . Tinebase_Frontend_Http_Abstract::_appendFileTime('Tinebase/js/' . $tineBuildPath . 'tine-all-debug.js') . "\"></script>";
        echo "\n    <script type=\"text/javascript\" src=\"" . Tinebase_Frontend_Http_Abstract::_appendFileTime("Tinebase/js/Locale/build/" . (string)$locale . "-all-debug.js") ."\"></script>";
        break;
        
    case 'RELEASE':
        echo "\n    <link rel='stylesheet' type='text/css' href='Tinebase/css/" . $tineBuildPath . "tine-all.css' />";
        echo "\n    <script type=\"text/javascript\" src=\"Tinebase/js/" . $tineBuildPath . "tine-all.js\"></script>";
        echo "\n    <script type=\"text/javascript\" src=\"Tinebase/js/Locale/build/" . (string)$locale . "-all.js\"></script>";
        break;
}

if (Tinebase_Core::getConfig()->customMainscreenHeaders) {echo "\n" . Tinebase_Core::getConfig()->customMainscreenHeaders;}
