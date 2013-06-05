/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/
 
Ext.ns('Tine.Tinebase');

/**
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.LoginPanel
 * @extends     Ext.Panel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.LoginPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {String} defaultUsername prefilled username
     */
    defaultUsername: '',
    
    /**
     * @cfg {String} defaultPassword prefilled password
     */
    defaultPassword: '',
    
    /**
     * @cfg {String} loginMethod server side login method
     */
    loginMethod: 'Tinebase.login',
    
    /**
     * @cfg {String} loginLogo logo to show
     */
    loginLogo: 'images/tine_logo.png',
    
    /**
     * @cfg {String} onLogin callback after successfull login
     */
    onLogin: Ext.emptyFn,
    
    /**
     * @cfg {Boolean} show infobox (survey, links, text)
     */
    showInfoBox: true,
    
    /**
     * @cfg {String} scope scope of login callback
     */
    scope: null,
    
    layout: 'fit',
    border: false,
    
    /**
     * return loginPanel
     * 
     * @return {Ext.FromPanel}
     */
    getLoginPanel: function () {

        var modSsl = Tine.Tinebase.registry.get('modSsl');
        
        if (! this.loginPanel) {
            this.loginPanel = new Ext.FormPanel({
                width: 460,
                height: 250,
                frame: true,
                labelWidth: 90,
                cls: 'tb-login-panel',
                items: [{
                    xtype: 'container',
                    cls: 'tb-login-lobobox',
                    border: false,
                    html: '<a target="_blank" href="' + Tine.weburl + '" border="0"><img src="' + this.loginLogo + '" /></a>'
                }, {
                    xtype: 'label',
                    cls: 'tb-login-big-label',
                    text: _('Login')
                }, {
                    xtype: 'tinelangchooser',
                    name: 'locale',
                    width: 170,
                    tabindex: 1
                }, {
                    xtype: 'textfield',
                    tabindex: 2,
                    width: 170,
                    fieldLabel: _('Username'),
                    id: 'username',
                    name: 'username',
                    allowBlank: modSsl ? false : true,
                    validateOnBlur: false,
                    selectOnFocus: true,
                    value: this.defaultUsername ? this.defaultUsername : undefined,
                    disabled: modSsl ? true : false,
                    listeners: {
                        render: function (field) {
                            field.focus(false, 250);
                        }
                    }
                }, {
                    xtype: 'textfield',
                    tabindex: 3,
                    width: 170,
                    inputType: 'password',
                    fieldLabel: _('Password'),
                    id: 'password',
                    name: 'password',
                    //allowBlank: false,
                    selectOnFocus: true,
                    value: this.defaultPassword,
                    disabled: modSsl ? true : false,
                    listeners: {
                        render: this.setLastLoginUser.createDelegate(this) 
                    }
                }, {
                    xtype: 'displayfield',
                    style: {
                        align: 'center',
                        marginTop: '10px'
                    },
                    value: _('Certificate detected. Please, press Login button to proceed.'),
                    hidden: modSsl ? false : true
                }],
                buttonAlign: 'right',
                buttons: [{
                    xtype: 'button',
                    width: 120,
                    text: _('Login'),
                    scope: this,
                    handler: this.onLoginPress
                }]
            });
        }
        
        return this.loginPanel;
    },

    setLastLoginUser: function (field) {
        var lastUser;
        lastUser = Ext.util.Cookies.get('TINE20LASTUSERID');
        if (lastUser) {
            this.loginPanel.getForm().findField('username').setValue(lastUser);
            field.focus(false,250);
        }
    },
     
    getCommunityPanel: function () {
        if (! this.communityPanel) {
            var translationPanel = [],
                stats = Locale.translationStats,
                version = Tine.clientVersion.packageString.match(/\d+\.\d+\.\d+/),
                language = Tine.Tinebase.registry.get('locale').language,
                percentageCompleted = stats ? Math.floor(100 * stats.translated / stats.total) : undefined;
                
            this.communityPanel = new Ext.Container({
                layout: 'fit',
                cls: 'tb-login-tinepanel',
                border: false,
                defaults: {xtype: 'label'},
                items: [{
                    cls: 'tb-login-big-label',
                    html: _('Tine 2.0 is made for you')
                }, {
                    html: '<p>' + _('Tine 2.0 wants to make business collaboration easier and more enjoyable - for your needs! So you are warmly welcome to discuss with us, bring in ideas and get help.') + '</p>'
                }, {
                    cls: 'tb-login-big-label-spacer',
                    html: '&nbsp;'
                }, {
                    html: '<ul>' + 
                        '<li><a target="_blank" href="' + Tine.weburl + '" border="0">' + _('Tine 2.0 Homepage') + '</a></li>' +
                        '<li><a target="_blank" href="http://www.tine20.org/forum/" border="0">' + _('Tine 2.0 Forum') + '</a></li>' +
                    '</ul><br/>'
                }, {
                    cls: 'tb-login-big-label',
                    html: _('Translations')
                }, {
                    html: Ext.isDefined(percentageCompleted) ? ('<p>' + String.format(_('Translation state of {0}: {1}%.'), language, percentageCompleted) + '</p>') : ''
                }, {
                    html: '<p>' + _('If the state of your language is not satisfying, or if you miss a language, please consider becoming a Tine 2.0 translator.') + '</p>'
                }, {
                    html: '<br/><ul>' +
                        '<li><a target="_blank" href="http://www.tine20.org/wiki/index.php/Contributors/Howtos/Translations" border="0">' + _('Tine 2.0 Translation Howto') + '</a></li>' +
                        '<li><a target="_blank" href="http://www.tine20.org/langStats/"' + (Ext.isArray(version) ? '?v=' + version[0] : '') +' border="0">' + _('Detailed Language Statistics') + '</a></li>'
                    + '</ul>'
                }]
            });
        }
        
        return this.communityPanel;
    },
    
    getSurveyData: function (cb) {
        var ds = new Ext.data.Store({
            proxy: new Ext.data.ScriptTagProxy({
                url: 'https://versioncheck.officespot20.com/surveyCheck/surveyCheck.php'
            }),
            reader: new Ext.data.JsonReader({
                root: 'survey'
            }, ['title', 'subtitle', 'duration', 'langs', 'link', 'enddate', 'htmlmessage', 'version'])
        });
        
        ds.on('load', function (store, records) {
            var survey = records[0];
            
            cb.call(this, survey);
        }, this);
        ds.load({params: {lang: Tine.Tinebase.registry.get('locale').locale}});
    },
    
    getSurveyPanel: function () {
        if (! this.surveyPanel) {
            this.surveyPanel = new Ext.Container({
                layout: 'fit',
                cls: 'tb-login-surveypanel',
                border: false,
                defaults: {xtype: 'label'},
                items: []
            });
            
            if (! Tine.Tinebase.registry.get('denySurveys')) {
                this.getSurveyData(function (survey) {
                    if (typeof survey.get === 'function') {
                        var enddate = Date.parseDate(survey.get('enddate'), Date.patterns.ISO8601Long);
                        var version = survey.get('version');
                        
                        if (Ext.isDate(enddate) && enddate.getTime() > new Date().getTime() && 
                            Tine.clientVersion.packageString.indexOf(version) === 0) {
                            survey.data.lang_duration = String.format(_('about {0} minutes'), survey.data.duration);
                            survey.data.link = 'https://versioncheck.officespot20.com/surveyCheck/surveyCheck.php?participate';
                            
                            this.surveyPanel.add([{
                                cls: 'tb-login-big-label',
                                html: _('Tine 2.0 needs your help')
                            }, {
                                html: '<p>' + _('We regularly need your feedback to make the next Tine 2.0 releases fit your needs even better. Help us and yourself by participating:') + '</p>'
                            }, {
                                html: this.getSurveyTemplate().apply(survey.data)
                            }, {
                                xtype: 'button',
                                width: 120,
                                text: _('participate!'),
                                handler: function () {
                                    window.open(survey.data.link);
                                }
                            }]);
                            this.surveyPanel.doLayout();
                        }
                    }
                });
            }
        }
        
        return this.surveyPanel;
    },
    
    getSurveyTemplate: function () {
        if (! this.surveyTemplate) {
            this.surveyTemplate = new Ext.XTemplate(
                '<br/ >',
                '<p><b>{title}</b></p>',
                '<p><a target="_blank" href="{link}" border="0">{subtitle}</a></p>',
                '<br/>',
                '<p>', _('Languages'), ': {langs}</p>',
                '<p>', _('Duration'), ': {lang_duration}</p>',
                '<br/>').compile();
        }
        
        return this.surveyTemplate;
    },
    
    /**
     * checks browser compatibility and show messages if unknown/incompatible
     * 
     * ie6, gecko2 -> bad
     * unknown browser -> may not work
     * 
     * @return {Ext.Container}
     * 
     * TODO find icons with the correct license
     */
    getBrowserIncompatiblePanel: function() {
        if (! this.browserIncompatiblePanel) {
            this.browserIncompatiblePanel = new Ext.Container({
                layout: 'fit',
                cls: 'tb-login-surveypanel',
                border: false,
                defaults: {xtype: 'label'},
                items: []
            });
            
            var browserSupport = 'compatible';
            if (Ext.isIE6 || Ext.isGecko2) {
                browserSupport = 'incompatible';
            } else if (
                ! (Ext.isWebKit || Ext.isGecko || Ext.isIE || Ext.isNewIE)
            ) {
                // yepp we also mean -> Ext.isOpera
                browserSupport = 'unknown';
            }
            
            var items = [];
            if (browserSupport == 'incompatible') {
                items = [{
                    cls: 'tb-login-big-label',
                    html: _('Browser incompatible')
                }, {
                    html: '<p>' + _('Your browser is not supported by Tine 2.0.') + '<br/><br/></p>'
                }];
            } else if (browserSupport == 'unknown') {
                items = [{
                    cls: 'tb-login-big-label',
                    html: _('Browser incompatible?')
                }, {
                    html: '<p>' + _('You are using an unrecognized browser. This could result in unexpected behaviour.') + '<br/><br/></p>'
                }];
            }
            
            if (browserSupport != 'compatible') {
                this.browserIncompatiblePanel.add(items.concat([{
                    html: '<p>' + _('You might try one of these browsers:') + '<br/>'
                        + '<a href="http://www.google.com/chrome" target="_blank">Google Chrome</a><br/>'
                        + '<a href="http://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox</a><br/>'
                        + '<a href="http://www.apple.com/safari/download/" target="_blank">Apple Safari</a><br/>'    
                        + '<a href="http://www.microsoft.com/windows/internet-explorer/default.aspx" target="_blank">Microsoft Internet Explorer</a>'
                        + '<br/></p>'
                }]));
                this.browserIncompatiblePanel.doLayout();
            }
        }
        
        return this.browserIncompatiblePanel;
    },
    
    initComponent: function () {
        this.initLayout();
        
        this.supr().initComponent.call(this);
    },
    
    initLayout: function () {
        var infoPanelItems = (this.showInfoBox) ? [
            this.getBrowserIncompatiblePanel(),
            this.getCommunityPanel(),
            this.getSurveyPanel()
        ] : [];
        
        this.infoPanel = new Ext.Container({
            cls: 'tb-login-infosection',
            border: false,
            width: 300,
            height: 460,
            layout: 'vbox',
            layoutConfig: {
                align: 'stretch'
            },
            items: infoPanelItems
        });
        
        this.items = [{
            xtype: 'container',
            layout: 'absolute',
            border: false,
            items: [
                this.getLoginPanel(),
                this.infoPanel
            ]
        }];
    },
    
    /**
     * do the actual login
     */
    onLoginPress: function () {
        var form = this.getLoginPanel().getForm(),
            values = form.getValues();
            
        if (form.isValid()) {
            Ext.MessageBox.wait(_('Logging you in...'), _('Please wait'));
            
            Ext.Ajax.request({
                scope: this,
                params : {
                    method: this.loginMethod,
                    username: values.username,
                    password: values.password
                },
                timeout: 60000, // 1 minute
                callback: function (request, httpStatus, response) {
                    var responseData = Ext.util.JSON.decode(response.responseText);
                    if (responseData.success === true) {
                        Ext.MessageBox.wait(String.format(_('Login successful. Loading {0}...'), Tine.title), _('Please wait!'));
                        window.document.title = this.originalTitle;
                        this.onLogin.call(this.scope);
                    } else {
                        if (responseData.data && responseData.data.code === 510) {
                            // NOTE: when communication is lost, we can't create a nice ext window.
                            (function() {
                                Ext.MessageBox.hide();
                                alert(_('Connection lost, please check your network!'));
                            }).defer(1000);
                        } else {
                            var modSsl = Tine.Tinebase.registry.get('modSsl');
                            var resultMsg = modSsl ? _('There was an error verifying your certificate!!!') : 
                                _('Your username and/or your password are wrong!!!');
                            Ext.MessageBox.show({
                                title: _('Login failure'),
                                msg: resultMsg,
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR,
                                fn: function () {
                                    this.getLoginPanel().getForm().findField('password').focus(true);
                                }.createDelegate(this)
                            });
                        }
                    }
                }
            });
        } else {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
        }
    },
    
    onRender: function (ct, position) {
        this.supr().onRender.apply(this, arguments);
        
        this.map = new Ext.KeyMap(this.el, [{
            key : [10, 13],
            scope : this,
            fn : this.onLoginPress
        }]);
        
        this.originalTitle = window.document.title;
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        window.document.title = Tine.title + postfix + ' - ' + _('Please enter your login data');
    },
    
    onResize: function () {
        this.supr().onResize.apply(this, arguments);

        var box      = this.getBox(),
            loginBox = this.getLoginPanel().rendered ? this.getLoginPanel().getBox() : {width : this.getLoginPanel().width, height: this.getLoginPanel().height},
            infoBox  = this.infoPanel.rendered ? this.infoPanel.getBox() : {width : this.infoPanel.width, height: this.infoPanel.height};

        var top = (box.height - loginBox.height) / 2;
        if (box.height - top < infoBox.height) {
            top = box.height - infoBox.height;
        }
        
        var loginLeft = (box.width - loginBox.width) / 2;
        if (loginLeft + loginBox.width + infoBox.width > box.width) {
            loginLeft = box.width - loginBox.width - infoBox.width;
        }
                
        this.getLoginPanel().setPosition(loginLeft, top);
        this.infoPanel.setPosition(loginLeft + loginBox.width, top);
    },
    
    renderSurveyPanel: function (survey) {
        var items = [{
            cls: 'tb-login-big-label',
            html: _('Tine 2.0 needs your help')
        }, {
            html: '<p>' + _('We regularly need your feedback to make the next Tine 2.0 releases fit your needs even better. Help us and yourself by participating:') + '</p>'
        }];
    }
});
