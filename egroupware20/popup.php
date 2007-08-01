
<html>
<head>
	<title>EDIT POPUP</title>
	<link rel="stylesheet" type="text/css" href="extjs/resources/css/ext-all.css"/>
	<!-- GC --> <!-- LIBS -->     
	<script type="text/javascript" src="extjs/adapter/yui/yui-utilities.js"></script>     
	<script type="text/javascript" src="extjs/adapter/yui/ext-yui-adapter.js"></script>     
	<!-- ENDLIBS -->
	<script type="text/javascript" src="extjs/ext-all.js"></script>
	
	<script>
		<?  
		require_once('javascript.inc.php');		
		echo convertPHPArrayJSArray('_REQUEST',$_REQUEST);
		?>
	</script>
	
	<script type="text/javascript" language="javascript" src="popup.js"></script>
	
	
</head>

<body>


<!--<div>

<div style="padding-top:50px; text-align:center">
<b><i>Selected line has the following ID: <? //echo $_GET['userid']; ?></i></b><br /><br />
<input type="button" value="Click for refresh table grid from opener" onclick="window.opener.ds.load({params:{start:0, limit:5}});  window.opener.grid.getView().refresh();" />
</div>
</div>-->



</body>
</html>
