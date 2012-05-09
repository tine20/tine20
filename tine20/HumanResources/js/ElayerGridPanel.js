/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Employee Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeEditDialog
 */

Tine.HumanResources.ElayerGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    
    /* private */
    
    /**
     * 
     * @type Ext.data.store 
     */
    store: null,
    recordClass: Tine.HumanResources.Model.Elayer,
    
    /* config */
    
    height: 100,
    
    /* public */
    
    app: null,
    record: null,
    editDialog: null,
    
    initComponent: function() {
        this.initStore();
        this.colModel = this.getColumnModel();
        this.sm = new Ext.grid.RowSelectionModel({singleSelect:true}),
        
        Tine.HumanResources.ElayerGridPanel.superclass.initComponent.call(this);
    },
    
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                width: 120,
                sortable: true
            },
            columns: [
                {id: 'start_date', type: 'date', header: this.app.i18n._('Start Date')},
                {id: 'end_date', type: 'date', header: this.app.i18n._('End Date')},
                {id: 'vacation_days', type: 'int', header: this.app.i18n._('Vacation Days')},
                {id: 'cost_centre', type: 'string', header: this.app.i18n._('Cost Centre')},
                {id: 'working_hours', type: 'int', header: this.app.i18n._('Working Hours')}
                ]
        })
    },
    
    initStore: function() {
        this.store = new Tine.Tinebase.data.RecordStore({
            recordClass: this.recordClass
        });
    }
    
});