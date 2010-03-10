<?php
/**
 * update view
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/ext-all.css"/>
    <!-- <link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/xtheme-gray.css" /> --><!-- LIBS -->
    
    <script type="text/javascript" src="library/ExtJS/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="library/ExtJS/ext-all.js"></script>

    <script type="text/javascript">
        Ext.onReady(function() {
                
                
                new Ext.Viewport({
                        layout: 'fit',
                        items: {
                                xtype: 'panel',
                                layout: 'fit'
                        }
                    });
                    Ext.MessageBox.wait('Tine 2.0 needs to be updated or is not installed yet.', 'Please wait or contact your administrator');
                    window.setTimeout('location.href = location.href', 20000);
        }); 
    </script>

</head>
<body>
<div id="button"></div>
</body>
</html>
