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
     */
    getColumnModel: function() {
        var genericComboConfig = {
            typeAhead: true,
            triggerAction: 'all',
            lazyRender:true,
            triggerAction: 'all',
            allowBlank: false,
            editable: false,
            blurOnSelect: true,
            store: [
                ['off', 'off'],
                ['number', 'number'],
                ['voicemail', 'voicemail']
            ]
        };

        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns:  [
                {id: 'linenumber',  header: '', dataIndex: 'linenumber', width: 20},
                {id: 'name', header: this.app.i18n._('Line'), dataIndex: 'asteriskline_id', width: 120, renderer: this.nameRenderer},
                {id: 'idletext', header: this.app.i18n._('Idle Text'), dataIndex: 'idletext', width: 80, editor: new Ext.form.TextField({
                    allowBlank: false,
                    allowNegative: false,
                    maxLength: 60
                })},
                {id: 'cfi_mode',    header: this.app.i18n._('Forward'), dataIndex: 'cfi_mode', width: 70, editor: new Ext.form.ComboBox(genericComboConfig)},
                {id: 'cfi_number',    header: this.app.i18n._('Forward #'), dataIndex: 'cfi_number', width: 90, editor: new Ext.form.TextField({
                    allowBlank: true,
                    allowNegative: false,
                    maxLength: 60
                })},
                {id: 'cfb_mode',    header: this.app.i18n._('Forward Busy'), dataIndex: 'cfb_mode', width: 70, editor: new Ext.form.ComboBox(genericComboConfig)},
                {id: 'cfb_number',    header: this.app.i18n._('Busy #'), dataIndex: 'cfb_number', width: 90, editor: new Ext.form.TextField({
                    allowBlank: true,
                    allowNegative: false,
                    maxLength: 60
                })},
                {id: 'cfd_mode',    header: this.app.i18n._('Forward No Answer'), dataIndex: 'cfd_mode', width: 70, editor: new Ext.form.ComboBox(genericComboConfig)},
                {id: 'cfd_number',    header: this.app.i18n._('No Answer #'), dataIndex: 'cfd_number', width: 90, editor: new Ext.form.TextField({
                    allowBlank: true,
                    allowNegative: false,
                    maxLength: 60
                })},
                {id: 'cfd_time',    header: this.app.i18n._('No Answer Time'), dataIndex: 'cfd_time', width: 80, editor: new Ext.form.TextField({
                    allowBlank: true,
                    allowNegative: false,
                    maxLength: 60
                })}
                //this.checkColumn
            ]
        });
    },
    
    nameRenderer: function(value) {
        return (value && value.name) ? value.name : '';
    }
});
