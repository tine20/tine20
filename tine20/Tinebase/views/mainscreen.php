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
    <link rel="stylesheet" type="text/css" href="ExtJS/resources/css/xtheme-gray.css" />
	
    <!-- <script type="text/javascript" src="ExtJS/adapter/yui/yui-utilities.js"></script> -->
    <!-- <script type="text/javascript" src="ExtJS/adapter/yui/ext-yui-adapter.js"></script> -->
    <script type="text/javascript" src="ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="ExtJS/ext-all.js"></script>

    <?php echo (isset($this->googleApi)) ? $this->googleApi : '' ?>

    <!-- Tine 2.0 static files -->
    <?php
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
    
    <!-- Static Localisation -->
    <?php  
        $locale = Zend_Registry::get('locale');
        echo '<script type="text/javascript" language="javascript" src="' . Tinebase_Application_Http_Abstract::_appendFileTime(Tinebase_Translation::getJsTranslationFile($locale, 'ext')) . '"></script>' . "\n";
        echo '<script type="text/javascript" language="javascript" src="' . Tinebase_Application_Http_Abstract::_appendFileTime(Tinebase_Translation::getJsTranslationFile($locale, 'generic')) . '"></script>' . "\n";
        echo '<script type="text/javascript" language="javascript" src="' . Tinebase_Application_Http_Abstract::_appendFileTime(Tinebase_Translation::getJsTranslationFile($locale, 'tine')) . '"></script>' . "\n";
    ?>
    
    
    <!-- Tine 2.0 dynamic initialisation -->
    <script type="text/javascript" language="javascript">
    // registry
        <?php
                foreach ((array)$this->configData as $index => $value) {
                    echo "\n    Tine.Tinebase.Registry.add('$index'," . Zend_Json::encode($value) . ");";
                }
                echo "\n    Tine.Tinebase.Registry.add('jsonKey','" . Zend_Registry::get('jsonKey') . "');";
        ?>
        
    // initialData
        <?php
           foreach ((array)$this->initialData as $appname => $data) {
               if (!empty($data) ) {
                   foreach ($data as $var => $content) {
                       echo "\n    Tine.$appname.$var = ". Zend_Json::encode($content). ';';
                   }
               }
           }
        ?>
        
    // onReady, fired by ExtJS
        Ext.onReady(function(){
            Tine.Tinebase.initFramework();
            <?php if(isset($this->formData)) echo "formData=" . Zend_Json::encode($this->formData) . "; \n" ?>
            <?php if(isset($this->jsExecute)) echo "$this->jsExecute \n" ?>
            window.focus();
    	});
    	
    </script>
</head>
<body>
</body>
</html>
