/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './ProgressRenderer';

Ext.ns('Ext.ux.file');


Ext.ux.file.UploadManagementDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    /**
     * Tine.Filemanager.Model.DownloadLink
     */
    record: null,

    /**
     * Filemanager
     */
    app: null,

    windowNamePrefix: 'FilePublishedDialog_',

    layout: 'fit',
    border: false,
    frame: false,
    uploadStore: null,
    buttonAlign: null,
    /**
     * Constructor.
     */
    initComponent: function () {
        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.totalSize = 0;
        this.window.setTitle(this.app.i18n._('Uploads Monitor'));
    
        this.postalSubscriptions = [];
        this.postalSubscriptions.push(postal.subscribe({
            channel: "recordchange",
            topic: 'Filemanager.Node.*',
            callback: _.bind(this.onUploadRecordChange, this)
        }));
        
        this.uploadStore = new Ext.data.ArrayStore({
            sortInfo: {field: 'last_upload_time', direction: 'ASC'},
            fields: [
                {name: 'name'},
                {name: 'size', type: 'int'},
                {name: 'status'},
                {name: 'path'},
                {name: 'last_upload_time'},
            ]
        });

        this.clearAllUploadsButton = {
            xtype: 'button',
            text: this.app.i18n._('Stop and Clear All Uploads'),
            iconCls: 'action_cancel',
            minWidth: 120,
            scope: this,
            hidden: true,
            handler: async () => {
                await Tine.Tinebase.uploadManager.resetUploadChannels();
                this.uploadStore.removeAll();
                this.progressBar.show();
                this.progressBar.update(Tine.ux.file.ProgressRenderer(0, 0, /*use SoftQuota*/ false));
            }
        };

        this.byUserGrid = new Ext.grid.GridPanel({
            store: this.uploadStore,
            flex: 1,
            columns: [
                {id:'name',header: 'Name', width: 150, sortable: true, dataIndex: 'name', renderer: Ext.ux.PercentRendererWithName},
                {id:'path',header: 'Path', width: 100, sortable: true, dataIndex: 'path'},
                {id:'size',header: 'Size', width: 70, sortable: true, dataIndex: 'size', renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)},
                {id:'status',header: 'Status', width: 70, sortable: true, dataIndex: 'status'},
                {id:'last_upload_time',header: 'Upload Date', width: 150, sortable: true, dataIndex: 'last_upload_time',  renderer: Tine.Tinebase.common.dateTimeRenderer},
            ],
            stripeRows: true,
            autoExpandColumn: 'name',
            autoExpandMin : 200,
            autoShow: true,
        });
    
        this.byUserGrid.on('render', function(grid) {
            const store = grid.getStore();  // Capture the Store.
            const view = grid.getView();    // Capture the GridView.
            grid.tip = new Ext.ToolTip({
                width: 300,
                target: view.mainBody,    // The overall target element.
                delegate: '.x-grid3-row', // Each grid row causes its own seperate show and hide.
                trackMouse: true,         // Moving within the row should not hide the tip.
                renderTo: document.body,  // Render immediately so that tip.body can be
                                          //  referenced prior to the first show.
                listeners: {              // Change content dynamically depending on which element
                    //  triggered the show.
                    beforeshow: function updateTipBody(tip) {
                        const rowIndex = view.findRowIndex(tip.triggerElement);
                        let path = store.getAt(rowIndex).data.path;
                        path = path.startsWith('/') ? path.substring(1) : path;
                        path = path.replace(/\//g, '/<br />');
                        tip.body.dom.innerHTML = path;
                    }
                }
            });
        });

        this.progressBar = new Ext.Component({
            style: {
                marginTop: '3px',
                width: '200px',
                height: '16px',
            }
        });

        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            autoScroll: true,
            items: [
                this.byUserGrid,
            ]
        }];

        this.tbar = [
            {
                xtype: 'label',
                text: this.app.i18n._('Total Uploads : '),
                minWidth: 150,
            },
            this.progressBar,
            '->',
            this.clearAllUploadsButton,
        ];

        this.initButtons();
        Ext.ux.file.UploadManagementDialog.superclass.initComponent.call(this);
    },

    /**
     * init buttons
     */
    initButtons: function() {
        this.fbar = [
            '->', {
                text: this.applyButtonText ? this.app.i18n._hidden(this.applyButtonText) : i18n._('Ok'),
                minWidth: 100,
                ref: '../buttonApply',
                scope: this,
                handler: this.onButtonApply,
                iconCls: 'action_saveAndClose'
            }
        ];
    },

    afterRender: async function () {
        this.supr().afterRender.call(this);
        await this.loadUploadData();
    },

    async loadUploadData() {
        const tasks = await Tine.Tinebase.uploadManager.getAllFileUploadTasks();

        _.each(tasks, (task) => {
            let recordData = task.args.nodeData;
            recordData.id = Tine.Tinebase.data.Record.generateUID();
            const record = Tine.Tinebase.data.Record.setFromJson(recordData,Tine.Filemanager.Model.Node);
            
            record.data.status = task.status;
            record.data.last_upload_time = _.isString(recordData.last_upload_time) ? recordData.last_upload_time : recordData.last_upload_time.toJSON();
            if (record.data.status === 'pending') {
                this.uploadStore.insert(0, [record]);
            } else {
                this.uploadStore.addSorted(record);
            }
        });

        this.updateProgressBar();
    },

    /**
     * get record by data path
     * @param data
     */
    getRecordByData(data) {
        const store = this.uploadStore;

        return _.find(store.data.items, (node) => {return node.get('path') === data?.path;})
            || _.find(store.data.items, (node) => {return node?.id === data?.id;});
    },

    onUploadRecordChange(recordData, e) {
        if (!this.rendered) {
            return;
        }

        let record = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Filemanager.Model.Node);

        if (record && Ext.isFunction(record.copy)) {
            if (record.data.type === 'folder') {
                return;
            }

            record.data.status = recordData.status;
            record.data.last_upload_time = recordData.last_upload_time;

            const store = this.uploadStore;

            if (e.topic.match(/\.create/)) {
                store.insert(0, [record]);
            }

            if (e.topic.match(/\.update/)) {
                const existRecord = this.getRecordByData(recordData);
                record.data.id = existRecord.id;
                record.id = existRecord.id;
                const idx = store.indexOfId(existRecord.id);
                store.removeAt(idx);

                if (existRecord.data.status === 'pending' && record.data.status === 'uploading') {
                    store.insert(0, [record]);
                } else if (record.data.status === 'failed' || record.data.status === 'complete') {
                    store.add([record]);
                } else {
                    store.insert(idx, [record]);
                }
            }
        }

        if (record && e.topic.match(/\.delete/)) {
            this.store.remove(record);
        }

        this.updateProgressBar();
    },

    /**
     * update progress bar
     *
     * this monitor can not grab data from uploadManager , unless caculate tasks here
     */
    updateProgressBar: function () {
        const total = _.sum(_.map(this.uploadStore.data.items, 'data.size'));
        const completedUploads = this.uploadStore.query('status', 'complete');

        let current = _.sum(_.map(completedUploads.items, 'data.size'));
        const uploadingFiles = this.uploadStore.query('status', 'uploading');

        _.each(uploadingFiles.items, (upload) => {
            const progress = parseInt(_.last(_.split(upload.data.contenttype, ';')).replace('progress=', ''));
            current += upload.data.size * progress / 100;
        });

        this.progressBar.update(Tine.ux.file.ProgressRenderer(current, total, /*use SoftQuota*/ false));
        this.progressBar.show();
    },

    onDestroy: function() {
        _.each(this.postalSubscriptions, (subscription) => {subscription.unsubscribe()});
        return this.supr().onDestroy.call(this);
    },
});

Ext.ux.file.UploadManagementDialog.openWindow = function (config) {
    var id =  0;
    return Tine.WindowFactory.getWindow({
        width: 400,
        height: 300,
        name: Ext.ux.file.UploadManagementDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Ext.ux.file.UploadManagementDialog',
        contentPanelConstructorConfig: config,
    });
};
