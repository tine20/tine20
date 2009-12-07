<?php
/**
 * main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        check if build script puts the translation files in build dir $tineBuildPath
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="icon" href="images/favicon.ico" type="image/x-icon" />

    <!-- EXT JS -->
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />
    
    <!--  http://extjs.com/forum/showthread.php?t=65694
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/xtheme-gray.css" /> -->
    
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>
    
    <script type="text/javascript" src="library/OpenLayers/OpenLayers.js"></script>
    
    <script type="text/javascript" src="library/GeoExt/script/GeoExt.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="library/GeoExt/resources/css/geoext-all.css"></link>
    
    <!-- Tine 2.0 static files --><?php
        /**
         * this variable gets replaced by the buildscript
         */
        $tineBuildPath = '';
        
        $locale = Zend_Registry::get('locale');
        switch(TINE20_BUILDTYPE) {
            case 'DEVELOPMENT':
                $includeFiles = Tinebase_Frontend_Http::getAllIncludeFiles();
                
                // js files
                foreach ($includeFiles['css'] as $name) {
                    echo "\n    ". '<link rel="stylesheet" type="text/css" href="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'" />';
                }
                
                //css files
                foreach ($includeFiles['js'] as $name) {
                    echo "\n    ". '<script type="text/javascript" language="javascript" src="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'"></script>';
                }
                
                // laguage file
                echo "\n    ". '<script type="text/javascript" language="javascript" src="index.php?method=Tinebase.getJsTranslations&' . time() . '"></script>';
                break;

            case 'DEBUG':
                echo "\n    <link rel='stylesheet' type='text/css' href='" . Tinebase_Frontend_Http_Abstract::_appendFileTime('Tinebase/css/' . $tineBuildPath . 'tine-all-debug.css') . "' />";
                echo "\n    <script type='text/javascript' language='javascript' src='" . Tinebase_Frontend_Http_Abstract::_appendFileTime('Tinebase/js/' . $tineBuildPath . 'tine-all-debug.js') . "'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='" . Tinebase_Frontend_Http_Abstract::_appendFileTime("Tinebase/js/Locale/build/" . (string)$locale . "-all-debug.js") ."'></script>";
                break;
                
            case 'RELEASE':
                echo "\n    <link rel='stylesheet' type='text/css' href='Tinebase/css/" . $tineBuildPath . "tine-all.css' />";
                echo "\n    <script type='text/javascript' language='javascript' src='Tinebase/js/" . $tineBuildPath . "tine-all.js'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='Tinebase/js/Locale/build/" . (string)$locale . "-all.js'></script>";
                break;
        }
        
        if (Tinebase_Core::getConfig()->customMainscreenHeaders) {echo "\n" . Tinebase_Core::getConfig()->customMainscreenHeaders;}?>
        
        <link rel="stylesheet" type="text/css" href="styles/tine20.css" />
</head>
<body>
    <noscript>You need to enable javascript to use <a href="http://www.tine20.org">Tine 2.0</a></noscript>
</body>
</html>
