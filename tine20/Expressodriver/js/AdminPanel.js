/*
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

Ext.namespace('Tine.Expressodriver');


/**
 * admin settings panel
 *
 * @namespace   Tine.Expressodriver
 * @class       Tine.Expressodriver.AdminPanel
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Expressodriver Admin Panel</p>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>

 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressodriver.AdminPanel
 */
Tine.Expressodriver.AdminPanel = Ext.extend(Tine.widgets.dialog.EditDialog, {

    appName: 'Expressodriver',
    recordClass: Tine.Expressodriver.Model.Settings,
    recordProxy: Tine.Expressodriver.settingsBackend,
    evalGrants: false,
    storageAdaptersPanel: null,

    updateToolbars: function() {
    },

    getFormItems: function() {
        this.storageAdaptersPanel = new Tine.Expressodriver.ExternalAdapterGridPanel({
            title: this.app.i18n._('External Storage Adapters')
        });

        return {
            xtype: 'tabpanel',
            activeTab: 0,
            border: true,
            items: [{
                title: this.app.i18n._('Default'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'fieldset',
                autoHeight: 'auto',
                items: [
                        {
                            name: 'useCache',
                            fieldLabel: this.app.i18n._('Enable cache for adapters'),
                            xtype: 'checkbox'
                        },
                        {
                            name: 'cacheLifetime',
                            fieldLabel: this.app.i18n._('Cache lifetime'),
                            xtype: 'numberfield',
                            allowDecimals: false,
                            allowNegative: false
                        }
                    ]
                },
                this.storageAdaptersPanel
            ]
        };

    },
     /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        this.window.setTitle(String.format(_('Change settings for application {0}'), this.appName));

        if (this.fireEvent('load', this) !== false) {
            var defaultSettings = this.record.get('default'),
                form = this.getForm();
            form.findField('useCache').setValue(defaultSettings.useCache);
            form.findField('cacheLifetime').setValue(defaultSettings.cacheLifetime);

            this.storageAdaptersPanel.loadAdapters(this.record.get('adapters'))

            form.clearInvalid();
            this.loadMask.hide();
        }
    },

    /**
     * is called from onApplyChanges
     * @param {Boolean} closeWindow
     */
    doApplyChanges: function(closeWindow) {
        // we need to sync record before validating to let (sub) panels have
        // current data of other panels
        this.onRecordUpdate();

        // quit copy mode
        this.copyRecord = false;

        if (this.isValid()) {

            var items = [];
            Ext.each(this.storageAdaptersPanel.getGrid().getStore().data.items, function(item){
                items.push(item.data);
            });
            this.record.set('adapters', items);

            var formData = this.getForm().getFieldValues();
            var defaultData = {
                'useCache': formData.useCache,
                'cacheLifetime': formData.cacheLifetime
            };
            this.record.set('default', defaultData);

            Ext.Ajax.request({
                params: {
                    method: 'Expressodriver.saveSettings',
                    recordData: this.record.data
                },
                scope: this,
                success: function (_result, _request) {
                    //this.record = Ext.util.JSON.decode(_result.responseText);

                    if (!Ext.isFunction(this.window.cascade)) {
                        this.onRecordLoad();
                    }
                    var ticketFn = this.onAfterApplyChanges.deferByTickets(this, [closeWindow]),
                            wrapTicket = ticketFn();

                    this.fireEvent('update', Ext.util.JSON.encode(this.record.data), this.mode, this, ticketFn);
                    wrapTicket();
                },
                failure: this.onRequestFailed,
                timeout: 300000 // 5 minutes

            });


        } else {
            this.saving = false;
            this.loadMask.hide();
            Ext.MessageBox.alert(_('Errors'), this.getValidationErrorMessage());
        }
    },

});

/**
 * Expressodriver admin settings popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Expressodriver.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        id: 'expressodriver-admin-panel',
        name: 'expressodriver-admin-panel',
        contentPanelConstructor: 'Tine.Expressodriver.AdminPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};