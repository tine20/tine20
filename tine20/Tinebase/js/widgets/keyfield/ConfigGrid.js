/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase.widgets.keyfield');

/**
 * quickadd grid panel
 * 
 * @namespace   Tine.Tinebase.widgets.keyfield
 * @class       Tine.Tinebase.widgets.keyfield.ConfigGrid
 * @extends     Tine.widgets.grid.QuickaddGridPanel
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tinebase.widgets.keyfield.ConfigGrid
 */
Tine.Tinebase.widgets.keyfield.ConfigGrid = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {

    /**
     * @cfg {record} (optional) configRecord keyFieldConfig record
     */
    configRecord: null,

    keyFieldOptions: null,

    autoExpandColumn: 'value',
    quickaddMandatory: 'id',
    resetAllOnNew: true,
    useBBar: true,
    border: false,

    /**
     * @private
     */
    initComponent: function() {
        if (this.configRecord) {
            // used withing config system
            this.keyFieldOptions = this.configRecord.get('options') || {};
        } else {
            // defaults
            this.keyFieldOptions = {
                recordModel : 'Tinebase_Config_KeyFieldRecord'
            }
        }

        this.defaultCheck = new Ext.ux.grid.CheckColumn({
            id: 'default',
            header: _('Default'),
            dataIndex: 'default',
            sortable: false,
            align: 'center',
            width: 55
        });
        this.plugins = [this.defaultCheck];

        this.initStore();

        Tine.Tinebase.widgets.keyfield.ConfigGrid.superclass.initComponent.call(this);

        this.getSelectionModel().on('selectionchange', function(sm) {
            var rows = sm.getSelections()
                deletedDisabled = rows.length == 0;

            Ext.each(sm.getSelections(), function(record) {
                deletedDisabled |= record.data.system;
            });
            this.deleteAction.setDisabled(deletedDisabled);
        }, this);

        this.on('beforeedit', this.onBeforeValueEdit, this);
    },

    /**
     * init keyfield store
     */
    initStore: function() {
        var storeEntry = Ext.data.Record.create([
            {name: 'default'},
            {name: 'id'},
            {name: 'value'},
            {name: 'color'}
        ]);
        this.recordClass = storeEntry;
        this.store = new Ext.data.Store({
            reader: new Ext.data.ArrayReader({idIndex: 0}, storeEntry),
            listeners: {
                scope: this,
                'update': function (store, rec, operation) {
                    rec.commit(true);

                    // be sure that only one default is checked
                    if (rec.get('default')) {
                        // reset other default of same parentId
                        var parentId = this.getParentId(rec);

                        store.each(function (r) {
                            if (this.getParentId(r) == parentId && r.id !== rec.id) {
                                r.set('default', false);
                                r.commit(true);
                            }
                        }, this);
                    }
                }
            }
        });
    },

    getParentId: function(r) {
        var parts = String(r.get('id')).split(':'),
            parentId = parts.length > 1 ? parts[0] : '';

        return parentId;
    },

    /**
     * @returns {Ext.grid.ColumnModel}
     */
    getColumnModel: function () {
        return new Ext.grid.ColumnModel([
            this.defaultCheck,
            {
                id: 'id',
                header: _('ID'),
                dataIndex: 'id',
                hideable: false,
                sortable: false,
                editor: new Ext.form.TextField({}),
                quickaddField: new Ext.form.TextField({
                    emptyText: _('Add a New ID...')
                })
            }, {
                id: 'value',
                header: _('Value'),
                dataIndex: 'value',
                hideable: false,
                sortable: false,
                editor: new Ext.form.TextField({}),
                quickaddField: new Ext.form.TextField({
                    emptyText: _('Add a New Value...')
                })

            }, {
                id: 'color',
                header: _('Color'),
                dataIndex: 'color',
                sortable: false,
                width: 50,
                editor: new Ext.ux.form.ColorField({}),
                renderer: Tine.Tinebase.common.colorRenderer
            }
        ]);
    },

    onBeforeValueEdit: function(o) {
        if (o.record.data.system == true) {
            o.cancel = true;
        }

    },
        /**
     * Do some checking on new entry add
     * 
     * @param {Object} recordData
     */
    onNewentry: function (recordData) {
        // check if id exists in grid
        if (this.store.findExact('id', recordData.id) !== -1) {
            Ext.Msg.alert(_('Error'), _('ID already exists'));
            return false;
        }

        if (this.keyFieldOptions.parentField && ! String(recordData.id).match(/^.+:.+$/)) {
            Ext.Msg.alert(_('Error'), _('ID needs to follow the syntax PARENTID:ID'));
            return false;
        }

        // if value is empty, set it to ID
        if (Ext.isEmpty(recordData.value)) {
            recordData.value = recordData.id;
        }

        Tine.Tinebase.widgets.keyfield.ConfigGrid.superclass.onNewentry.apply(this, arguments);
    },

    /**
     * @param value
     */
    setValue: function (value) {
        this.keyFieldConfig = Ext.decode(Ext.encode(value));

        this.setStoreFromArray(value.records);

        // if there is default value check it
        if (value['default']) {
            // there might be multiple defaults in case of dependend keyFields
            var defaults = Ext.isArray(value['default']) ? value['default'] : [value['default']];
            Ext.each(defaults, function(def) {
                var defaultRecIdx = this.store.findExact('id', def);
                if (defaultRecIdx !== -1) {
                    this.store.getAt(defaultRecIdx).set('default', true);
                }
            }, this);
        }
    },

    /**
     * @returns {Array}
     */
    getValue: function () {
        // don't touch other configs than records & default
        var value = Ext.decode(Ext.encode(this.keyFieldConfig)) || {};

        var records = [],
            defaults = [];

        this.store.each(function (rec) {
            var data = Ext.apply({}, rec.data);
            if (data['default'] == true) {
                defaults.push(data.id);
            }
            delete(data['default']);
            records.push(data);

        });

        value['records'] = records;
        value['default'] = Ext.isArray(value['default']) ? defaults : defaults[0];
        Tine.Tinebase.common.assertComparable(value);

        return value;
    },

    /**
     * @returns {boolean}
     */
    isValid: function () {
        return true;
    }
});
