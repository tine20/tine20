/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.account');
Tine.widgets.account.ConfigGrid = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Int} accountPickerWidth
     */
    accountPickerWidth: 200,
    /**
     * @cfg {Ext.data.JsonStore} configStore
     */
    configStore: null,
    /**
     * @cfg {String} property the account is stored in
     */
    accountProperty: 'accountId',
    /**
     * @cfg {Array} Array of column's config objects where the config options are in
     */
    configColumns: [],
    
    accountPicker: null,
    configGridPanel: null,
    
    layout: 'column',
    height: 200,
    border: true,

    /**
     * @private
     */
    initComponent: function(){
        this.action_removeAccount = new Ext.Action({
            text: 'remove account',
            disabled: true,
            scope: this,
            handler: null,//this.handlers.removeAccount,
            iconCls: 'action_deleteContact'
        });
        
        this.accountPicker = new Tine.widgets.account.PickerPanel({
            enableBbar: true
        });
        
        var columnModel = new Ext.grid.ColumnModel([{
                resizable: true, 
                id: this.accountProperty, 
                header: 'Name', 
                dataIndex: this.accountProperty, 
                renderer: Tine.Tinebase.Common.accountRenderer,
                width: 70
            }].concat(this.configColumns)
        );
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({
            multiSelect:true
        });
        
        rowSelectionModel.on('selectionchange', function(selectionModel) {
            this.action_removeAccount.setDisabled(selectionModel.getCount() < 1);
        }, this);
        

        this.configGridPanel = new Ext.grid.EditorGridPanel({
            title: 'Permissions',
            store: this.configStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            plugins: this.configColumns,
            autoExpandColumn: this.accountProperty,
            bbar: [this.action_removeAccount],
            border: false
        });
        
        this.items = this.getConfigGridLayout();
        Tine.widgets.account.ConfigGrid.superclass.initComponent.call(this);
        
        this.on('afterlayout', function(){
            var height = this.getEl().getSize().height;
            this.items.each(function(item){
                item.setHeight(height);
            });
        },this);
    },
    /**
     * @private Layout
     */
    getConfigGridLayout: function() {
        return [{
            layout: 'fit',
            width: this.accountPickerWidth,
            items: new Tine.widgets.account.PickerPanel({
                enableBbar: true
            })
        },{
            layout: 'fit',
            columnWidth: 1,
            items: this.configGridPanel
        }];
    }
});