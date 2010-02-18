/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
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
     * @cfg
     */
    clicksToEdit: 1,
    autoExpandColumn: 'name',
    
    /**
     * @private
     */
    initComponent: function() {
        /*
        this.checkColumn = new Ext.ux.grid.CheckColumn({
           header: this.app.i18n._('Line active'),
           dataIndex: 'lineactive',
           width: 50
        });
        */
        
        this.recordClass = Tine.Voipmanager.Model.SnomLine;
        this.searchRecordClass = Tine.Voipmanager.Model.AsteriskSipPeer;
        //this.configColumns = this.checkColumn;
        
        Tine.Voipmanager.LineGridPanel.superclass.initComponent.call(this);
    },

    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function() {
        
        Tine.Voipmanager.LineGridPanel.superclass.initActionsAndToolbars.call(this);
        
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
    },
    
    /**
     * @param {Record} recordToAdd
     * 
     * TODO make reset work correctly -> show emptyText again
     */
    onAddRecordFromCombo: function(recordToAdd) {
        
        var recordData = {
            asteriskline_id: recordToAdd.data,
            linenumber: this.recordStore.getCount()+1,
            lineactive: 1
        };
        var record = new this.newRecordClass(recordData);
        
        this.recordStore.add([record]);
        
        // TODO check if already in
        /*
        if (! this.recordStore.getById(record.id)) {
        }
        */
        this.collapse();
        this.clearValue();
        this.reset();
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     * 
     * TODO add more editors
     */
    getColumnModel: function() {
        var genericComboConfig = {
            typeAhead: true,
            triggerAction: 'all',
            lazyRender:true,
            triggerAction: 'all',
            allowBlank: false,
            editable: true,
            store: [
                ['off', this.app.i18n._('off')],
                ['voicemail', this.app.i18n._('voicemail')]
            ]
        };

        var cfiModeConfig = genericComboConfig;
        cfiModeConfig.onSelect = function(record) {
            //console.log(record);
            // TODO save in corresponding field (if off/voicemail -> mode / if number -> number and mode -> number) 
        };
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns:  [
                {id: 'linenumber',  header: '', dataIndex: 'linenumber', width: 20},
                {id: 'name', header: this.app.i18n._('Line'), dataIndex: 'asteriskline_id', width: 100, renderer: this.nameRenderer},
                {id: 'idletext', header: this.app.i18n._('Idle Text'), dataIndex: 'idletext', width: 80, editor: new Ext.form.TextField({
                    allowBlank: false,
                    allowNegative: false,
                    maxLength: 60
                })},
                {id: 'cfi_mode',    header: this.app.i18n._('Forward'), dataIndex: 'asteriskline_id', width: 80, renderer: this.forwardRenderer, 
                    editor: new Ext.form.ComboBox(cfiModeConfig)},
                {id: 'cfb_mode',    header: this.app.i18n._('Forward Busy'), dataIndex: 'asteriskline_id', width: 80, renderer: this.busyRenderer},
                {id: 'cfd_mode',    header: this.app.i18n._('Forward No Answer'), dataIndex: 'asteriskline_id', width: 80, renderer: this.noanswerRenderer},
                {id: 'cfd_time',    header: this.app.i18n._('No Answer Time'), dataIndex: 'asteriskline_id', width: 80, renderer: this.noanswerTimeRenderer}
                //this.checkColumn
            ]
        });
    },
    
    nameRenderer: function(value) {
        return value.name;
    },
    
    // TODO generalize this
    // TODO show number or mode depending on mode
    forwardRenderer: function(value) {
        return value.cfi_mode;
    },

    busyRenderer: function(value) {
        return value.cfb_mode;
    },

    noanswerRenderer: function(value) {
        return value.cfd_mode;
    },

    noanswerTimeRenderer: function(value) {
        return value.cfd_time;
    }
});

