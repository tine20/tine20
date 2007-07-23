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
			EGWNameSpace.Addressbook.alertme();
		});
	</script>
</head>
<body>
<div id="content">
</div> 
</body>
</html>
