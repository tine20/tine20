/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

 Ext.ns('Tine', 'Tine.Tinebase');

 /**
  * @namespace  Tine.Tinebase
  * @class      Tine.Tinebase.AboutDialog
  * @extends    Ext.Window
  */
Tine.Tinebase.AboutDialog = Ext.extend(Ext.Window, {

    closeAction: 'close',
    modal: true,
    width: 400,
    height: 360,
    minWidth: 400,
    minHeight: 360,
    layout: 'fit',
    title: null,

    initAboutTpl: function() {
        this.aboutTpl = new Ext.XTemplate(
            '<div class="tb-about-dlg">',
                '<div class="tb-about-img"><a href="{logoLink}" target="_blank"><img src="{logo}" /></a></div>',
                '<div class="tb-link-home"><a href="{logoLink}" target="_blank">{linkText}</a></div>',
                '<div class="tb-about-version">Version: {codeName}</div>',
                '<div class="tb-about-build">({packageString})</div>',
                '<div class="tb-about-copyright">Copyright: 2007-{[new Date().getFullYear()]}&nbsp;<a href="http://www.metaways.de/" target="_blank">Metaways Infosystems GmbH</a></div>',
                '<div class="tb-about-credits-license"><p><a href="javascript:void()" class="license" /><a href="javascript:void()" class="credits" /></p></div>',
            '</div>'
        );
    },
    
    initComponent: function() {
        this.title = String.format(i18n._('About {0}'), Tine.title);
        
        this.initAboutTpl();
        
        var version = (Tine.Tinebase.registry.get('version')) ? Tine.Tinebase.registry.get('version') : {
            codeName: 'unknown',
            packageString: 'unknown'
        };

        this.items = {
            layout: 'fit',
            border: false,
            html: this.aboutTpl.applyTemplate({
                logo: Tine.logo,
                logoLink: Tine.weburl,
                linkText: String.format(i18n._('Learn more about {0}'), Tine.title),
                codeName: version.codeName,
                packageString: version.packageString
            }),
            buttons: [{
                text: i18n._('Ok'),
                iconCls: 'action_saveAndClose',
                handler: this.close,
                scope: this
            }]
        };
        
        // create links
        this.on('afterrender', function() {
            var el = this.getEl().select('div.tb-about-dlg div.tb-about-credits-license a.license');
            el.insertHtml('beforeBegin', ' ' + i18n._('Released under different') + ' ');
            el.insertHtml('beforeEnd', i18n._('Open Source Licenses'));
            el.on('click', function(){
                var ls = new Tine.Tinebase.LicenseScreen();
                ls.show();
            });
            
            var el = this.getEl().select('div.tb-about-dlg div.tb-about-credits-license a.credits');
            el.insertHtml('beforeBegin', ' ' + i18n._('with the help of our') + ' ');
            el.insertHtml('beforeEnd', i18n._('Contributors'));
            el.on('click', function() {
                var cs = new Tine.Tinebase.CreditsScreen();
                cs.show();
            });
            
            
        }, this);

        Tine.Tinebase.AboutDialog.superclass.initComponent.call(this);
    }
});
