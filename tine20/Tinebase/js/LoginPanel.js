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
     * @cfg {String}
     * translated heads up text
     */
    headsUpText: '',

    /**
     * @cfg {String} loginMethod server side login method
     */
    loginMethod: 'Tinebase.login',

    /**
     * @cfg {String} onLogin callback after successful login
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
     * @return {Ext.FormPanel}
     */
    getLoginPanel: function () {
        //Do we have a custom Logo for branding?
        var modSsl = Tine.Tinebase.registry.get('modSsl'),
            secondFactor = Tine.Tinebase.registry.get('secondFactor'),
            logo = Tine.installLogo;

        if (! this.loginPanel) {
            this.loginPanel = new Ext.Container({
                getForm: function() {return this.items.get(0).getForm();},
                layout: 'vbox',
                width: 320,
                height: 300,
                layoutConfig: {
                    align: 'stretch'
                },
                items: [
                    new Ext.FormPanel({
                        height: 210,
                        frame: true,
                        labelWidth: 90,
                        cls: 'tb-login-panel',
                        items: [/*{
                        xtype: 'container',
                        cls: 'tb-login-lobobox',
                        border: false,
                        html: '<div class="tb-login-headsup-box">'+ this.headsUpText + '</div><a target="_blank" href="' + Tine.websiteUrl + '" border="0"><img src="' + logo + '" /></a>'
                    },*/ {
                            xtype: 'label',
                            cls: 'tb-login-big-label',
                            text: i18n._('Login')
                        }, {
                            xtype: 'tinelangchooser',
                            name: 'locale',
                            width: 170,
                            tabindex: 1
                        }, {
                            xtype: 'textfield',
                            tabindex: 2,
                            width: 170,
                            fieldLabel: i18n._('Username'),
                            name: 'username',

                            allowBlank: modSsl ? false : true,
                            validateOnBlur: false,
                            selectOnFocus: true,
                            value: this.defaultUsername ? this.defaultUsername : undefined,
                            disabled: modSsl ? true : false,
                            listeners: {
                                scope: this,
                                render: function (field) {
                                    field.el.dom.setAttribute('autocapitalize', 'none');
                                    field.el.dom.setAttribute('autocorrect', 'off');
                                    if (Ext.supportsUserFocus) {
                                        field.focus(false, 250);
                                    }
                                },
                                focus: function(field) {
                                    if (Ext.isTouchDevice) {
                                        Ext.getBody().dom.scrollTop = this.loginPanel.getBox()['y'] - 10;
                                    }
                                }
                            }
                        }, {
                            xtype: 'textfield',
                            tabindex: 3,
                            width: 170,
                            inputType: 'password',
                            fieldLabel: i18n._('Password'),
                            name: 'password',
                            selectOnFocus: true,
                            value: this.defaultPassword,
                            disabled: modSsl ? true : false,
                            listeners: {
                                render: this.setLastLoginUser.createDelegate(this)
                            }
                        }, {
                            xtype: 'textfield',
                            tabindex: 4,
                            width: 170,
                            inputType: 'password',
                            hidden: secondFactor ? false : true,
                            fieldLabel: i18n._('Two-Factor Authentication Code'),
                            id: 'otp',
                            name: 'otp',
                            selectOnFocus: true
                        }, {
                            xtype: 'displayfield',
                            style: {
                                align: 'center',
                                marginTop: '10px'
                            },
                            value: i18n._('Certificate detected. Please, press Login button to proceed.'),
                            hidden: modSsl ? false : true
                        }, {
                            xtype: 'container',
                            id:'contImgCaptcha',
                            layout: 'form',
                            style: { visibility:'hidden' },
                            items:[{
                                xtype: 'textfield',
                                width: 170,
                                labelSeparator: '',
                                id: 'security_code',
                                value: null,
                                name: 'securitycode'
                            }, {
                                fieldLabel:(' '),
                                labelSeparator: '',
                                items:[
                                    new Ext.Component({
                                        autoEl: {
                                            tag: 'img',
                                            id: 'imgCaptcha'
                                        }
                                    })]
                            }]
                        }, {
                            xtype: 'container',
                            layout: 'hbox',
                            items: [{
                                width: 95
                            }, {
                                xtype: 'button',
                                width: 170,
                                text: i18n._('Login'),
                                scope: this,
                                handler: this.onLoginPress
                            }]
                        }, {
                            xtype: 'container',
                            style: 'padding-top: 2px',
                            layout: 'hbox',
                            hidden: !Tine.Tinebase.registry.get('sso'),
                            items: [{
                                width: 95,
                            }, {
                                xtype: 'button',
                                width: 170,
                                text: i18n._('Login via OpenID Connect'),
                                scope: this,
                                handler: this.onOIDCLoginPress
                            }]
                        }]
                    }), {
                        xtype: 'container',
                        style: 'height: 5px;'
                    }, {
                        xtype: 'container',
                        cls: 'tb-login-lobobox',
                        border: false,
                        html: '<div class="tb-login-headsup-box">'+ this.headsUpText + '</div><a target="_blank" href="' + Tine.websiteUrl + '" border="0"><img src="' + logo + '" /></a>'
                    }
                ]
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
    
    getVersionPanel: function () {
        if (! this.versionPanel) {
            var version = (Tine.Tinebase.registry.get('version')) ? Tine.Tinebase.registry.get('version') : {
                codeName: 'unknown',
                packageString: 'unknown'
            };
            
            var versionHtml = '<label class="tb-version-label">' + i18n._('Version') + ':</label> ' +
                              '<label class="tb-version-codename">' + version.codeName + '</label> ' +
                              '<label class="tb-version-packagestring">(' + version.packageString + ')</label>';
            this.versionPanel = new Ext.Container({
                layout: 'fit',
                cls: 'tb-version-tinepanel',
                border: false,
                defaults: {xtype: 'label'},
                items: [{
                    html: versionHtml
                }]
            })
        }

        return this.versionPanel;
    },

    getCommunityPanel: function () {
        if (! this.communityPanel) {
            var translationPanel = [],
                stats = Tine.__translationData.translationStats,
                version = Tine.clientVersion.packageString.match(/\d+\.\d+\.\d+/),
                language = Tine.Tinebase.registry.get('locale').language,
                // TODO make stats work again (currently displays 100% for all langs)
                //percentageCompleted =  stats ? Math.floor(100 * stats.translated / stats.total) : undefined;
                percentageCompleted = undefined;

            this.communityPanel = new Ext.Container({
                layout: 'fit',
                cls: 'tb-login-tinepanel',
                border: false,
                defaults: {xtype: 'label'},
                items: [{
                    cls: 'tb-login-big-label',
                    html: String.format(i18n._('{0} is made for you'), Tine.title)
                }, {
                    html: '<p>' + String.format(i18n._('{0} wants to make business collaboration easier and more enjoyable - for your needs! So you are warmly welcome to discuss with us, bring in ideas and get help.'), Tine.title) + '</p>'
                }, {
                    cls: 'tb-login-big-label-spacer',
                    html: '&nbsp;'
                }, {
                    html: '<ul>' +
                    '<li><a target="_blank" href="' + Tine.weburl + '" border="0">' + String.format(i18n._('{0} Homepage'), Tine.title) + '</a></li>' +
                    '<li><a target="_blank" href="http://www.tine20.org/forum/" border="0">' + String.format(i18n._('{0} Forum'), Tine.title) + '</a></li>' +
                    '</ul><br/>'
                }, {
                    cls: 'tb-login-big-label',
                    html: i18n._('Translations')
                }, {
                    html: Ext.isDefined(percentageCompleted) ? ('<p>' + String.format(i18n._('Translation state of {0}: {1}%.'), language, percentageCompleted) + '</p>') : ''
                }, {
                    html: '<p>' + String.format(i18n._('If the state of your language is not satisfying, or if you miss a language, please consider becoming a {0} translator.'), Tine.title) + '</p>'
                }, {
                    html: '<br/><ul>' +
                    '<li><a target="_blank" href="https://github.com/tine20/Tine-2.0-Open-Source-Groupware-and-CRM/wiki/EN%3Atranslation-Howto" border="0">' + String.format(i18n._('{0} Translation Howto'), Tine.title) + '</a></li>' +
                    '<li><a target="_blank" href="https://www.transifex.com/projects/p/tine20/" border="0">' + i18n._('Detailed Language Statistics') + '</a></li>'
                    + '</ul>'
                }]
            });
        }

        return this.communityPanel;
    },

    getPoweredByPanel: function () {
        if (! this.poweredByPanel) {
            this.poweredByPanel = new Ext.Container({
                layout: 'fit',
                cls: 'powered-by-panel',
                width: 200,
                height: 50,
                border: false,
                defaults: {xtype: 'label'},
                items: [{
                    html: "<div class='tine-viewport-poweredby' style='position: absolute; bottom: 10px; right: 10px; font:normal 12px arial, helvetica,tahoma,sans-serif;'>" + 
                        i18n._("Powered by:") + " <a target='_blank' href='" + Tine.weburl + "' title='" + i18n._("online open source groupware and crm") + "'>" + Tine.title + "</a>"
                }]
            });
        }
        
        return this.poweredByPanel;
    },
    
    getSurveyData: function (cb) {
        var ds = new Ext.data.Store({
            proxy: new Ext.data.ScriptTagProxy({
                url: 'https://versioncheck.tine20.net/surveyCheck/surveyCheck.php'
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
                Tine.log.debug('getSurveyPanel() - fetching survey data ...');
                this.getSurveyData(function (survey) {
                    Tine.log.debug(survey);
                    if (typeof survey.get === 'function') {
                        var enddate = Date.parseDate(survey.get('enddate'), Date.patterns.ISO8601Long);
                        var version = survey.get('version');
                        
                        Tine.log.debug('Survey version: ' + version + ' / Tine version: ' + Tine.clientVersion.packageString) ;
                        Tine.log.debug('Survey enddate: ' + enddate);
                        
                        if (Ext.isDate(enddate) && enddate.getTime() > new Date().getTime() && 
                            Tine.clientVersion.packageString.indexOf(version) === 0) {
                            Tine.log.debug('Show survey panel');
                            survey.data.lang_duration = String.format(i18n._('about {0} minutes'), survey.data.duration);
                            survey.data.link = 'https://versioncheck.tine20.net/surveyCheck/surveyCheck.php?participate';
                            
                            this.surveyPanel.add([{
                                cls: 'tb-login-big-label',
                                html: i18n._('Tine 2.0 needs your help')
                            }, {
                                html: '<p>' + i18n._('We regularly need your feedback to make the next Tine 2.0 releases fit your needs even better. Help us and yourself by participating:') + '</p>'
                            }, {
                                html: this.getSurveyTemplate().apply(survey.data)
                            }, {
                                xtype: 'button',
                                width: 120,
                                text: i18n._('participate!'),
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
                '<p>', i18n._('Languages'), ': {langs}</p>',
                '<p>', i18n._('Duration'), ': {lang_duration}</p>',
                '<br/>').compile();
        }
        
        return this.surveyTemplate;
    },
    
    /**
     * checks browser compatibility and show messages if unknown/incompatible
     * 
     * ie6-11, gecko2 -> bad
     * unknown browser -> may not work
     * 
     * @return {Ext.Container}
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
            if (Ext.isIE6 || Ext.isGecko2 || Ext.isIE || Ext.isNewIE) {
                browserSupport = 'incompatible';
            } else if (
                ! (Ext.isWebKit || Ext.isGecko || Ext.isEdge)
            ) {
                // yepp we also mean -> Ext.isOpera
                browserSupport = 'unknown';
            }
            
            var items = [];
            if (browserSupport == 'incompatible') {
                items = [{
                    cls: 'tb-login-big-label',
                    html: i18n._('Browser incompatible')
                }, {
                    html: '<p>' + i18n._('Your browser is not supported by Tine 2.0.') + '<br/><br/></p>'
                }];
            } else if (browserSupport == 'unknown') {
                items = [{
                    cls: 'tb-login-big-label',
                    html: i18n._('Browser incompatible?')
                }, {
                    html: '<p>' + i18n._('You are using an unrecognized browser. This could result in unexpected behaviour.') + '<br/><br/></p>'
                }];
            }
            
            if (browserSupport != 'compatible') {
                this.browserIncompatiblePanel.add(items.concat([{
                    html: '<p>' + i18n._('You might try one of these browsers:') + '<br/>'
                        + '<a href="https://www.google.com/chrome" target="_blank">Google Chrome</a><br/>'
                        + '<a href="https://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox</a><br/>'
                        + '<a href="https://www.apple.com/safari/" target="_blank">Apple Safari</a><br/>'
                        + '<a href="https://www.microsoft.com/en-us/windows/microsoft-edge" target="_blank">Microsoft Edge</a>'
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

        this.checkOIDCLogin();
    },

    checkOIDCLogin: function() {
        var oidcResponse = window.location.hash;
        if (oidcResponse.match(/access_token/)) {
            Ext.MessageBox.wait(String.format(i18n._('Login successful. Loading {0}...'), Tine.title), i18n._('Please wait!'));
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Tinebase.openIDCLogin',
                    oidcResponse: oidcResponse
                },
                timeout: 60000, // 1 minute
                success: this.onLoginSuccess
            });
        }
    },

    onLoginSuccess: function(response) {
        var responseData = Ext.util.JSON.decode(response.responseText);
        if (responseData.success === true) {
            Ext.MessageBox.wait(String.format(i18n._('Login successful. Loading {0}...'), Tine.title), i18n._('Please wait!'));
            window.document.title = this.originalTitle;
            response.responseData = responseData;
            this.onLogin.call(this.scope, response);
        } else {
            var modSsl = Tine.Tinebase.registry.get('modSsl');
            var resultMsg = modSsl ? i18n._('There was an error verifying your certificate!') :
                i18n._('Your username and/or your password are wrong!');
            Ext.MessageBox.show({
                title: i18n._('Login failure'),
                msg: resultMsg,
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR,
                fn: function () {
                    this.getLoginPanel().getForm().findField('password').focus(true);
                    if (document.getElementById('useCaptcha')) {
                        if (typeof responseData.c1 != 'undefined') {
                            document.getElementById('imgCaptcha').src = 'data:image/png;base64,' + responseData.c1;
                            document.getElementById('contImgCaptcha').style.visibility = 'visible';
                        }
                    }
                }.createDelegate(this)
            });
        }
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
            height: 520, // bad idea to hardcode height here
            layout: 'vbox',
            layoutConfig: {
                align: 'stretch'
            },
            items: infoPanelItems
        });
        
        this.items = [{
            xtype: 'container',
            layout: window.innerWidth < 768 ? 'column' : 'absolute',
            border: false,
            items: [
                this.getLoginPanel(),
                this.infoPanel,
                this.getPoweredByPanel(),
                this.getVersionPanel()
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
            Ext.MessageBox.wait(i18n._('Logging you in...'), i18n._('Please wait'));

            Ext.Ajax.request({
                scope: this,
                params : {
                    method: this.loginMethod,
                    username: values.username,
                    password: values.password,
                    securitycode: values.securitycode,
                    otp: values.otp
                },
                timeout: 60000, // 1 minute
                success: this.onLoginSuccess
            });
        } else {

            Ext.MessageBox.alert(i18n._('Errors'), i18n._('Please fix the errors noted.'));
        }
    },

    onOIDCLoginPress: function() {
        Ext.MessageBox.wait(i18n._('Redirecting to SSO Identity Provider'), i18n._('Please wait!'));
        window.location.href = 'http://localhost:4000/index.php?method=Tinebase.openIDCLogin';
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
        window.document.title = Ext.util.Format.stripTags(Tine.title + postfix + ' - ' + i18n._('Please enter your login data'));
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
        this.getPoweredByPanel().setPosition(box.width - this.poweredByPanel.width, box.height - this.poweredByPanel.height);
    },
    
    renderSurveyPanel: function (survey) {
        var items = [{
            cls: 'tb-login-big-label',
            html: i18n._('Tine 2.0 needs your help')
        }, {
            html: '<p>' + i18n._('We regularly need your feedback to make the next Tine 2.0 releases fit your needs even better. Help us and yourself by participating:') + '</p>'
        }];
    }
});
