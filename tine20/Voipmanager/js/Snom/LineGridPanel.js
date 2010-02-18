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
        this.checkColumn = new Ext.ux.grid.CheckColumn({
           header: this.app.i18n._('Line active'),
           dataIndex: 'lineactive',
           width: 50
        });
        
        this.recordClass = Tine.Voipmanager.Model.SnomLine;
        this.configColumns = this.checkColumn;
        
        Tine.Voipmanager.LineGridPanel.superclass.initComponent.call(this);
    },

    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function() {
        
        Tine.Voipmanager.LineGridPanel.superclass.initActionsAndToolbars.call(this);
        
        /*
        this.accountTypeSelector = this.getAccountTypeSelector();
        this.contactSearchCombo = this.getContactSearchCombo();
        this.groupSearchCombo = this.getGroupSearchCombo();
        
        var items = [];
        switch (this.selectType) {
            case 'both':
                items = items.concat([this.contactSearchCombo, this.groupSearchCombo]);
                if (this.selectTypeDefault == 'user') {
                    this.groupSearchCombo.hide();
                } else {
                    this.contactSearchCombo.hide();
                }
                break;
            case 'user':
                items = this.contactSearchCombo;
                break;
            case 'group':
                items = this.groupSearchCombo;
                break;
        }
        
        this.comboPanel = new Ext.Panel({
            layout: 'hfit',
            border: false,
            items: items,
            columnWidth: 1
        });
        
        this.tbar = new Ext.Toolbar({
            items: [
                this.accountTypeSelector,
                this.comboPanel
            ],
            layout: 'column'
        });
        */
    },
    
    /**
     * @return {Tine.Tinebase.widgets.form.RecordPickerComboBox}
     */
    getSearchCombo: function() {
        /*
        return new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            //anchor: '100%',
            accountsStore: this.store,
            blurOnSelect: true,
            recordClass: Tine.Tinebase.Model.Group,
            newRecordClass: this.recordClass,
            recordPrefix: this.recordPrefix,
            emptyText: _('Search for groups ...'),
            onSelect: this.onAddRecordFromCombo
        });        
        */
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
                {id: 'name', header: this.app.i18n._('Line'), dataIndex: 'asteriskline_id', width: 100, renderer: this.nameRenderer},
                {id: 'idletext', header: this.app.i18n._('Idle Text'), dataIndex: 'idletext', width: 100, editor: new Ext.form.TextField({
                    allowBlank: false,
                    allowNegative: false,
                    maxLength: 60
                })},
                {id: 'linenumber', header: this.app.i18n._('Line Number'), dataIndex: 'linenumber', width: 100},
                this.checkColumn
            ]
        });
    },
    
    nameRenderer: function(value) {
        return value.name;
    }
});

