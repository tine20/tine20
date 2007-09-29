<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $this->escape($this->title) ?></title>
	<!-- EXT JS -->
	<link rel="stylesheet" type="text/css" href="extjs/resources/css/ext-all.css"/>
	<link rel="stylesheet" type="text/css" href="extjs/resources/css/xtheme-gray.css"/>
	<script type="text/javascript" src="extjs/adapter/yui/yui-utilities.js"></script>     
	<script type="text/javascript" src="extjs/adapter/yui/ext-yui-adapter.js"></script>     
	<script type="text/javascript" src="extjs/ext-all.js"></script>

	<!-- eGW -->
	<link rel="stylesheet" type="text/css" href="Egwbase/css/egwbase.css"/>
	<script type="text/javascript" src="Addressbook/Js/Addressbook.js"></script>
	<script type="text/javascript" language="javascript">
		Ext.onReady(function(){
			<?php if(isset($this->formData)) echo "formData=" . Zend_Json::encode($this->formData) . ";" ?>
			<?php if(isset($this->jsExecute)) echo "$this->jsExecute" ?>
			window.focus();
		});
	</script>
	
	<?php 
		//error_log(print_r($this->jsIncludeFiles,true));
	
		foreach ($this->jsIncludeFiles as $name) {
			echo '<script type="text/javascript" language="javascript" src="'. $name .'"></script>';
		}
	?>
	
	<script type="text/javascript" language="javascript">
		var application = <?php echo Zend_Json::encode($this->application) ?>;
	</script>
	
	<style type="text/css">
	    .x-layout-panel {
	        background:#EEEEEE;
	    }
	</style>
</head>
<body>
<div id ="container">
<div id="header" class="x-layout-inactive-content" style="padding:0px;"></div>
<div id="content" class="x-layout-inactive-content" style="padding:3px;"></div>
<div id="south" class="x-layout-inactive-content" style="padding:3px;"></div>
</div> 
</body>
</html>
