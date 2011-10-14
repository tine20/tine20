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
        Ext.each([].concat(this.duplicates), function(duplicate, idx) {this.duplicates[idx] = this.createRecord(this.duplicates[idx])}, this);
        
        this.initStore();
        
        // select one duplicate (one of the up to five duplicates we allow to edit)
        this.duplicateIdx = 0;
        
        this.initColumnModel();
        
        Tine.widgets.dialog.DuplicateResolveGridPanel.superclass.initComponent.call(this);
    },
    
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
                    clientValue: this.clientRecord.get(fieldName)
                };
            
            Ext.each([].concat(this.duplicates), function(duplicate, idx) {recordData['value' + idx] =  this.duplicates[idx].get(fieldName)}, this);
            
            this.store.addSorted(new Tine.widgets.dialog.DuplicateResolveModel(recordData, fieldName));
        }, this);
    },
    
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
    
    valueRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        var fieldName = record.get('fieldName'),
            renderer = Tine.widgets.grid.RendererManager.get(this.app, this.recordClass, fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
        
        try {
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
 * @class   Tine.widgets.dialog.DuplicateResolveColumnModel
 * @extends Ext.grid.ColumnModel
 * A custom column model for the {@link Tine.widgets.dialog.DuplicateResolveGridPanel}.  Generally it should not need to be used directly.
 * @constructor
 * @param {Object} config
 */
Tine.widgets.dialog.DuplicateResolveColumnModel = Ext.extend(Ext.grid.ColumnModel, {
    constructor : function(grid, config) {
        Ext.apply(this, config);
        
        this.grid = grid;
        var valueRendererDelegate = this.valueRenderer.createDelegate(this);
        
        Tine.widgets.dialog.DuplicateResolveColumnModel.superclass.constructor.call(this, [{
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
    
//    valueRendererDelegate: function(value, metaData, record, rowIndex, colIndex, store) {
//        var fieldName = 
//    }
//    
//    getRenderer: function() {
//    
//        var bfield = new f.Field({
//            autoCreate: {tag: 'select', children: [
//                {tag: 'option', value: 'true', html: this.trueText},
//                {tag: 'option', value: 'false', html: this.falseText}
//            ]},
//            getValue : function(){
//                return this.el.dom.value == 'true';
//            }
//        });
//        this.editors = {
//            'date' : new g.GridEditor(new f.DateField({selectOnFocus:true})),
//            'string' : new g.GridEditor(new f.TextField({selectOnFocus:true})),
//            'number' : new g.GridEditor(new f.NumberField({selectOnFocus:true, style:'text-align:left;'})),
//            'boolean' : new g.GridEditor(bfield, {
//                autoSize: 'both'
//            })
//        };
//        this.renderCellDelegate = this.renderCell.createDelegate(this);
//        this.renderPropDelegate = this.renderProp.createDelegate(this);
//    }
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