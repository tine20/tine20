<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $this->escape($this->title) ?></title>
	<!-- EXT JS -->
	<link rel="stylesheet" type="text/css" href="ExtJS/resources/css/ext-all.css"/>
	<link rel="stylesheet" type="text/css" href="ExtJS/resources/css/xtheme-gray.css"/>
	<script type="text/javascript" src="ExtJS/adapter/yui/yui-utilities.js"></script>     
	<script type="text/javascript" src="ExtJS/adapter/yui/ext-yui-adapter.js"></script>     
	<script type="text/javascript" src="ExtJS/ext-all.js"></script>

	<!-- Tine -->
	<link rel="stylesheet" type="text/css" href="Tinebase/css/tinebase.css"/>
	<script type="text/javascript" language="javascript" src="Tinebase/js/Login.js"></script>
	<!-- initialize the registry, before the other js files get included -->
    <script type="text/javascript" language="javascript">
            <?php
                echo "Tine.Tinebase.jsonKey = '" . Zend_Registry::get('jsonKey') . "';\n";
            ?>
    </script>
	
	<script type="text/javascript" language="javascript">
		Ext.onReady(function() {
			TINENameSpace.Login.showLoginDialog(<?php echo "'{$this->defaultUsername}', '{$this->defaultPassword}'"?>);
		});
	</script>
</head>
<body>
<div id="loginMainDiv" style="width:255px;">
    <div id="loginForm"></div>
</div>
<!-- phpmyvisites -->
<script type="text/javascript">
<!--
var a_vars = Array();
var pagename='';

var phpmyvisitesSite = 4;
var phpmyvisitesURL = "http://stats.egroupware20.org/phpmyvisites.php";
//-->
</script>
<script language="javascript" src="http://stats.egroupware20.org/phpmyvisites.js" type="text/javascript"></script>
<!-- /phpmyvisites --> 
</body>
</html>
