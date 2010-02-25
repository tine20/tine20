/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager');

/**
 * Line Picker GridPanel
 * 
 * @namespace   Tine.Voipmanager
 * @class       Tine.Voipmanager.LineGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 * 
 * <p>Line Picker GridPanel</p>
 * <p><pre>
 * TODO         check max number of lines ?
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Voipmanager.LineGridPanel
 */
Tine.Voipmanager.LineGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    
    /**
     * 
     * @type tinebase app 
     */
    app: null,
    
    /**
     * 
     * @type Tine.Voipmanager.CallForwardPanel
     */
    cfPanel: null,
    
    /**
     * Tine record
     * @type 
     */
    activeRecord: null,
    
    /**
     * @type Tine.widgets.dialog.EditDialog
     */
    editDialog: null,
    
    /**
     * 
     * @cfg
     */
    clicksToEdit: 2,
    autoExpandColumn: 'name',
    // needed to select the first row after render!
    deferRowRender: false,
    
    /**
     * @private
     */
    initComponent: function() {
        
        this.recordClass = Tine.Voipmanager.Model.SnomLine;
        this.searchRecordClass = Tine.Voipmanager.Model.AsteriskSipPeer;

        this.cfPanel.on('change', this.onFieldChange, this);
        this.cfPanel.setDisabled(true);
        
        Tine.Voipmanager.LineGridPanel.superclass.initComponent.call(this);
        
        this.on('afterrender', this.onAfterRender, this);
    },

    /**
     * select first row after render
     */
    onAfterRender: function() {
        if (this.store.getCount() > 0) {
            this.getSelectionModel().selectFirstRow();
        }
    },
    
    /**
     * on call forward form field change: update store
     */
    onFieldChange: function() {
        //this.cfPanel.getForm().updateRecord(this.activeRecord);
        this.editDialog.getForm().updateRecord(this.activeRecord);
        this.getView().refresh();
    },
    
    /**
     * Return CSS class to apply to rows depending upon record state
     * 
     * @param {} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var result = '';
        if (record.dirty) {
            result = 'voipmanager-row-changed';
        }
        return result;
    },
    
    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function() {
        
        Tine.Voipmanager.LineGridPanel.superclass.initActionsAndToolbars.call(this);
        
        // only allow to add new lines from Voipmanager
        if (this.editDialog.recordProxy.appName == 'Voipmanager') {
            this.searchCombo = this.getSearchCombo();
    
            this.comboPanel = new Ext.Panel({
                layout: 'hfit',
                border: false,
                items: this.searchCombo,
                columnWidth: 1
            });
            
            this.tbar = new Ext.Toolbar({
                items: [
                    this.comboPanel
                ],
                layout: 'column'
            });
        }
    },
    
    /**
     * @param {Record} recordToAdd
     */
    onAddRecordFromCombo: function(recordToAdd) {
        
        var recordData = {
            asteriskline_id: recordToAdd.data,
            linenumber: this.recordStore.getCount()+1,
            lineactive: 1
        };
        var record = new this.newRecordClass(recordData);
        var fields = ['cfi_mode','cfi_number','cfb_mode','cfb_number','cfd_mode','cfd_number','cfd_time' ];
        for (var i=0; i < fields.length; i++) {
            record.data[fields[i]] = recordToAdd.data[fields[i]];
        }

        // check if already in
        var found = false;
        this.recordStore.each(function (line) {
            if (line.data.asteriskline_id.id == recordToAdd.data.id) {
                found = true;
            }
        }, this);
        if (! found) {
            // if not found -> add
            this.recordStore.add([record]);
        }
        
        this.collapse();
        this.clearValue();
        this.reset();
    },
    
    /**
     * init selection model
     */
    initGrid: function() {
        Tine.Voipmanager.LineGridPanel.superclass.initGrid.call(this);
        
        this.selModel.on('selectionchange', this.onSelectionChange, this);

        // init view
        this.view =  new Ext.grid.GridView({
            getRowClass: this.getViewRowClass,
            autoFill: true,
            forceFit:true
        });
    },
    
    /**
     * on selection change handler
     * @param {} sm
     */
    onSelectionChange: function(sm) {
        var rowCount = sm.getCount();
        if (rowCount == 1) {
            var selectedRows = sm.getSelections();
            this.activeRecord = selectedRows[0];
            this.cfPanel.setDisabled(false);
            //this.cfPanel.getForm().loadRecord(this.activeRecord);
            this.editDialog.getForm().loadRecord(this.activeRecord);
            this.cfPanel.onRecordLoad(this.activeRecord);
        } else {
            //this.cfPanel.getForm().reset();
            this.cfPanel.setDisabled(true);
        }
        
        // only allow to remove lines from Voipmanager and if rowCount > 0
        this.actionRemove.setDisabled(this.editDialog.recordProxy.appName != 'Voipmanager' || rowCount == 0);
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns:  [
                {id: 'linenumber',  header: '', dataIndex: 'linenumber', width: 20},
                {id: 'name', header: this.app.i18n._('Line'), dataIndex: 'asteriskline_id', width: 120, renderer: this.nameRenderer},
                {id: 'idletext', header: this.app.i18n._('Idle Text'), dataIndex: 'idletext', width: 100, editor: new Ext.form.TextField({
                    allowBlank: false,
                    allowNegative: false,
                    maxLength: 60
                })}
            ]
        });
    },
    
    nameRenderer: function(value) {
        return (value && value.name) ? value.name : '';
    }
});
