/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Admin.customfield');


/**
 * Customfield grid panel
 * 
 * @namespace   Tine.Admin.customfield
 * @class       Tine.Admin.customfield.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.customfield.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    /**
     * @cfg
     */
    newRecordIcon: 'admin-action-add-customfield',
    recordClass: Tine.Admin.Model.Customfield,
    recordProxy: Tine.Admin.customfieldBackend,
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    evalGrants: false,
    copyEditAction: true,
    gridConfig: {
        autoExpandColumn: 'name'
    },
    listenMessageBus: false,
    
    /**
     * initComponent
     */
    initComponent: function() {
        this.gridConfig.cm = this.getColumnModel();
        Tine.Admin.customfield.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                hidden: true,
                resizable: true
            },
            columns: this.getColumns()
        });
    },
    
    /**
     * returns columns
     * @private
     * @return Array
     */
    getColumns: function(){
        return [
            { header: this.app.i18n._('ID'), id: 'id', dataIndex: 'id', width: 50},
            { header: this.app.i18n._('Label'), id: 'label', dataIndex: 'definition', hidden: false, width: 100, renderer: this.labelRenderer.createDelegate(this)},
            { header: this.app.i18n._('Name'), id: 'name', dataIndex: 'name', hidden: false, width: 75},
            { header: this.app.i18n._('Type'), id: 'xtype', dataIndex: 'definition', hidden: false, width: 75, renderer: this.typeRenderer.createDelegate(this)},
            { header: this.app.i18n._('Application'), id: 'application_id', dataIndex: 'application_id', hidden: false, width: 100, renderer: this.appRenderer.createDelegate(this)},
            { header: this.app.i18n._('Model'), id: 'model', dataIndex: 'model', hidden: false, width: 100}
        ];
    },
    
    /**
     * returns label name
     * 
     * @param {Object} value
     * @return {String}
     */
    labelRenderer: function(value) {
        return this.app.i18n._(value.label);
    },
    
    /**
     * returns type name
     * 
     * @param {Object} value
     * @return {String}
     */
    typeRenderer: function(value) {
        return this.app.i18n._(value.type);
    },
    
    /**
     * returns application name
     * 
     * @param {Object} value
     * @return {String}
     */
    appRenderer: function(value) {
        return this.app.i18n._(value.name);
    },
       
    /**
     * initialises filter toolbar
     */
    initFilterPanel: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Customfield'),       field: 'query',    operators: ['contains']},
                {filtertype: 'admin.application', app: this.app}
            ],
            defaultFilter: 'query',
            filters: [
            ],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
    },
    
    /**
     * on update after edit
     * 
     * @param {String|Tine.Tinebase.data.Record} record
     */
    onUpdateRecord: function (record) {
        Tine.Admin.customfield.GridPanel.superclass.onUpdateRecord.apply(this, arguments);
        
        Tine.Tinebase.common.confirmApplicationRestart();
    },
    
    /**
     * do something after deletion of records
     * - reload the store
     * 
     * @param {Array} [ids]
     */
    onAfterDelete: function (ids) {
        Tine.Admin.customfield.GridPanel.superclass.onAfterDelete.apply(this, arguments);
        
        Tine.Tinebase.common.confirmApplicationRestart();
    },
    
    deleteRecords: function(sm, records) {
        // directly remove records from the store (only for non-filter-selection)
        if (Ext.isArray(records)) {
            Ext.each(records, function(record) {
                this.store.remove(record);
            });
            // if nested in an editDialog, just change the parent record
            if (this.editDialog) {
                var items = [];
                this.store.each(function(item) {
                    items.push(item.data);
                });
                this.editDialog.record.set(this.editDialogRecordProperty, items);
                this.editDialog.fireEvent('updateDependent');
                return;
            }
        }

        if (this.recordProxy) {
            if (this.usePagingToolbar) {
                this.pagingToolbar.refresh.disable();
            }

            var i18nItems = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), records.length),
                recordIds = [].concat(records).map(function(v){ return v.id; });

            this.deleteQueue = this.deleteQueue.concat(recordIds);

            var options = {
                scope: this,
                success: function() {
                    this.refreshAfterDelete(recordIds);
                    this.onAfterDelete(recordIds);
                },
                failure: function (exception) {
                    if (exception.code == 703) {
                        Ext.Msg.confirm(this.app.i18n._('This custom field is still in use!'),
                            this.app.i18n._('Are you sure you want to delete them?'),
                            function(btn) {
                                if (btn == 'yes') {
                                    this.recordProxy.deleteRecords(records, options, {'context': { 'skipUsageCheck': true }});
                                } else {
                                    this.refreshAfterDelete(recordIds);
                                    this.loadGridData();
                                }
                            }, this);
                    } else {
                        this.refreshAfterDelete(recordIds);
                        this.loadGridData();
                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                    }
                }
            };
        
        this.recordProxy.deleteRecords(records, options);
        }
    }
});
