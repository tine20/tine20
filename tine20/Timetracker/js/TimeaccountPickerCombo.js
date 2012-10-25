/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Timetracker');

/**
 * @namespace   Tine.Timetracker
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @class       Tine.Timetracker.TimeaccountPickerCombo
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * 
 * adds show closed handling
 */

Tine.Timetracker.TimeaccountPickerCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    /**
     * @cfg {Bool} showClosed
     */
    showClosed: false,
    
    /**
     * @property showClosedBtn
     * @type Ext.Button
     */
    showClosedBtn: null,
    
    sortBy: 'number',
    
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timeaccountBackend;
        this.recordClass = Tine.Timetracker.Model.Timeaccount;
        
        Tine.Timetracker.TimeaccountPickerCombo.superclass.initComponent.apply(this, arguments);
    },
    
    initList: function() {
        Tine.Timetracker.TimeaccountPickerCombo.superclass.initList.apply(this, arguments);
        
        if (this.pageTb && ! this.showClosedBtn) {
            this.showClosedBtn = new Tine.widgets.grid.FilterButton({
                text: this.app.i18n._('Show closed'),
                iconCls: 'action_showArchived',
                field: 'is_open',
                invert: true,
                pressed: this.showClosed,
                scope: this,
                handler: function() {
                    this.showClosed = this.showClosedBtn.pressed;
                    this.store.load();
                }
                
            });
            
            this.pageTb.add('-', this.showClosedBtn);
            this.pageTb.doLayout();
        }
    },
    
    /**
     * apply showClosed value
     */
    onStoreBeforeLoadRecords: function(o, options, success, store) {
        if (Tine.Timetracker.TimeaccountPickerCombo.superclass.onStoreBeforeLoadRecords.apply(this, arguments) !== false) {
            if (this.showClosedBtn) {
                this.showClosedBtn.setValue(options.params.filter);
            }
        }
    },
    
    /**
     * append showClosed value
     */
    onBeforeLoad: function (store, options) {
        Tine.Timetracker.TimeaccountPickerCombo.superclass.onBeforeLoad.apply(this, arguments);

        if (this.showClosedBtn) {
            Ext.each(store.baseParams.filter, function(filter, idx) {
                if (filter.field == 'is_open'){
                    store.baseParams.filter.remove(filter);
                }
            }, this);
            
            if (this.showClosedBtn.getValue().value === true) {
                store.baseParams.filter.push(this.showClosedBtn.getValue());
            }
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Timetracker', 'Timeaccount', Tine.Timetracker.TimeaccountPickerCombo);