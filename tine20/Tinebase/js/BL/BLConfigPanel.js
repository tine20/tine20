/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./BLClassnameRenderer');
require('./BLConfigRecordRenderer');

Ext.ns('Tine.Tinebase.BL');

Tine.Tinebase.BL.BLConfigPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {

    /**
     * @cfg {Tine.Tinebase.data.Record} owningRecordClass
     * record class with blConfig field
     */
    owningRecordClass: null,

    /**
     * @cfg {String}
     * field name in owningRecordClass where blConfig is configured/stored
     */
    owningField: 'blpipe',

    /**
     * @cfg {String}
     * path to get/put data
     */
    dataPath: 'data.blpipe',

    quickaddMandatory: 'classname',
    autoExpandColumn: 'configRecord',

    initComponent: function() {
        var _ = window.lodash,
            me = this;

        if (! this.owningRecordClass && this.editDialog) {
            this.owningRecordClass = this.editDialog.recordClass;
        }

        var fieldCfg = _.get(this.owningRecordClass.getField(this.owningField), 'fieldDefinition.config');

        this.recordClass = Tine.Tinebase.data.RecordMgr.get(fieldCfg.recordClassName);


        // @TODO: move to generic 'model' picker
        this.BLElementConfigClassNames = _.get(this.recordClass.getField('classname'), 'fieldDefinition.config.availableModels', [])
        this.BLElementPicker = new Ext.form.ComboBox({
            store: _.reduce(this.BLElementConfigClassNames, function(arr, classname) {
                var recordClass = Tine.Tinebase.data.RecordMgr.get(classname);
                if (recordClass) {
                    arr.push([classname, recordClass.getRecordName() /*, recordClass.getDescription()*/]);
                }
                return arr;
            }, []),
            typeAhead: true,
            triggerAction: 'all',
            emptyText: i18n._('Add new Element...'),
            selectOnFocus:true,
        });

        // @TODO: move to generic model/dynamicRecord renderers
        this.columns = [
            {
                id:'classname',
                header: i18n._('Type'),
                width: 125,
                sortable: false,
                dataIndex: 'classname',
                quickaddField: this.BLElementPicker,
                renderer: Tine.widgets.grid.RendererManager.get('Tinebase', 'BLConfig', 'classname', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL)
            }, {
                id: 'configRecord',
                header: i18n._('Config'),
                width: 400,
                sortable: false,
                dataIndex: 'configRecord',
                renderer: Tine.widgets.grid.RendererManager.get('Tinebase', 'BLConfig', 'configRecord', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL)
        }];

        this.on('beforeaddrecord', this.onBeforeAddBLElementRecord, this);
        this.on('celldblclick', this.onCellDoubleClick, this);

        this.supr().initComponent.call(this);
    },

    onRender: function() {
        this.supr().onRender.apply(this, arguments);

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }
        
        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: in case we are rendered after record was load
        this.onRecordLoad(this.editDialog, this.editDialog.record);
    },

    onBeforeAddBLElementRecord: function(newRecord) {
        this.openEditDialog(newRecord);

        return false;
    },

    onCellDoubleClick: function(grid, row, col) {
        if (! this.readOnly) {
            var configWrapper = this.store.getAt(row);

            this.openEditDialog(configWrapper);
        }
    },

    openEditDialog: function(configWrapper) {

        var recordClass = Tine.Tinebase.data.RecordMgr.get(configWrapper.get('classname')),
            editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(recordClass),
            configRecord = configWrapper.get('configRecord') || {};

        if (! configRecord.data) {
            configRecord = Tine.Tinebase.data.Record.setFromJson(configRecord, recordClass);
        }

        if (editDialogClass) {
            editDialogClass.openWindow({
                mode: 'local',
                record: Ext.encode(configRecord.data),
                recordId: configRecord.getId(),
                listeners: {
                    scope: this,
                    'update': function (updatedRecord) {
                        if (!updatedRecord.data) {
                            updatedRecord = Tine.Tinebase.data.Record.setFromJson(updatedRecord, recordClass)
                        }
                        Tine.Tinebase.common.assertComparable(updatedRecord.data);
                        configWrapper.set('configRecord', updatedRecord.data);

                        if (this.store.indexOf(configWrapper) < 0) {
                            this.store.add([configWrapper]);
                        }
                    }
                }
            });
        }
    },

    onRecordLoad: function(editDialog, record) {
        var _ = window.lodash,
            data = _.get(record, this.dataPath) || [];

        this.setStoreFromArray(data);
    },

    onRecordUpdate: function(editDialog, record) {
        var _ = window.lodash,
            data = this.getFromStoreAsArray();

        _.set(record, this.dataPath, data)
    },

});