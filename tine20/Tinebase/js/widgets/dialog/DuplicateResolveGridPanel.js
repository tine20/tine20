/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.dialog');

/**
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.DuplicateResolveGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.dialog.DuplicateResolveGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,

    // private config overrides
    cls: 'tw-editdialog',
    border: false,
    layout: 'fit',
    enableColumnMove: false,
    stripeRows: true,
    trackMouseOver: false,
    clicksToEdit:2,
    enableHdMenu : false,
    viewConfig : {
        forceFit:true
    },

    initComponent: function() {

        this.title = i18n._('The record you try to add might already exist.');

        this.view = new Ext.grid.GroupingView({
            forceFit:true,
            hideGroupedColumn: true,
            groupTextTpl: '{group}'
        });

        this.initColumnModel();
        this.initToolbar();

        this.store.on('load', this.onStoreLoad, this);
        this.store.on('strategychange', this.onStoreLoad, this);
        this.on('cellclick', this.onCellClick, this);
        this.on('afterrender', this.onAfterRender, this);
        this.on('afteredit', this.onAfterEdit, this);

        Tine.widgets.dialog.DuplicateResolveGridPanel.superclass.initComponent.call(this);
    },

    onAfterRender: function() {
        // apply initial strategy
        this.onStoreLoad();
    },

    /**
     * is called on doubleclick on a cell and sets the editor if the cell is the finalValue
     * 
     * @param {Ext.grid.EditorGridPanel} grid
     * @param {number} rowIndex
     * @param {number} colIndex
     * @param {Ext.EventObject} e
     */
    onCellDblClick: function(grid, rowIndex, colIndex, e) {
        var strategy = this.actionCombo.getValue();
        
        // editing is just possible if strategy "keep" is chosen, or the finalValue when choosing strategy "mergeTheirs" or "mergeMine"
        if (! (((colIndex == 4) && (strategy == 'mergeMine' || strategy == 'mergeTheirs')) 
            || (strategy == 'keep' && (colIndex == 2)))) {
            return;
        }
        
        var cm = this.getColumnModel();
        var column = cm.getColumnAt(colIndex);
        var record = this.store.getAt(rowIndex);
        
        var fieldDef = record.get('fieldDef');
        
        if (! Ext.isFunction(fieldDef.type)) {
            switch (fieldDef.type) {
                case 'string':
                    column.setEditor(new Ext.form.TextField({}));
                    break;
                case 'date':
                    column.setEditor(new Ext.ux.form.ClearableDateField({}));
                    break;
                case 'int':
                    column.setEditor(new Ext.form.NumberField({}));
                    break;
                default:
                    // TODO: allow more types, create FieldRegistry
                    return;
            }
            this.startEditing(rowIndex, colIndex);
        } else {
            return;
        }
    },
    
    /**
     * is called after edit a field
     * 
     * @param {Ext.EventObject} e
     */
    onAfterEdit: function(e) {
        var record = this.store.getAt(e.row);
        if (e.field == 'clientValue') {
            record.set('finalValue', e.value);
        }
        this.stopEditing();
    },
    
    /**
     * adopt final value to the one selected
     * 
     * @param {Ext.grid.EditorGridPanel} grid
     * @param {number} rowIndex
     * @param {number} colIndex
     * @param {Ext.EventObject} e
     */
    onCellClick: function(grid, rowIndex, colIndex, e) {
        var dataIndex = this.getColumnModel().getDataIndex(colIndex),
            resolveRecord = this.store.getAt(rowIndex);

        if (resolveRecord && dataIndex && dataIndex.match(/clientValue|value\d+/)) {
            if (e.shiftKey && Ext.isString(resolveRecord.get(dataIndex))) {
                // combine string values if shift is pressed
                var record = this.store.getAt(rowIndex),
                    combined = record.get('clientValue') + ' / ' + record.get('value0'),
                    colToSelect = (colIndex == 2) ? 3 : 2,
                    otherCellEl = this.getView().getCell(rowIndex, colToSelect);

                if (otherCellEl) {
                    // TODO it would be nicer if this worked ...
                    //Ext.fly(otherCellEl).addClass('x-grid3-cell-selected');
                    Ext.fly(otherCellEl).highlight();
                }

                resolveRecord.set('finalValue', combined);
            } else {
                resolveRecord.set('finalValue', resolveRecord.get(dataIndex));
            }

            var celEl = this.getView().getCell(rowIndex, this.getColumnModel().getIndexById('finalValue'));
            if (celEl) {
                Ext.fly(celEl).highlight();
            }
        }
    },

    /**
     * called when the store got new data
     */
    onStoreLoad: function() {
        var strategy = this.store.resolveStrategy;

        this.actionCombo.setValue(strategy);
        this.applyStrategy(strategy);
    },

    /**
     * select handler of action combo
     */
    onActionSelect: function(combo, record, idx) {
        var strategy = record.get('value');

        this.applyStrategy(strategy);
        this.store.applyStrategy(strategy);
    },

    /**
     * apply an action (generate final data)
     * - mergeTheirs:   merge keep existing values (discards client record)
     * - mergeMine:     merge, keep client values (discards client record)
     * - discard:       discard client record
     * - keep:          keep client record (create duplicate)
     * 
     * @param {Ext.data.Store} store with field records (DuplicateResolveModel)
     * @param {Sting} strategy
     */
    applyStrategy: function(strategy) {
        var cm = this.getColumnModel(),
            view = this.getView();

        if (cm) {
            cm.setHidden(cm.getIndexById('clientValue'), strategy == 'discard');
            cm.setHidden(cm.getIndexById('finalValue'), strategy == 'keep');

            if (view && view.grid) {
                this.getView().refresh();
            }
        }
    },
    
    /**
     * init our column model
     */
    initColumnModel: function() {
        var valueRendererDelegate = this.valueRenderer.createDelegate(this);

        this.cm = new Ext.grid.ColumnModel([{
            header: i18n._('Field Group'),
            width:50, 
            sortable: true, 
            dataIndex:'group', 
            id: 'group', 
            menuDisabled:true
        }, {
            header: i18n._('Field Name'),
            width:50, 
            sortable: true, 
            dataIndex:'i18nFieldName', 
            id: 'i18nFieldName', 
            menuDisabled:true
        }, {
            header: i18n._('My Value'),
            width:50, 
            resizable:false, 
            dataIndex: 'clientValue', 
            id: 'clientValue', 
            menuDisabled:true, 
            renderer: valueRendererDelegate
        }, {
            header: i18n._('Existing Value'),
            width:50, 
            resizable:false, 
            dataIndex: 'value' + this.store.duplicateIdx, 
            id: 'value' + this.store.duplicateIdx, 
            menuDisabled:true, 
            renderer: valueRendererDelegate
        }, {
            header: i18n._('Final Value'),
            width:50, 
            resizable:false, 
            dataIndex: 'finalValue', 
            id: 'finalValue', 
            menuDisabled:true,
            renderer: valueRendererDelegate,
            editable: true
        }]);
    },

    /**
     * init the toolbar
     */
    initToolbar: function() {
        this.tbar = [{
            xtype: 'label',
            text: i18n._('Action:') + ' '
        }, {
            xtype: 'combo',
            ref: '../actionCombo',
            typeAhead: true,
            width: 350,
            triggerAction: 'all',
            lazyRender:true,
            mode: 'local',
            valueField: 'value',
            displayField: 'text',
            value: this.store.resolveStrategy,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: ['value', 'text'],
                data: [
                    ['mergeTheirs', i18n._('Merge, keeping existing details')],
                    ['mergeMine',   i18n._('Merge, keeping my details')],
                    ['discard',     i18n._('Keep existing record and discard mine')],
                    ['keep',        i18n._('Keep both records')]
                ]
            }),
            listeners: {
                scope: this, 
                select: this.onActionSelect
            }
        }];
    },

    /**
     * interceptor for all renderers
     * - manage colors
     * - pick appropriate renderer
     */
    valueRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        var fieldName = record.get('fieldName'),
            dataIndex = this.getColumnModel().getDataIndex(colIndex),
            cfValue = arguments[0],
            renderer = Tine.widgets.grid.RendererManager.get(this.app, this.store.recordClass, fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);

        // color management
        if (dataIndex && dataIndex.match(/clientValue|value\d+/) && !this.store.resolveStrategy.match(/(keep|discard)/)) {
            var action = record.get('finalValue') == value ? 'keep' : 'discard';
            metaData.css = 'tine-duplicateresolve-' + action + 'value';
        }

        if(String(fieldName).match(/^#.+/)) {
            arguments[0] = {};
            arguments[0][fieldName.replace(/^#/, '')] = cfValue;
        }

        metaData.css = metaData.css + ' tine-dublicateResolve-cell';

        return renderer.apply(this, arguments);
    }
});

