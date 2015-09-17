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
Ext.ns('Tine.Expressodriver');

/**
 * @namespace   Tine.Expressodriver
 * @class       Tine.Expressodriver.NodeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Node Compose Dialog</p>
 * <p></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressodriver.NodeEditDialog
 */
Tine.Expressodriver.NodeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'NodeEditWindow_',
    appName: 'Expressodriver',
    recordClass: Tine.Expressodriver.Model.Node,
    recordProxy: Tine.Expressodriver.fileRecordBackend,
    tbarItems: null,
    evalGrants: true,
    showContainerSelector: false,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Expressodriver');
        this.downloadAction = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: false,
            actionType: 'download',
            text: this.app.i18n._('Save locally'),
            handler: this.onDownload,
            iconCls: 'action_expressodriver_save_all',
            disabled: false,
            scope: this
        });

        this.tbarItems = [this.downloadAction];

        Tine.Expressodriver.NodeEditDialog.superclass.initComponent.call(this);

        this.action_saveAndClose.setDisabled(true);

    },

    /**
     * download file
     */
    onDownload: function() {
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Expressodriver.downloadFile',
                requestType: 'HTTP',
                path: '',
                id: this.record.get('id')
            }
        }).start();
    },

    /**
     * returns dialog
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        var formFieldDefaults = {
            xtype:'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: .5,
            readOnly: true,
            disabled: true
        };

        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._('Node'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Node'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: formFieldDefaults,
                            items: [[{
                                    fieldLabel: this.app.i18n._('Name'),
                                    name: 'name',
                                    allowBlank: false,
                                    readOnly: true,
                                    columnWidth: .75,
                                    disabled: false
                                }, {
                                    fieldLabel: this.app.i18n._('Type'),
                                    name: 'contenttype',
                                    columnWidth: .25
                                }],[
                                Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    userOnly: true,
                                    useAccountRecord: true,
                                    blurOnSelect: true,
                                    fieldLabel: this.app.i18n._('Modified By'),
                                    name: 'last_modified_by'
                                }), {
                                    fieldLabel: this.app.i18n._('Last Modified'),
                                    name: 'last_modified_time',
                                    xtype: 'datefield'
                                }
                                ]]
                        }]
                    }]
                }]
            }]
        };
    },

    /**
     * creates the relations panel, if relations are defined
     */
    initRelationsPanel: function() {
        // do not initialize relations
    }
});

/**
 * Expressodriver Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Expressodriver.NodeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 570,
        height: 270,
        name: Tine.Expressodriver.NodeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Expressodriver.NodeEditDialog',
        contentPanelConstructorConfig: config
    });

    return window;
};
