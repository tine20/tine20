/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @cfg {String} resolveAction
     * default resolve action
     */
    resolveAction: 'mergeTheirs',
    
    // private config overrides
    layout: 'fit',
    enableColumnMove: false,
    stripeRows: true,
    trackMouseOver: false,
    clicksToEdit:1,
    enableHdMenu : false,
    viewConfig : {
        forceFit:true
    },
    
    initComponent: function() {
//        this.addEvents();
        
        // init records
        this.clientRecord = this.createRecord(this.clientRecord);
        Ext.each([].concat(this.duplicates.results), function(duplicate, idx) {this.duplicates.results[idx] = this.createRecord(this.duplicates.results[idx])}, this);
        
        this.initStore();
        
        // select one duplicate (one of the up to five duplicates we allow to edit)
        this.duplicateIdx = 0;
        this.applyAction(this.store, this.resolveAction);
        
        this.initColumnModel();
        
        this.tbar = [{
            xtype: 'label',
            text: _('Action:') + ' '
        }, {
            xtype: 'combo',
            ref: '../actionCombo',
            typeAhead: true,
            width: 250,
            triggerAction: 'all',
            lazyRender:true,
            mode: 'local',
            valueField: 'value',
            displayField: 'text',
            value: this.resolveAction,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: ['value', 'text'],
                data: [
                    ['mergeTheirs', _('Merge, keeping existing details')],
                    ['mergeMine',   _('Merge, keeping my details')],
                    ['discard',     _('Keep existing record and discard mine')],
                    ['keep',        _('Keep both records')]
                ]
            }),
            listeners: {
                scope: this, 
                select: this.onActionSelect
            }
        }/*, {
            text: _('Apply'),
            scope: this,
            handler: this.onApplyAction
        }*/];
        
        this.on('cellclick', this.onCellClick, this);
        Tine.widgets.dialog.DuplicateResolveGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init out store of resoveRecords
     */
    initStore: function() {
        this.store = new Ext.data.JsonStore({
            idProperty: 'fieldName',
            fields: Tine.widgets.dialog.DuplicateResolveModel
        });
        
        // @TODO sort conflict fileds first 
        //   - group fields (contact org, home / phones etc.)
        // @TODO add customfields
        Ext.each(this.recordClass.getFieldDefinitions(), function(field) {
            if (field.isMetaField || field.ommitDuplicateResolveing) return;
            
            var fieldName = field.name,
                recordData = {
                    fieldName: fieldName,
                    i18nFieldName: field.label ? this.app.i18n._hidden(field.label) : this.app.i18n._hidden(fieldName),
                    clientValue: Tine.Tinebase.common.assertComparable(this.clientRecord.get(fieldName))
                };
            
            Ext.each([].concat(this.duplicates.results), function(duplicate, idx) {recordData['value' + idx] =  Tine.Tinebase.common.assertComparable(this.duplicates.results[idx].get(fieldName))}, this);
            
            this.store.addSorted(new Tine.widgets.dialog.DuplicateResolveModel(recordData, fieldName));
        }, this);
        
    },
    
    /**
     * adopt final value to the one selected
     */
    onCellClick: function(grid, rowIndex, colIndex, e) {
        var dataIndex = this.getColumnModel().getDataIndex(colIndex),
            resolveRecord = this.store.getAt(rowIndex);
        
        if (resolveRecord && dataIndex && dataIndex.match(/clientValue|value\d+/)) {
            resolveRecord.set('finalValue', resolveRecord.get(dataIndex));
            
            var celEl = this.getView().getCell(rowIndex, this.getColumnModel().getIndexById('finalValue'));
            if (celEl) {
                Ext.fly(celEl).highlight();
            }
        }
    },
    
    /**
     * handler of apply button
     */
    onApplyAction: function() {
        this.applyAction(this.store, this.actionCombo.getValue());
    },
    
    /**
     * select handler of action combo
     */
    onActionSelect: function(combo, record, idx) {
        this.applyAction(this.store, record.get('value'));
    },
    
    /**
     * apply an action (generate final data)
     * - mergeTheirs:   merge keep existing values (discards client record)
     * - mergeMine:     merge, keep client values (discards client record)
     * - discard:       discard client record
     * - keep:          keep client record (create duplicate)
     * 
     * @param {Ext.data.Store} store with field records (DuplicateResolveModel)
     * @param {Sting} action
     */
    applyAction: function(store, action) {
        Tine.log.debug('Tine.widgets.dialog.DuplicateResolveGridPanel::applyAction action: ' + action);
        
        this.resolveAction = action;
        
        store.each(function(resolveRecord) {
            var theirs = resolveRecord.get('value' + this.duplicateIdx),
                mine = resolveRecord.get('clientValue'),
                location = action === 'keep' ? 'mine' : 'theirs';
            
            // undefined theirs value -> keep mine
            if (action == 'mergeTheirs' && String(theirs) === "undefined") {
                location = 'mine';
            }
            
            // only keep mine if its not undefined
            if (action == 'mergeMine' && String(mine) !== "undefined") {
                location = 'mine';
            }
            
            resolveRecord.set('finalValue', location === 'mine' ? mine : theirs);
        }, this);
        
        store.commitChanges();
        
        var cm = this.getColumnModel();
        if (cm) {
            cm.setHidden(cm.getIndexById('clientValue'), action == 'discard');
            cm.setHidden(cm.getIndexById('finalValue'), action == 'keep');
            
            this.getView().refresh();
        }
    },
    
    /**
     * init our column model
     */
    initColumnModel: function() {
        var valueRendererDelegate = this.valueRenderer.createDelegate(this);
        
        this.cm = new Ext.grid.ColumnModel([{
            header: _('Field Name'), 
            width:50, 
            sortable: true, 
            dataIndex:'i18nFieldName', 
            id: 'i18nFieldName', 
            menuDisabled:true
        }, {
            header: _('My Value'), 
            width:50, 
            resizable:false, 
            dataIndex: 'clientValue', 
            id: 'clientValue', 
            menuDisabled:true, 
            renderer: valueRendererDelegate
        }, {
            header: _('Existing Value'), 
            width:50, 
            resizable:false, 
            dataIndex: 'value' + this.duplicateIdx, 
            id: 'value' + this.duplicateIdx, 
            menuDisabled:true, 
            renderer: valueRendererDelegate
        }, {
            header: _('Final Value'), 
            width:50, 
            resizable:false, 
            dataIndex: 'finalValue', 
            id: 'finalValue', 
            menuDisabled:true, 
            renderer: valueRendererDelegate
        }]);
        
    },
    
    /**
     * interceptor for all renderers
     * - manage colors
     * - pick appropriate renderer
     */
    valueRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        var fieldName = record.get('fieldName'),
            dataIndex = this.getColumnModel().getDataIndex(colIndex),
            renderer = Tine.widgets.grid.RendererManager.get(this.app, this.recordClass, fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
        
        try {
            // color management
            if (dataIndex && dataIndex.match(/clientValue|value\d+/) && !this.resolveAction.match(/(keep|discard)/)) {
                
                var action = record.get('finalValue') == value ? 'keep' : 'discard';
                metaData.css = 'tine-duplicateresolve-' + action + 'value';
//                metaData.css = 'tine-duplicateresolve-adoptedvalue';
            }
            
            return renderer.apply(this, arguments);
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.DuplicateResolveGridPanel::valueRenderer');
            Tine.log.err(e.stack ? e.stack : e);
        }
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

/**
 * @class Tine.widgets.dialog.DuplicateResolveModel
 * A specific {@link Ext.data.Record} type that represents a field/clientValue/doublicateValues/finalValue set and is made to work with the
 * {@link Tine.widgets.dialog.DuplicateResolveGridPanel}.
 * @constructor
 */
Tine.widgets.dialog.DuplicateResolveModel = Ext.data.Record.create([
    {name: 'fieldName', type: 'string'},
    {name: 'i18nFieldName', type: 'string'},
    'clientValue', 'value0' , 'value1' , 'value2' , 'value3' , 'value4', 'finalValue'
]);