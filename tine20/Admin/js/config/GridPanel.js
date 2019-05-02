/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Admin.config');

/**
 * config grid panel
 *
 * @namespace   Tine.Admin.config
 * @class       Tine.Admin.config.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.config.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    /**
     * app to configure with this config panel
     * @cfg {Tine.Tinebase.Application}
     */
    configApp: null,

    recordClass: Tine.Admin.Model.Config,
    defaultSortInfo: {field: 'name', direction: 'ASC'},

    evalGrants: false,

    initFilterPanel: Ext.emptyFn,
    onRowDblClick: Ext.emptyFn,

    /**
     * initComponent
     */
    initComponent: function() {
        var me = this;

        this.recordProxy = new Tine.Tinebase.data.RecordProxy({
            recordClass: this.recordClass
        });

        if (this.appName && !this.configApp) {
            this.configApp = Tine.Tinebase.appMgr.get(this.appName);
        }

        this.app = Tine.Tinebase.appMgr.get('Admin');
        this.title = this.app.i18n._("Config");

        this.gridConfig = {
            autoExpandColumn: 'value',
            gridType: Ext.grid.EditorGridPanel,
            clicksToEdit: 'auto',
            onEditComplete: this.onEditComplete
        };

        this.detailsPanel = new Tine.widgets.grid.DetailsPanel({
            singleRecordPanel: new Ext.ux.display.DisplayPanel({
                layout: 'fit',
                border: false,
                items: [{
                    cls: 'x-ux-display-background-border',
                    fieldLabel: me.app.i18n.gettext('Description'),
                    name: 'description',
                    xtype: 'ux.displaytextarea',
                    renderer: Tine.Tinebase.common.i18nRenderer.createDelegate(me.configApp.i18n)
                }]
            })
        });


        this.gridConfig.columns = [{
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 150,
            sortable: true,
            dataIndex: 'name'
        },{
            id: 'label',
            header: this.app.i18n._("Label"),
            width: 150,
            sortable: true,
            dataIndex: 'label',
            renderer: Tine.Tinebase.common.i18nRenderer.createDelegate(this.configApp.i18n)
        }, {
            id: 'value',
            header: this.app.i18n._("Value"),
            width: 150,
            sortable: true,
            dataIndex: 'value',
            renderer: this.valueRenderer.createDelegate(this),
            editable: true
        }];

        this.supr().initComponent.call(this);

        this.getGrid().on('beforeedit', this.onBeforeValueEdit, this);
        this.getGrid().on('afteredit', this.onAfterValuerEdit, this);
        this.action_editInNewWindow.setHidden(true);
        this.action_addInNewWindow.setHidden(true);
    },

    /**
     * @param o
         grid - This grid
         record - The record being edited
         field - The field name being edited
         value - The value for the field being edited.
         row - The grid row index
         column - The grid column index
         cancel - Set this to true to cancel the edit or return false from your handler.
     */
    onBeforeValueEdit: function(o) {
        if (o.field != 'value') {
            o.cancel = true;
        }

        else {
            var colModel = o.grid.getColumnModel(),
                type = o.record.get('type');

            if (o.record.get('source') == 'FILE') {
                // excluded by admin controller
            }
            o.record.beginEdit();

            if (o.record.get('source') == 'DEFAULT') {
                o.value = o.record.get('default');
            }

            o.value = String(o.value).match(/^[{\[]/) ? o.value : Ext.encode(o.value);
            o.record.set('value', Ext.decode(o.value));

            colModel.config[o.column].setEditor(
                Tine.Admin.config.FieldManager.create(o.record, {
                    app: this.configApp,
                    configRecord: o.record,
                    expandOnFocus: true
                })
            );
        }
    },

    onEditComplete: function(ed, value, startValue) {
        var type = _.get(ed, 'record.data.type');

        switch (type) {
            case 'record':
                value = _.get(ed, 'field.selectedRecord.data');
                Tine.Tinebase.common.assertComparable(value);
                break;

            default:
                break;

        }

        Ext.grid.EditorGridPanel.prototype.onEditComplete.call(this, ed, value, startValue);
    },

    /**
     * @param o
         grid - This grid
         record - The record being edited
         field - The field name being edited
         value - The value being set
         originalValue - The original value for the field, before the edit.
         row - The grid row index
         column - The grid column index
     */
    onAfterValuerEdit: function(o) {
        if (o.field == 'value') {
            o.value = Ext.encode(o.value);

            var def = o.record.get('default');
            def = String(def).match(/^[{\[]]/) ? def : Ext.encode(def);

            if (o.value == def) {
                o.record.cancelEdit();

                if (o.record.get('source') != 'DEFAULT') {
                    this.deleteRecords(this.grid.getSelectionModel(), [o.record]);
                }

                return;
            } else {
                if (o.record.get('source') == 'DEFAULT') {
                    o.record.set('source', '');
                }

                o.record.set('value', '');
                o.record.set('value', o.value);
            }

            if (o.record.get('id').match(/^virtual-/)) {
                o.record.set('id', '');
            }

            o.record.endEdit();

        }
    },

    initStore: function() {
        this.supr().initStore.call(this);

        this.store.on('beforeload', this.onStoreBeforeload, this);
    },

    onStoreBeforeload: function(store, options) {
        this.supr().onStoreBeforeload.call(this, store, options);
        options.params.filter.push({'field': 'application_id', 'operator': 'equals', 'value': this.configApp.id})
    },

    /**
     * called when the store gets updated, e.g. from editgrid
     *
     * @param {Ext.data.store} store
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} operation
     */
    onStoreUpdate: function(store, record, operation) {
        this.supr().onStoreUpdate.call(this, store, record, operation);

        switch (operation) {
            case Ext.data.Record.EDIT:
                // do/check registry reload
                // TODO do this in parent window?
                Tine.Tinebase.common.confirmApplicationRestart(true);
                break;
        }
    },

    valueRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        if (record.get('source') == 'DEFAULT') {
            value = Ext.encode(record.get('default'));
        }

        value = Ext.decode(value);

        switch (record.get('type')) {
            case 'bool':
                value = Tine.Tinebase.common.booleanRenderer(value);
                break;
            case 'keyField':
                Ext.each(record.get('options')['records'], function(record) {
                    if (record.id == value) {
                        value = this.configApp.i18n._hidden(record.value);
                    }
                }, this);
                break;
            case 'keyFieldConfig':
                value = '...';
                break;
            case 'record':
                var recordOptions = record.get('options'),
                    recordClass = Tine.Tinebase.data.RecordMgr.get(recordOptions.appName, recordOptions.modelName),
                    record = Tine.Tinebase.data.Record.setFromJson(value, recordClass);

                value = record.getTitle();
                break;
            default:
                break;
        }

        if (record.get('source') == 'DEFAULT') {
            return String.format(this.app.i18n._('Default ({0})'), value);
        }

        return value;
    }
});

