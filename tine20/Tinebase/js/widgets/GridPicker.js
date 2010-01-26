/**
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * includes the contact search and picker panel and a small contacts grid
 * 
 * @todo generalise for more different object types
 * @todo add translations
 * 
 * @deprecated -> replaced by contact search combobox
 * @todo remove it!
 */
 
Ext.namespace('Tine.widgets');
Tine.widgets.GridPicker = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Int} pickerWidth
     */
    pickerWidth: 200,
    
    /**
     * @cfg pickerType one of 'contact', ...
     * 
     * @todo more to add
     */
    pickerType: 'contact',
    
    /**
     * @cfg{String} pickerTypeDefault - default: 'contact'
     */
    pickerTypeDefault: 'contact',    

    /**
     * @cfg{String} autoExpand columnn - default: 'n_fileas'
     */
    autoExpand: 'n_fileas',    
    
    /**
     * @cfg {String} title for the record list
     */
    recordListTitle: '',
    
    /**
     * @cfg {Ext.data.JsonStore} gridStore
     */
    gridStore: null,
    
    /**
     * @cfg {Ext.grid.ColumnModel} columnModel
     */
    columnModel: null,    
    
    /**
     * @cfg {array} bbarItems
     */
    bbarItems: [],

    /**
     * @cfg {Array} Array of column's config objects where the config options are in
     */
    configColumns: [],
    
    picker: null,
    gridPanel: null,
    
    layout: 'border',
    border: false,

    /**
     * @private
     */
    initComponent: function(){
        this.action_removeRecord = new Ext.Action({
            text: _('remove record'),
            disabled: true,
            scope: this,
            handler: this.removeRecord,
            iconCls: 'action_deleteContact'
        });
    	
        this.gridStore.sort(this.recordPrefix + 'name', 'asc');
                
        /* picker */
        this.picker = new Tine.widgets.PickerPanel({
            selectType: this.pickerType,
            selectTypeDefault: this.pickerTypeDefault,
            enableBbar: true
        });
        this.picker.on('recorddblclick', function(record){
            this.addRecord(record);   
        }, this);
                
        /* selection model */
        var rowSelectionModel = new Ext.grid.RowSelectionModel({
            multiSelect:true
        });
        
        rowSelectionModel.on('selectionchange', function(selectionModel) {
            this.action_removeRecord.setDisabled(selectionModel.getCount() < 1);
        }, this);
        
        // add bbarItems
        // @todo use each() here?
        var gridBbarItems = [ this.action_removeRecord ];
        gridBbarItems.push(this.bbarItems);
                
        /* grid panel */
        this.gridPanel = new Ext.grid.EditorGridPanel({
            title: this.recordListTitle,
            store: this.gridStore,
            cm: this.columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            plugins: this.configColumns,
            autoExpandColumn: this.autoExpand,
            bbar: gridBbarItems,
            border: false
        });
        
        this.items = this.getGridLayout();
        
        Tine.widgets.GridPicker.superclass.initComponent.call(this);
        /*
        this.on('afterlayout', function(container){
            var height = container.ownerCt.getSize().height;
            this.setHeight(height);
            this.items.each(function(item){
                item.setHeight(height);
            });
        },this);
        */
    },
    
    /**
     * @private Layout
     */
    getGridLayout: function() {
        return [{
            layout: 'fit',
            region: 'west',
            border: false,
            split: true,
            width: this.pickerWidth,
            items: this.picker
        },{
            layout: 'fit',
            region: 'center',
            border: false,
            items: this.gridPanel
        }];
    },
    
    /**
     * add given record to this.configStore
     * 
     * @param {Tine.model.record}
     */
    addRecord: function(record) {
        var recordIndex = this.getRecordIndex(record);
        if (recordIndex === -1) {
            var newRecord = {};
            newRecord = record.data.data;
            newRecord.relation_type = 'customer';
            
            var newData = [newRecord];
        	            
        	//console.log(newData);
        	
        	this.gridStore.loadData(newData, true);
        	
        	//console.log(this.gridStore);
        }
        this.gridPanel.getSelectionModel().selectRow(this.getRecordIndex(record));
    },
    
    /**
     * removes currently in this.configGridPanel selected rows
     */    
    removeRecord: function() {
        var selectedRows = this.gridPanel.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.gridStore.remove(selectedRows[i]);
        }
    },
    
    /**
     * returns index of given record in this.configStore
     * 
     * @param {Ext.data.Record}
     */
    getRecordIndex: function(record) {

    	return id ? this.gridStore.indexOfId(record.data.id) : false;
    	
    	/*
        var id = false;
        this.configStore.each(function(item){
            if ( item.data[this.recordPrefix + 'type'] == 'user' && record.data.type == 'user' &&
                    item.data[this.recordPrefix + 'id'] == record.data.id) {
                id = item.id;
            } else if (item.data[this.recordPrefix + 'type'] == 'group' && record.data.type == 'group' &&
                    item.data[this.recordPrefix + 'id'] == record.data.id) {
                id = item.id;
            }
        }, this);
        return id ? this.configStore.indexOfId(id) : false;
        */
    }
    
});

/**
 * Record picker panel
 * 
 * @class Tine.widgets.PickerPanel
 * @package Tinebase
 * @subpackage Widgets
 * @extends Ext.TabPanel
 * 
 * <p> This widget supplies a picker panel to be used in related widgets.</p>
 */
 
