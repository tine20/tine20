/*
 * Tine 2.0
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Expressomail');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.AdminPanel
 * @extends     Tine.widgets.dialog.AdminPanel
 * 
 * <p>Admin Admin Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.AdminPanel
 */
Tine.Expressomail.AdminPanel = Ext.extend(Tine.widgets.dialog.AdminPanel, {
    /**
     * @private
     */
    appName: 'Expressomail',
    
    /**
     * get config items
     * 
     * @return {Array}
     */
    getConfigItems: function() {
        return [[
            {
                name: 'imapSearchMaxResults',
                fieldLabel: this.app.i18n._('Max Results in Search Messages'),
                xtype: 'numberfield',
                value: null,
                minValue: 1,
                maxValue: 9999,
                allowBlank: false
            },
            {
                name: 'autoSaveDraftsInterval',
                fieldLabel: this.app.i18n._('Interval (in seconds) for Auto Saving Drafts (0 to disable)'),
                xtype: 'numberfield',
                value: null,
                minValue: 0,
                maxValue: 9999,
                allowBlank: false
            },
            {
                name: 'reportPhishingEmail',
                fieldLabel: this.app.i18n._('Email to which to report phishing'),
                xtype: 'textfield',
                value: null,
                maxLength: 64,
                allowBlank: true
            },
            {
                name: 'enableMailDirExport',
                fieldLabel: this.app.i18n._('Enable mail folders for exportation (compressed)'),
                typeAhead     : false,
                triggerAction : 'all',
                lazyRender    : true,
                editable      : false,
                mode          : 'local',
                forceSelection: true,
                value: null,
                xtype: 'combo',
                store: [
                    [false, this.app.i18n._('No')],
                    [true,  this.app.i18n._('Yes')]
                ]
            }
        ]];
    }
});

/**
 * admin panel on update function
 * 
 * TODO         update registry without reloading the mainscreen
 */
Tine.Expressomail.AdminPanel.onUpdate = function() {
    // reload mainscreen to make sure registry gets updated
    window.location = window.location.href.replace(/#+.*/, '');
}

/**
 * Admin admin settings popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Expressomail.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.Expressomail.AdminPanel.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Expressomail.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};
