<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $this->escape($this->title) ?></title>
	<!-- EXT JS -->
	<link rel="stylesheet" type="text/css" href="extjs/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="extjs/resources/css/xtheme-gray.css" />
	
	<script type="text/javascript" src="extjs/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="extjs/ext-all-debug.js"></script>

	<!-- eGW -->
	<link rel="stylesheet" type="text/css" href="Egwbase/css/egwbase.css"/>
	<script type="text/javascript" language="javascript" src="Egwbase/js/Egwbase.js"></script>
    <!-- initialize the registry, before the other js files get included -->
    <script type="text/javascript" language="javascript">
            <?php
                foreach ((array)$this->configData as $index => $value) {
                    echo "Egw.Egwbase.Registry.add('$index'," . Zend_Json::encode($value) . ");\n";
                }
                echo "Egw.Egwbase.Registry.add('jsonKey','" . Zend_Registry::get('jsonKey') . "');\n";
            ?>
    </script>
	<?php 
		foreach ($this->jsIncludeFiles as $name) {
			echo '<script type="text/javascript" language="javascript" src="'. $name .'"></script>';
		}
		foreach ($this->cssIncludeFiles as $name) {
			echo '<link rel="stylesheet" type="text/css" href="'. $name .'" />';
		}
	?>
	<script type="text/javascript" language="javascript">
	   <?php
           foreach ((array)$this->initialData as $appname => $data) {
               if (!empty($data) ) {
                   foreach ($data as $var => $content) {
                       echo "Egw.$appname.$var = ". Zend_Json::encode($content). ';';
                   }
               }
           }
        ?>
        
		Ext.onReady(function(){
            Egw.Egwbase.initFramework();
			<?php if(isset($this->formData)) echo "formData=" . Zend_Json::encode($this->formData) . ";" ?>
			<?php if(isset($this->jsExecute)) echo "$this->jsExecute" ?>
			window.focus();
		});
	</script>
</head>
<body>
</body>
</html>
