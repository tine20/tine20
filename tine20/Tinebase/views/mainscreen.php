<?php
/**
 * main view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      ?
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo		add author
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
	
    <script type="text/javascript" src="ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="ExtJS/ext-all-debug.js"></script>

    <!-- Tine 2.0 -->
    <link rel="stylesheet" type="text/css" href="Tinebase/css/Tinebase.css"/>
    <script type="text/javascript" language="javascript" src="Tinebase/js/Tinebase.js"></script>
    <!-- initialize the registry, before the other js files get included -->
    <script type="text/javascript" language="javascript">
            <?php
                foreach ((array)$this->configData as $index => $value) {
                    echo "Tine.Tinebase.Registry.add('$index'," . Zend_Json::encode($value) . ");\n";
                }
                echo "Tine.Tinebase.Registry.add('jsonKey','" . Zend_Registry::get('jsonKey') . "');\n";
            ?>
    </script>
    <?php 
    	foreach ($this->jsIncludeFiles as $name) {
    		echo "\n    ". '<script type="text/javascript" language="javascript" src="'. $name .'"></script>';
    	}
    	foreach ($this->cssIncludeFiles as $name) {
    		echo "\n    ". '<link rel="stylesheet" type="text/css" href="'. $name .'" />';
    	}
    ?>
    <script type="text/javascript" language="javascript">
        <?php
           foreach ((array)$this->initialData as $appname => $data) {
               if (!empty($data) ) {
                   foreach ($data as $var => $content) {
                       echo "Tine.$appname.$var = ". Zend_Json::encode($content). ';';
                   }
               }
           }
        ?>

        Ext.onReady(function(){
            Tine.Tinebase.initFramework();
            <?php if(empty($this->isPopup)) echo "Tine.Tinebase.MainScreen.display(); \n" ?>
            <?php if(isset($this->formData)) echo "formData=" . Zend_Json::encode($this->formData) . "; \n" ?>
            <?php if(isset($this->jsExecute)) echo "$this->jsExecute \n" ?>
            window.focus();
    	});
    </script>
</head>
<body>
</body>
</html>
