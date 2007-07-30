<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $this->escape($this->title) ?></title>
	<!-- EXT JS -->
	<link rel="stylesheet" type="text/css" href="ext-1.1-rc1/resources/css/ext-all.css"/>
	<script type="text/javascript" src="ext-1.1-rc1/adapter/yui/yui-utilities.js"></script>     
	<script type="text/javascript" src="ext-1.1-rc1/adapter/yui/ext-yui-adapter.js"></script>     
	<script type="text/javascript" src="ext-1.1-rc1/ext-all.js"></script>

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
	
	<style type="text/css">
	    .x-layout-panel {
	        background:#EEEEEE;
	    }
	</style>
</head>
<body>
<div id ="container">
<div id="header" class="x-layout-inactive-content" style="padding:0px;"></div>
<div id="content" class="x-layout-inactive-content" style="padding:3px; bbackground:#EEEEEE;"></div>
</div> 
</body>
</html>
