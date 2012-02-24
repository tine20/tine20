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
    height: 350,
    minWidth: 400,
    minHeight: 350,
    layout: 'fit',
//    plain: true,
    title: null,

    initAboutTpl: function() {
        this.aboutTpl = new Ext.XTemplate(
            '<div class="tb-about-dlg">',
                '<div class="tb-about-img"><a href="{logoLink}" target="_blank"><img src="{logo}" /></a></div>',
                '<div class="tb-link-home"><a href="{logoLink}" target="_blank">{logoLink}</a></div>',
                '<div class="tb-about-version">Version: {codeName}</div>',
                '<div class="tb-about-build">({packageString})</div>',
                '<div class="tb-about-copyright">Copyright: 2007-{[new Date().getFullYear()]}&nbsp;<a href="http://www.metaways.de" target="_blank">Metaways Infosystems GmbH</a></div>',
            '</div>'
        );
    },
    
    initComponent: function() {
        this.title = String.format(_('About {0}'), Tine.title);
        
        this.initAboutTpl();
        
        var version = (Tine.Tinebase.registry.get('version')) ? Tine.Tinebase.registry.get('version') : {
            codeName: 'unknown',
            packageString: 'unknown'
        };

        this.items = {
            layout: 'fit',
            border: false,
            html: this.aboutTpl.applyTemplate({
                logo: Tine.Tinebase.LoginPanel.prototype.loginLogo,
                logoLink: Tine.weburl,
                codeName: version.codeName,
                packageString: version.packageString
            }),
            buttons: [{
                text: _('Ok'),
                iconCls: 'action_saveAndClose',
                handler: this.close,
                scope: this
            }]
        };
        
        Tine.Tinebase.AboutDialog.superclass.initComponent.call(this);
    }
});