/**
 * @class Tine.widgets.dialog.DuplicateResolveModel
 * A specific {@link Ext.data.Record} type that represents a field/clientValue/doublicateValues/finalValue set and is made to work with the
 * {@link Tine.widgets.dialog.DuplicateResolveGridPanel}.
 * @constructor
 */
Tine.widgets.dialog.DuplicateResolveModel = Ext.data.Record.create([
    {name: 'fieldName', type: 'string'},
    {name: 'fieldDef', type: 'fieldDef'},
    {name: 'group', type: 'string'},
    {name: 'i18nFieldName', type: 'string'},
    'clientValue', 'value0' , 'value1' , 'value2' , 'value3' , 'value4', 'finalValue'
]);

Tine.widgets.dialog.DuplicateResolveStore = Ext.extend(Ext.data.GroupingStore, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,

    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,

    /**
     * @cfg {Ext.data.DataProxy} recordProxy
     */
    recordProxy: null,

    /**
     * @cfg {Object/Record} clientRecord
     */
    clientRecord: null,

    /**
     * @cfg {Array} duplicates
     * array of Objects or Records
     */
    duplicates: null,

    /**
     * @cfg {String} resolveStrategy
     * default resolve action
     */
    resolveStrategy: null,

    /**
     * @cfg {String} defaultResolveStrategy
     * default resolve action
     */
    defaultResolveStrategy: 'mergeTheirs',

    // private config overrides
    idProperty: 'fieldName',
    fields: Tine.widgets.dialog.DuplicateResolveModel,

    groupField: 'group',
//    groupOnSort: true,
//    remoteGroup: false,
    sortInfo: {field: 'group', oder: 'ASC'},

    constructor: function(config) {
        var initialData = config.data;
        delete config.data;

        this.reader = new Ext.data.JsonReader({
            idProperty: this.idProperty,
            fields: this.fields
        });

        Tine.widgets.dialog.DuplicateResolveStore.superclass.constructor.apply(this, arguments);

        if (! this.recordProxy && this.recordClass) {
            this.recordProxy = new Tine.Tinebase.data.RecordProxy({
                recordClass: this.recordClass
            });
        }

        // forece duplicate 0 atm.
        this.duplicateIdx = 0;

        if (initialData) {
            this.loadData(initialData);
        }
    },

    loadData: function(data, resolveStrategy, finalRecord) {
        // init records
        this.clientRecord = this.createRecord(data.clientRecord);

        this.duplicates = data.duplicates;
        Ext.each([].concat(this.duplicates), function(duplicate, idx) {this.duplicates[idx] = this.createRecord(this.duplicates[idx]);}, this);

        this.resolveStrategy = resolveStrategy || this.defaultResolveStrategy;

        if (finalRecord) {
            finalRecord = this.createRecord(finalRecord);
        }

        var fieldDefinitions = this.recordClass.getFieldDefinitions(),
            cfDefinitions = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, this.recordClass, true);

        var recordsToAdd = [];
        Ext.each(fieldDefinitions.concat(cfDefinitions), function(field) {
            
            if (field.omitDuplicateResolving) {
                return;
            }

            var fieldName = field.name,
                fieldGroup = field.uiconfig ? field.uiconfig.group : field.group,
                recordData = {
                    fieldName: fieldName,
                    fieldDef: field,
                    i18nFieldName: field.label ? this.app.i18n._hidden(field.label) : this.app.i18n._hidden(fieldName),
                    clientValue: Tine.Tinebase.common.assertComparable(this.clientRecord.get(fieldName))
                };

            recordData.group = fieldGroup ? this.app.i18n._hidden(fieldGroup) : recordData.i18nFieldName;
            Ext.each([].concat(this.duplicates), function(duplicate, idx) {recordData['value' + idx] =  Tine.Tinebase.common.assertComparable(this.duplicates[idx].get(fieldName));}, this);

            var record = new Tine.widgets.dialog.DuplicateResolveModel(recordData, fieldName);

            if (finalRecord) {
                if (finalRecord.modified && finalRecord.modified.hasOwnProperty(fieldName)) {
//                    Tine.log.debug('Tine.widgets.dialog.DuplicateResolveStore::loadData ' + fieldName + 'changed from  ' + finalRecord.modified[fieldName] + ' to ' + finalRecord.get(fieldName));
                    record.set('finalValue', finalRecord.modified[fieldName]);

                }

                record.set('finalValue', finalRecord.get(fieldName));
            }

            recordsToAdd.push(record);
        }, this);

        this.insert(0, recordsToAdd);
        
        if (! finalRecord) {
            this.applyStrategy(this.resolveStrategy);
        }

        this.sortData();
        this.fireEvent('load', this);
    },

    /**
     * custom sorter
     * 
     * @param {String} f (ignored atm.)
     * @param {String} direction
     */
    sortData: function(f, direction) {
        direction = direction || 'ASC';
        var groupConflictScore = {};
            
        this.each(function(r) {
            var group = r.get('group'),
                myValue = String(r.get('clientValue')).replace(/^undefined$|^null$|^\[\]$/, ''),
                theirValue = String(r.get('value' + this.duplicateIdx)).replace(/^undefined$|^null$|^\[\]$/, '');
            
            if (! groupConflictScore.hasOwnProperty(group)) {
                groupConflictScore[group] = 990;
            }
            
            if (myValue || theirValue) {
                groupConflictScore[group] -= 1;
            }
            
            if (myValue != theirValue) {
                groupConflictScore[group] -= 10;
            }
            
        }, this);
        
        this.data.sort('ASC', function(r1, r2) {
            var g1 = r1.get('group'),
                v1 = String(groupConflictScore[g1]) + g1,
                g2 = r2.get('group'),
                v2 = String(groupConflictScore[g2]) + g2;
                
            return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
        });
    },

    /**
     * apply an strategy (generate final data)
     * - mergeTheirs:   merge keep existing values (discards client record)
     * - mergeMine:     merge, keep client values (discards client record)
     * - discard:       discard client record
     * - keep:          keep client record (create duplicate)
     * 
     * @param {Sting} strategy
     */
    applyStrategy: function(strategy) {
        Tine.log.debug('Tine.widgets.dialog.DuplicateResolveStore::applyStrategy() - action: ' + strategy);
        
        this.resolveStrategy = strategy;
        this.checkEditGrant();

        this.each(function(resolveRecord) {
            var theirs = resolveRecord.get('value' + this.duplicateIdx),
                mine = resolveRecord.get('clientValue'),
                location = this.resolveStrategy === 'keep' ? 'mine' : 'theirs';

            // undefined or empty theirs value -> keep mine
            if (this.resolveStrategy == 'mergeTheirs' && ['', 'null', 'undefined', '[]'].indexOf(String(theirs)) > -1) {
                location = 'mine';
            }

            // only keep mine if its not undefined or empty
            if (this.resolveStrategy == 'mergeMine' && ['', 'null', 'undefined', '[]'].indexOf(String(mine)) < 0) {
                location = 'mine';
            }

            // special merge for tags
            if (resolveRecord.get('fieldName') == 'tags') {
                resolveRecord.set('finalValue', this.mergeTags(mine, theirs));
            } else {
                resolveRecord.set('finalValue', location === 'mine' ? mine : theirs);
            }
            
            Tine.log.debug('Tine.widgets.dialog.DuplicateResolveStore::applyStrategy() - resolved record field: ' + resolveRecord.get('fieldName'));
            Tine.log.debug(resolveRecord);
        }, this);
        
        this.commitChanges();
    },

    /**
     * merge tags
     *
     * @param {Array} mine
     * @param {Array} theirs
     *
     * TODO generalize me for "merge-able" values
     */
    mergeTags: function(mine, theirs) {
        var result = [],
            records = Tine.Tinebase.common.assertComparable([].concat(this.resolveStrategy != 'discard' ? mine : []).concat(this.resolveStrategy != 'keep' ? theirs : [])),
            recordIds = [];

        // remove duplicates (TODO should be simplified)
        Ext.each(records, function(value) {
            if (value && recordIds.indexOf(value['id']) < 0) {
                result.push(value);
                recordIds.push(value['id']);
            }
        });

        return result;
    },
    
    checkEditGrant: function() {
        var grant = ! this.recordClass.getMeta('containerProperty') ? true : this.duplicates[this.duplicateIdx].get('container_id') ? this.duplicates[this.duplicateIdx].get(this.recordClass.getMeta('containerProperty')).account_grants['editGrant'] : false;

        // change strategy from merge to keep if user has no rights to merge
        if (this.resolveStrategy.match(/^merge/) && ! grant) {
            Tine.log.info('Tine.widgets.dialog.DuplicateResolveStore::checkEditGrant() - user has no editGrant, changing strategy to keep');
            this.resolveStrategy = 'keep';
            this.fireEvent('strategychange', this, this.resolveStrategy);
        }
    },

    /**
     * returns record with conflict resolved data
     */
    getResolvedRecord: function() {
        var record = (this.resolveStrategy == 'keep' ? this.clientRecord : this.duplicates[this.duplicateIdx]).copy();

        this.each(function(resolveRecord) {
            var fieldName = resolveRecord.get('fieldName'),
                finalValue = resolveRecord.get('finalValue'),
                modified = resolveRecord.modified || {};

            // also record changes
            if (modified.hasOwnProperty('finalValue')) {
                Tine.log.debug('Tine.widgets.dialog.DuplicateResolveStore::getResolvedRecord ' + fieldName + ' changed from ' + modified.finalValue + ' to ' + finalValue);
                record.set(fieldName, Tine.Tinebase.common.assertComparable(modified.finalValue));
            }

            record.set(fieldName, Tine.Tinebase.common.assertComparable(finalValue));

        }, this);

        Tine.log.debug('Tine.widgets.dialog.DuplicateResolveStore::getResolvedRecord() resolved record:');
        Tine.log.debug(record);
        
        return record;
    },

    /**
     * create record from data
     * 
     * @param {Object} data
     * @return {Record}
     */
    createRecord: function(data) {
        return Ext.isFunction(data.beginEdit) ? data : this.recordProxy.recordReader({responseText: Ext.encode(data)});
    }
});
