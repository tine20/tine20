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
	<script type="text/javascript" language="javascript" src="Egwbase/Js/Egwbase.js"></script>
	<?php 
		foreach ($this->jsInlucdeFiles as $name) {
			echo '<script type="text/javascript" language="javascript" src="'. $name .'/Js/'. $name .'.js"></script>';
		}
	?>
	<script type="text/javascript" language="javascript">
		var applications = <?php echo Zend_Json::encode($this->applications) ?>;
	</script>
</head>
<body>
<div id="searchdiv">
	<input type="text" name="searchinput" id="searchinput"/>
</div> 
<!-- phpmyvisites -->
<script type="text/javascript">
<!--
var a_vars = Array();
var pagename='';

var phpmyvisitesSite = 1;
var phpmyvisitesURL = "http://lars.kneschke.de/phpmv2/phpmyvisites.php";
//-->
</script>
<script language="javascript" src="http://lars.kneschke.de/phpmv2/phpmyvisites.js" type="text/javascript"></script>
<!-- /phpmyvisites --> 
</body>
</html>
