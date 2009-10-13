/* $Id$ */

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

<div id="tine20-login" style="width:100px;"></div>

</body>
</html>
*/

Ext.namespace('Tine20.Login');

Tine20.Login = {
    detectBrowserLanguage : function ()
    {
        var result = 'en';
        var userLanguage;

        if (navigator.userLanguage) {// Explorer
            userLanguage = navigator.userLanguage;
        } else if (navigator.language) {// FF
            userLanguage = navigator.language;
        }
        
        if(Tine20.Login.translations[userLanguage]) {
            result = userLanguage;
        }

        return result;
    },

    translations: {
        'en' : {
            'loginname' : 'Username',
            'password'  : 'Password',
            'login'     : 'Login'
        },
        'de' : {
            'loginname' : 'Benutzername',
            'password'  : 'Passwort',
            'login'     : 'Anmelden'
        },
    }
}

Ext.onReady(function(){
    var userLanguage = Tine20.Login.detectBrowserLanguage();
    
    var t = new Ext.Template (
        '<form name="{formId}" id="{formId}" method="POST" action="{action}">',
            '<fieldset>',
                '<label>{loginname}:</label><br>',
                '<input type="text" name="username"><br>',
                '<label>{password}:</label><br>',
                '<input type="password" name="password"><br>',
            '<input type="hidden" name="method" value="{method}"><br><br>',
            '<a class="linkWithIconBefore" href="javascript:document.{formId}.submit();">{login}</a><br>',
        '</form>'
    );
    t.append('tine20-login', {
        formId: 'tine20loginform', 
        action: 'index.php', 
        method: 'Tinebase.loginFromPost',
        loginname: Tine20.Login.translations[userLanguage].loginname,
        password: Tine20.Login.translations[userLanguage].password,
        login: Tine20.Login.translations[userLanguage].login
    });
});
