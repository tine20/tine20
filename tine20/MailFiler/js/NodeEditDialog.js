/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.MailFiler');

/**
 * @namespace   Tine.MailFiler
 * @class       Tine.MailFiler.NodeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Node Compose Dialog</p>
 * <p></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.MailFiler.NodeEditDialog
 */
Tine.MailFiler.NodeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'NodeEditWindow_',
    appName: 'MailFiler',
    recordClass: Tine.MailFiler.Model.Node,
    recordProxy: Tine.MailFiler.fileRecordBackend,
    tbarItems: null,
    evalGrants: true,
    showContainerSelector: false,
    displayNotes: true,
    mailDetailsPanel: null,

    /**
     * @type Tine.MailFiler.DownloadLinkGridPanel
     */
    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('MailFiler');
        this.downloadAction = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: false,
            actionType: 'download',
            text: this.app.i18n._('Save locally'),
            handler: this.onDownload,
            iconCls: 'action_filemanager_save_all',
            disabled: false,
            scope: this
        });
        this.printAction = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Message'),
            handler: this.onPrint.createDelegate(this, []),
            disabled: false,
            iconCls:'action_print',
            scope:this
        });

        this.tbarItems = [this.downloadAction, this.printAction];

        Tine.MailFiler.NodeEditDialog.superclass.initComponent.call(this);
    },

    /**
     * Ripped of felamimail
     *
     * @param detailsPanel
     */
    onPrint: function() {
        var id = Ext.id(),
            doc = document,
            frame = doc.createElement('iframe');

        Ext.fly(frame).set({
            id: id,
            name: id,
            style: {
                position: 'absolute',
                width: '210mm',
                height: '297mm',
                top: '-10000px',
                left: '-10000px'
            }
        });

        doc.body.appendChild(frame);

        Ext.fly(frame).set({
            src : Ext.SSL_SECURE_URL
        });

        var doc = frame.contentWindow.document || frame.contentDocument || WINDOW.frames[id].document,
            content = this.getDetailsPanelContentForPrinting(this.mailDetailsPanel);

        doc.open();
        doc.write(content);
        doc.close();

        frame.contentWindow.focus();
        frame.contentWindow.print();
    },


    /**
     * get detail panel content
     *
     * @param {Tine.Felamimail.GridDetailsPanel} details panel
     * @return {String}
     */
    getDetailsPanelContentForPrinting: function(detailsPanel) {
        var detailsPanels = detailsPanel.getEl().query('.preview-panel-mail');

        var detailsPanelContent = (detailsPanels.length > 1) ? detailsPanels[1].innerHTML : detailsPanels[0].innerHTML;

        var buffer = '<html><head>';
        buffer += '<title>' + this.app.i18n._('Print Preview') + '</title>';
        buffer += '</head><body>';
        buffer += detailsPanelContent;
        buffer += '</body></html>';

        return buffer;
    },

    /**
     * folder or file?
     */
    getFittingTypeTranslation: function (isWindowTitle) {
        if (isWindowTitle) {
            return this.record.data.type == 'folder' ? this.app.i18n._('Edit folder') : this.app.i18n._('edit file');
        } else {
            return this.record.data.type == 'folder' ? this.app.i18n._('Folder') : this.app.i18n._('File');
        }
    },

    /**
     * executed when record is loaded
     * @private
     */
    onRecordLoad: function () {
        Tine.MailFiler.NodeEditDialog.superclass.onRecordLoad.apply(this, arguments);

        this.window.setTitle(this.getFittingTypeTranslation(true));

        this.mailDetailsPanel.loadRecord(this.record);
    },

    /**
     * download file
     */
    onDownload: function () {
        Tine.Filemanager.downloadFile(this.record, null, 'MailFiler');
    },

    /**
     * returns dialog
     * @return {Object}
     * @private
     */
    getFormItems: function () {
        var formFieldDefaults = {
            xtype: 'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: .5,
            readOnly: true,
            disabled: true
        };

        this.mailDetailsPanel = new Tine.MailFiler.MailDetailsPanel({
            appName: this.appName
        });

        var grantsPanel = new Tine.Filemanager.GrantsPanel({
            app: this.app,
            editDialog: this
        });

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            plugins: [{
                ptype: 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            items: [{
                title: this.getFittingTypeTranslation(false),
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'vbox',
                    layoutConfig: {
                        align : 'stretch',
                        pack  : 'start'
                    },
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        layout: 'hfit',
                        height: 155,
                        // flex: 1,
                        title: this.getFittingTypeTranslation(false),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: formFieldDefaults,
                            items: [[{
                                fieldLabel: this.app.i18n._('Name'),
                                name: 'name',
                                allowBlank: false,
                                readOnly: false,
                                columnWidth: .75,
                                disabled: false
                            }, {
                                fieldLabel: this.app.i18n._('Type'),
                                name: 'contenttype',
                                columnWidth: .25
                            }], [
                                Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    userOnly: true,
                                    useAccountRecord: true,
                                    blurOnSelect: true,
                                    fieldLabel: this.app.i18n._('Created By'),
                                    name: 'created_by'
                                }), {
                                    fieldLabel: this.app.i18n._('Creation Time'),
                                    name: 'creation_time',
                                    xtype: 'datefield'
                                }
                            ], [
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
                    }, {
                        layout: 'hfit',
                        autoScroll: true,
                        flex: 1,
                        items: [
                            this.mailDetailsPanel
                        ]
                    }]
                }, {
                    // activities and tags
                    layout: 'ux.multiaccordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Ext.Panel({
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype: 'textarea',
                                name: 'description',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100% 100%',
                                emptyText: this.app.i18n._('Enter description'),
                                requiredGrant: 'editGrant'
                            }]
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'MailFiler',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            },
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: 'Tinebase_Model_Tree_Node'
                }),
                // TODO include this?
                //{xtype: 'Tine.Filemanager.UsagePanel'},
                grantsPanel
            ]
        };
    }

});

/**
 * MailFiler Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.MailFiler.NodeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 570,
        name: Tine.MailFiler.NodeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.MailFiler.NodeEditDialog',
        contentPanelConstructorConfig: config
    });

    return window;
};
