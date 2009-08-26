<?php
/**
 * main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        check if build script puts the translation files in build dir $tineBuildPath
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $this->escape($this->title) ?></title>

    <!-- EXT JS -->
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/xtheme-blue.css" />
    <?php /*
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/xtheme-gray.css" />
    <!-- <script type="text/javascript" src="library/ExtJS/adapter/yui/yui-utilities.js"></script> -->
    <!-- <script type="text/javascript" src="library/ExtJS/adapter/yui/ext-yui-adapter.js"></script> --> 
    */?>
    
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all<?php echo TINE20_BUILDTYPE != 'RELEASE' ? '-debug' : '' ?>.js"></script>

    <!-- Tine 2.0 setup static files --><?php
        /**
         * this variable gets replaced by the buildscript
         */
        $tineBuildPath = '';
        
        $locale = Zend_Registry::get('locale');
        switch(TINE20_BUILDTYPE) {
            case 'RELEASE':
                echo "\n    <link rel='stylesheet' type='text/css' href='Setup/css/" . $tineBuildPath . "setup-all.css' />";
                echo "\n    <script type='text/javascript' language='javascript' src='Setup/js/" . $tineBuildPath . "setup-all.js'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='Tinebase/js/Locale/build/" . (string)$locale . "-all.js'></script>";
                break;
                
            case 'DEBUG':
                echo "\n    <link rel='stylesheet' type='text/css' href='" . Tinebase_Frontend_Http_Abstract::_appendFileTime('Setup/css/' . $tineBuildPath . 'setup-all-debug.css') . "' />";
                echo "\n    <script type='text/javascript' language='javascript' src='" . Tinebase_Frontend_Http_Abstract::_appendFileTime('Setup/js/' . $tineBuildPath . 'setup-all-debug.js') . "'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='" . Tinebase_Frontend_Http_Abstract::_appendFileTime("Tinebase/js/Locale/build/" . (string)$locale . "-all-debug.js") ."'></script>";
                break;
                
            case 'DEVELOPMENT':
            default:
                $includeFiles = Setup_Frontend_Http::getAllIncludeFiles();
                
                // js files
                foreach ($includeFiles['css'] as $name) {
                    echo "\n    ". '<link rel="stylesheet" type="text/css" href="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'" />';
                }
                
                //css files
                foreach ($includeFiles['js'] as $name) {
                    echo "\n    ". '<script type="text/javascript" language="javascript" src="'. Tinebase_Frontend_Http_Abstract::_appendFileTime($name) .'"></script>';
                }
                
                // laguage file
                echo "\n    ". '<script type="text/javascript" language="javascript" src="setup.php?method=Tinebase.getJsTranslations&' . time() . '"></script>';
                break;
        }?>
</head>
<body>
    <noscript>You need to enable javascript to use <a href="http://www.tine20.org">Tine 2.0 setup or use the CLI setup</a></noscript>
</body>
</html>
