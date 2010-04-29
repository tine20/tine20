<?php
/**
 * Tine 2.0 main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        check if build script puts the translation files in build dir $tineBuildPath
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Tine 2.0</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="icon" href="images/favicon.ico" type="image/x-icon" />

    <!-- EXT JS -->
     <?php
        if(isset(Tinebase_Core::getConfig()->themes->default)) {
            $path = Tinebase_Core::getConfig()->themes->themelist->get(Tinebase_Core::getConfig()->themes->default)->path;
            if(1 || Tinebase_Core::getConfig()->themes->themelist->get(Tinebase_Core::getConfig()->themes->default)->useBlueAsBase==1) {
                echo '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />';
            } else {
                echo '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all-notheme.css" />';
            }
            echo "\n".'<link rel="stylesheet" type="text/css" href="'.($path).'" />';
            echo "\n";
        } else {
            echo '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />';
            echo '<link rel="stylesheet" type="text/css" href="styles/tine20.css" />';
        }
     ?>
    
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>
    
    <!--  not yet
        <link rel="stylesheet" type="text/css"  media="print" href="Tinebase/css/print.css" />
    -->
          
    <!-- Tine 2.0 static files --><?php
        /**
         * this variable gets replaced by the buildscript
         */
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
        
        if (Tinebase_Core::getConfig()->customMainscreenHeaders) {echo "\n" . Tinebase_Core::getConfig()->customMainscreenHeaders;}?>
        
</head>
<body>
    <noscript><p>You need to enable javascript to use <a href="http://www.tine20.org">Tine 2.0</a></p></noscript>
</body>
</html>
