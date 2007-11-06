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
	<?php 
		foreach ($this->jsIncludeFiles as $name) {
			echo '<script type="text/javascript" language="javascript" src="'. $name .'"></script>';
		}
		foreach ($this->cssIncludeFiles as $name) {
			echo '<link rel="stylesheet" type="text/css" href="'. $name .'" />';
		}
	?>
	<script type="text/javascript" language="javascript">
		Ext.onReady(function(){
			<?php if(isset($this->formData)) echo "formData=" . Zend_Json::encode($this->formData) . ";" ?>
			<?php if(isset($this->jsExecute)) echo "$this->jsExecute" ?>
			window.focus();
		});
	</script>
</head>
<body>
</body>
</html>