Tine.widgets.PickerPanel = Ext.extend(Ext.TabPanel, {
    /**
     * @cfg {String} one of 'contact'
     * selectType
     */
    selectType: 'contact',
    
    /**
     * @cfg {Ext.Action}
     * selectAction
     */
    selectAction: false,

    /**
     * @cfg {bool}
     * multiSelect
     */
    multiSelect: false,
    
    /**
     * @cfg {bool}
     * enable bottom toolbar
     */
    enableBbar: true,
    
    /**
     * @cfg {Ext.Toolbar}
     * optional bottom bar, defaults to 'add record' which fires 'recorddblclick' event
     */ 
    bbar: null,
        
    /**
     * @cfg {string}
     * request method
     */ 
    requestMethod: 'Addressbook.searchContacts',

    /**
     * @cfg {string}
     * request sort and display field
     */ 
    displayField: 'n_fileas',
    
    activeTab: 0,
    defaults:{autoScroll:true},
    border: true,
    split: true,
    width: 300,
    height: 300,
    collapsible: false,
    
    //private
    initComponent: function(){
        this.addEvents(
            /**
             * @event recorddblclick
             * Fires when an record is dbl clicked
             * @param {Ext.Record} dbl clicked record
             */
            'recorddblclick',
            /**
             * @event recordselectionchange
             * Fires when record selection changes
             * @param {Ext.Record} dbl clicked record or undefined if none
             */
            'recordselectionchange'
        );
        
        this.actions = {
            addRecord: new Ext.Action({
                text: 'add record',
                disabled: false,
                scope: this,
                handler: function(){
                    var record = this.searchPanel.getSelectionModel().getSelected();
                    this.fireEvent('recorddblclick', record);
                },
                // @todo add the right icon
                iconCls: 'action_addContact'
            })
        };

        this.ugStore = new Ext.data.SimpleStore({
            //fields: this.recordModel
        	fields: Tine.Tinebase.PickerRecord
        });
        
        this.ugStore.setDefaultSort(this.displayField, 'asc');
        
        // get search data
        this.loadData = function() {
            var searchString = Ext.getCmp('Tinebase_Records_SearchField').getRawValue();
            
            if (this.requestParams && this.requestParams.filter.query == searchString || searchString.length < 1) {
                return;
            }
            this.requestParams = { 
                method: this.requestMethod,
                filter: [{
                    operator: 'contains',
                    field: 'query',
                    value: searchString
                }, {
                    field: 'containerType', 
                    operator: 'equals', 
                    value: 'all' 
                }],
                paging: {
                    dir: 'asc', 
                    start: 0, 
                    limit: 50,
                    sort: this.displayField
                } 
            };

            Ext.getCmp('Tinebase_Records_Grid').getStore().removeAll();
                        
            Ext.Ajax.request({
                params: this.requestParams,
                success: function(response, options){
                    var data = Ext.util.JSON.decode(response.responseText);
                    var toLoad = [];
                    for (var i=0; i<data.results.length; i++){                        
                        var item = (data.results[i]);
                        toLoad.push(new Tine.Tinebase.PickerRecord({
                            id: item.id,
                            name: item.n_fileas,
                            data: item
                        }));
                    }
                    if (toLoad.length > 0) {
                        var grid = Ext.getCmp('Tinebase_Records_Grid');
                        
                        grid.getStore().add(toLoad);
                        
                        //console.log(grid.getStore());
                        
                        // select first result and focus row
                        grid.getSelectionModel().selectFirstRow();                                
                        grid.getView().focusRow(0);
                    }
                }
            });
        };
        
        var columnModel = new Ext.grid.ColumnModel([
            {
                resizable: false,
                sortable: false, 
                id: 'name', 
                header: 'Name', 
                dataIndex: 'name', 
                width: 70
            }
        ]);
        
        //columnModel.defaultSortable = true; // by default columns are sortable
        
        this.quickSearchField = new Ext.ux.SearchField({
            id: 'Tinebase_Records_SearchField',
            emptyText: _('enter searchfilter')
        }); 
        this.quickSearchField.on('change', function(){
            this.loadData();
        }, this);
                
        this.Toolbar = new Ext.Toolbar({
            items: [
                this.quickSearchField
            ]
        });
        
        if (this.enableBbar && !this.bbar) {
            this.bbar = new Ext.Toolbar({
                items: [this.actions.addRecord]
            });
        }
        
        //console.log(this.bbar);

        this.searchPanel = new Ext.grid.GridPanel({
            title: _('Search'),
            id: 'Tinebase_Records_Grid',
            store: this.ugStore,
            cm: columnModel,
            enableColumnHide:false,
            enableColumnMove:false,
            autoSizeColumns: false,
            selModel: new Ext.grid.RowSelectionModel({multiSelect:this.multiSelect}),
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'name',
            tbar: this.Toolbar,
            //bbar: this.bbar,
            border: false
        });
        
        this.searchPanel.on('rowdblclick', function(grid, row, event) {
            var record = this.searchPanel.getSelectionModel().getSelected();
            this.fireEvent('recorddblclick', record);
        }, this);
        
        // on keypressed("enter") event to add record
        this.searchPanel.on('keydown', function(event){
             if(event.getKey() == event.ENTER){
                var record = this.searchPanel.getSelectionModel().getSelected();
                this.fireEvent('recorddblclick', record);
             }
        }, this);
        
        /*
        this.searchPanel.getSelectionModel().on('selectionchange', function(sm){
            var record = sm.getSelected();
            this.actions.addRecord.setDisabled(!record);
            this.fireEvent('recordselectionchange', record);
        }, this);
        */
        
        this.items = [this.searchPanel, {
           title: _('Browse'),
           html: _('Browse'),
           disabled: true
        }];
        
        Tine.widgets.PickerPanel.superclass.initComponent.call(this);
        /*
        this.on('resize', function(){
            this.quickSearchField.setWidth(this.getSize().width - 3);
        }, this);
        */
    }
});