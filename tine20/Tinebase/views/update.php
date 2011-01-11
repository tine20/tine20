<?php
/**
 * update view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css"/>
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all.js"></script>

	<?php require 'Tinebase/views/includeJsAndCss.php'; ?>
	
    <script type="text/javascript">
    	Ext.namespace('Tine');
    
    	// indicate that Tine needs to be updates so we don't initialize Tine
    	Tine.needUpdate = true;
    
        Ext.onReady(function() {
        	Ext.namespace('Tine', 'Tine.Tinebase');
        	
        	Tine.Tinebase.translation = new Locale.Gettext();
        	Tine.Tinebase.translation.textdomain('Tinebase');
	        window._ = function (msgid) {
	            return Tine.Tinebase.translation.dgettext('Tinebase', msgid);
	        };
        	
            var viewPort = new Ext.Viewport({
				layout: 'fit',
                items: {
                	xtype: 'container',
					layout: 'fit'
            	}
			});
            
            Ext.MessageBox.wait(_('Tine 2.0 needs to be updated or is not installed yet.'), _('Please wait or contact your administrator'));
            window.setTimeout('location.href = location.href', 20000);
        }); 
    </script>

</head>
<body>
<div id="button"></div>
</body>
</html>
