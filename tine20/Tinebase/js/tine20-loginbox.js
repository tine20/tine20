/* $id */

/*
Example html code to include Tine 2.0 login box

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            
    <!-- ExtJS library: all widgets -->
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/ext-core/3/ext-core.js"></script>
                    
    <script type="text/javascript" src="Tinebase/js/tine20-loginbox.js"></script>
                            
</head>
<body>

<div id="tine20-login"></div>

</body>
</html>
*/

Ext.onReady(function(){
    var t = new Ext.Template(
        '<form name="{formId}" id="{formId}" method="POST" action="{action}">',
            '<fieldset>',
                '<label>Loginname:</label><br>',
                '<input type="text" name="username"><br>',
                '<label>Password:</label><br>',
                '<input type="password" name="password"><br>',
            '<input type="hidden" name="method" value="{method}"><br><br>',
            '<a class="linkWithIconBefore" href="javascript:document.{formId}.submit();">Login</a><br>',
        '</form>'
    );
    t.append('tine20-login', {formId: 'tine20loginform', action: 'index.php', method: 'Tinebase.loginFromPost'});
});
