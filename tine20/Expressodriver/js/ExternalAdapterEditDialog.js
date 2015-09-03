/*
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 */

Ext.ns('Tine.Expressodriver', 'Tine.Expressodriver.ExternalAdapter');

/**
 * @namespace Tine.Expressodriver
 * @class Tine.Expressodriver.ExternalAdapterEditDialog
 * @extends Tine.widgets.dialog.EditDialog
 *
 *
 *
 * @param {Object}
 *            config
 * @constructor Create a new Tine.Expressodriver.ExternalAdapterEditDialog
 */
Tine.Expressodriver.ExternalAdapterEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowNamePrefix: 'ExternalAdapterEditWindow_',
    appName: 'Expressodriver',
    recordClass: Tine.Expressodriver.ExternalAdapter.Model,
    //recordProxy : Tine.Expressodriver.ExternalAdapter.Backend,
    mode: 'local',
    loadRecord: true,
    evalGrants: false,

    /**
     * generic apply changes handler
     */
    onApplyChanges: function() {
        this.onRecordUpdate();

        var form = this.getForm();
        if (form.isValid()) {
            var values = form.getValues();

            this.fireEvent('update', this.record);
            this.window.close();

        } else {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
        }
    },
    getFormItems: function() {

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            border : false,
                    items: [
                        {
                            title: this.app.i18n._('External Adapter'),
                            autoScroll: true,
                            border: false,
                            frame: true,
                            layout: 'border',
                            items: {
                                region: 'center',
                                xtype: 'columnform',
                                labelAlign: 'top',
                                formDefaults: {
                                    xtype: 'textfield',
                                    anchor: '100%',
                                    labelSeparator: '',
                                    columnWidth: .333

                                },
                                items: [
                                    [{
                                            columnWidth: 1,
                                            name: 'name',
                                            fieldLabel: this.app.i18n._('Name'),
                                            allowBlank: false
                                        }], [
                                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                                            app: 'Expressodriver',
                                            keyFieldName: 'externalDrivers',
                                            fieldLabel: this.app.i18n._('Adapter'),
                                            name: 'adapter'
                                        })

                                        ], [{
                                            columnWidth: 1,
                                            name: 'url',
                                            fieldLabel: this.app.i18n._('Url'),
                                            allowBlank: false

                                        }], [{
                                            name: 'useEmailAsLoginName',
                                            fieldLabel: this.app.i18n._('Use e-mail as login name'),
                                            xtype: 'checkbox'
                                        }]
                                ]
                            }

                        }]
        }
    }

});

/**
 * external adapter edit popup
 *
 * @param {Object}
 *            config
 * @return {Ext.ux.Window}
 */
Tine.Expressodriver.ExternalAdapterEditDialog.openWindow = function(config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 500,
        height: 400,
        name: Tine.Expressodriver.ExternalAdapterEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Expressodriver.ExternalAdapterEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
