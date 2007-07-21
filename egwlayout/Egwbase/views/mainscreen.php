<html>

<head>
	<title>eGW Test Layout</title>
	<link rel="stylesheet" type="text/css" href="ext-1.1-rc1/resources/css/ext-all.css"/>
	<link rel="stylesheet" type="text/css" href="Egwbase/css/egwbase.css"/>

	<!-- GC --> <!-- LIBS -->     
	<script type="text/javascript" src="ext-1.1-rc1/adapter/yui/yui-utilities.js"></script>     
	<script type="text/javascript" src="ext-1.1-rc1/adapter/yui/ext-yui-adapter.js"></script>     
	<!-- ENDLIBS -->
	<script type="text/javascript" src="ext-1.1-rc1/ext-all.js"></script>
	<script type="text/javascript" language="javascript" src="Egwbase/Js/Egwbase.js"></script>
	<?php 
		foreach ($this->applications as $val) {
			echo '<script type="text/javascript" language="javascript" src="'. $val .'/Js/'. $val .'.js"></script>"';
		}
	?>
</head>
<body>
	

	<!-- <img id="logo" src="/egwlayout/logo.png" alt="" style="margin: 0 78 0 0px;" /> -->

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
