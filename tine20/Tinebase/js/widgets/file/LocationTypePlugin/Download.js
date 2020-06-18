/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./Abstract');

Ext.ns('Tine.Tinebase.widgets.file.LocationTypePlugin');

Tine.Tinebase.widgets.file.LocationTypePlugin.Download = function(config) {
    Ext.apply(this, config);
    
    this.name = i18n._('My Device');
};

Ext.extend(Tine.Tinebase.widgets.file.LocationTypePlugin.Download, Tine.Tinebase.widgets.file.LocationTypePlugin.Abstract, {
    locationType: 'download',
    iconCls: 'action_download',

    getSelectionDialogArea: async function(area, cmp) {
        if (! this.selectionDialogInitialised) {
            this.cmp = cmp;
            this.targetForm = new Ext.Panel({
                height: 38,
                border: false,
                frame: true,
                layout: 'hbox',
                disabled: !this.cmp.fileName,
                defaults: {
                    height: 38,
                    border: false,
                    frame: true
                },
                items: [{
                    flex: 1,
                }, {
                    layout: 'form',
                    labelAlign: 'left',
                    width: 450,
                    items: this.fileNameField = Ext.create({
                        xtype: 'textfield',
                        fieldLabel: 'Save as',
                        value: this.cmp.fileName,
                        width: 300,
                        validate: Ext.emptyFn,
                    })
                }, {
                    flex: 1,
                    cls: 'x-form'
                }]
            });

            this.pluginPanel = new Ext.Button(Ext.apply({
                text: i18n._('Download file and save on my local device.'),
                doAutoWidth: Ext.emptyFn,
                iconCls: 'action_download',
                cls: 'tw-FileSelectionArea',
                handler: this.cmp.onButtonApply,
                scope: this.cmp
            }, _.get(this, 'cmp.pluginConfig.' + this.plugin, {})));

            this.selectionDialogInitialised = true;
        }
        return _.get(this, area);
    },
    
    getFileList: function() {
        return [{
            file_name: this.fileNameField.getValue()
        }];
    },

    getLocationName: function(location) {
        return this.name;
    },
    
    manageButtonApply: function(buttonApply) {
        buttonApply.setDisabled(false);
    }
});
