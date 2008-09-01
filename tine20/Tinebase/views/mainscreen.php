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
<html manifest="tine20.manifest">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $this->escape($this->title) ?></title>
    <!-- <script type="text/javascript" src="gears_init.js"></script>
    
    <script type="text/javascript" language="javascript">
        if (window.google && google.gears) {
            var localServer = google.gears.factory.create('beta.localserver');
            var store = localServer.createManagedStore('tine20-store');
            store.manifestUrl = 'Tinebase/js/tine20-manifest.js';
            store.checkForUpdate();
        }
    </script> -->

    <!-- EXT JS -->
    <link rel="stylesheet" type="text/css" href="ExtJS/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="ExtJS/resources/css/xtheme-gray.css" />
	
    <!-- <script type="text/javascript" src="ExtJS/adapter/yui/yui-utilities.js"></script> -->
    <!-- <script type="text/javascript" src="ExtJS/adapter/yui/ext-yui-adapter.js"></script> -->
    <script type="text/javascript" src="ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="ExtJS/ext-all.js"></script>

    <?php echo (isset($this->googleApi)) ? $this->googleApi : '' ?>

    <!-- Tine 2.0 static files --><?php
        $TinebasePath = dirname(dirname(__FILE__));
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        
        // include css files
        if (file_exists("$TinebasePath/css/tine-all.css")) {
            echo "\n    " . '<link rel="stylesheet" type="text/css" href="' . Tinebase_Application_Http_Abstract::_appendFileTime('Tinebase/css/tine-all.css') . '" />';
        } else {
            foreach ($includeFiles['css'] as $name) {
                echo "\n    ". '<link rel="stylesheet" type="text/css" href="'. Tinebase_Application_Http_Abstract::_appendFileTime($name) .'" />';
            }
        }
        
        // include js files
        if (file_exists("$TinebasePath/js/tine-all.js")) {
            echo "\n    " . '<script type="text/javascript" language="javascript" src="' . Tinebase_Application_Http_Abstract::_appendFileTime('Tinebase/js/tine-all.js') . '"></script>';
        } else {
        	foreach ($includeFiles['js'] as $name) {
        		echo "\n    ". '<script type="text/javascript" language="javascript" src="'. Tinebase_Application_Http_Abstract::_appendFileTime($name) .'"></script>';
        	}
        }
    ?>
    
    <!-- Static Localisation --><?php
    $locale = Zend_Registry::get('locale');
    echo "
    <script type='text/javascript' language='javascript' src='" . Tinebase_Application_Http_Abstract::_appendFileTime(Tinebase_Translation::getJsTranslationFile($locale, 'ext')) . "'></script>
    <script type='text/javascript' language='javascript' src='" . Tinebase_Application_Http_Abstract::_appendFileTime(Tinebase_Translation::getJsTranslationFile($locale, 'generic')) . "'></script>
    <script type='text/javascript' language='javascript' src='" . Tinebase_Application_Http_Abstract::_appendFileTime(Tinebase_Translation::getJsTranslationFile($locale, 'tine')) . "'></script>
    ";?>
    
    
    <!-- Tine 2.0 dynamic initialisation -->
    <script type="text/javascript" language="javascript"><?php
        // registry data
        foreach (Tinebase_Http::getRegistryData() as $index => $value) {
            echo "
        Tine.Tinebase.Registry.add('$index'," . Zend_Json::encode($value) . ");";
        }
        
        // initial data
        foreach ((array)$this->initialData as $appname => $data) {
            if (!empty($data) ) {
                foreach ($data as $var => $content) {
                    echo "
        Tine.$appname.$var = ". Zend_Json::encode($content). ';';
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
