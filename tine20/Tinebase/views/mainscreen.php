<?php
/**
 * main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $this->escape($this->title) ?></title>

    <!-- EXT JS -->
    <link rel="stylesheet" type="text/css" href="ExtJS/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="ExtJS/resources/css/xtheme-gray.css" /><?php /*
    <!-- <script type="text/javascript" src="ExtJS/adapter/yui/yui-utilities.js"></script> -->
    <!-- <script type="text/javascript" src="ExtJS/adapter/yui/ext-yui-adapter.js"></script> --> */?>
    
    <script type="text/javascript" src="ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="ExtJS/ext-all.js"></script>

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
                    echo "\n    ". '<link rel="stylesheet" type="text/css" href="'. Tinebase_Application_Http_Abstract::_appendFileTime($name) .'" />';
                }
                
                //css files
                foreach ($includeFiles['js'] as $name) {
                    echo "\n    ". '<script type="text/javascript" language="javascript" src="'. Tinebase_Application_Http_Abstract::_appendFileTime($name) .'"></script>';
                }
                
                // laguage file
                echo "\n    ". '<script type="text/javascript" language="javascript" src="index.php?method=Tinebase.getJsTranslations&' . time() . '"></script>';
                break;

            case 'DEBUG':
                echo "\n    <link rel='stylesheet' type='text/css' href='" . Tinebase_Application_Http_Abstract::_appendFileTime('Tinebase/css/' . $tineBuildPath . 'tine-all-debug.css') . "' />";
                echo "\n    <script type='text/javascript' language='javascript' src='" . Tinebase_Application_Http_Abstract::_appendFileTime('Tinebase/js/' . $tineBuildPath . 'tine-all-debug.js') . "'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='" . Tinebase_Application_Http_Abstract::_appendFileTime("Tinebase/js/" . $tineBuildPath . 'Locale/build/' . (string)$locale . "-all-debug.js") ."'></script>";
                break;
                
            case 'RELEASE':
                echo "\n    <link rel='stylesheet' type='text/css' href='Tinebase/css/" . $tineBuildPath . "tine-all.css' />";
                echo "\n    <script type='text/javascript' language='javascript' src='Tinebase/js/" . $tineBuildPath . "tine-all.js'></script>";
                echo "\n    <script type='text/javascript' language='javascript' src='Tinebase/js/" . $tineBuildPath . 'Locale/build/' . (string)$locale . "-all.js'></script>";
                break;
        }?>
    
    
    <!-- Tine 2.0 dynamic initialisation -->
    <script type="text/javascript" language="javascript"><?php
        // registry data
        foreach ((array)$this->registryData as $appname => $data) {
            if ($appname != 'Tinebase') {
                echo "\n        Tine.$appname.registry = new Ext.util.MixedCollection();";
            }
            
            if (!empty($data) ) {
                foreach ($data as $var => $content) {
                    echo "\n        Tine.$appname.registry.add('$var'," . Zend_Json::encode($content). ");";
                }
            }
        }
        if(isset($this->jsExecute)) {
            echo "
            Tine.onReady = function() {
                $this->jsExecute
            };";
        }?>        
    </script>
</head>
<body>
</body>
</html>
