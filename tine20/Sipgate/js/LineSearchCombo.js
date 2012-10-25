/*
 * Tine 2.0
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sipgate');

/**
 * Line selection combo box
 * 
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.LineSearchCombo
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sipgate.LineSearchCombo
 */
Tine.Sipgate.LineSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    /**
     * Show only usable lines for calling
     * @type String
     */
    onlyUsable: false,
    
    minListWidth: 100,
    //private
    initComponent: function(){
        this.recordClass = Tine.Sipgate.Model.Line;
        this.recordProxy = Tine.Sipgate.lineBackend;
        Tine.Sipgate.LineSearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent) {
        Tine.Sipgate.LineSearchCombo.superclass.onBeforeQuery.call(this, qevent);
        if (this.onlyUsable) {
            this.store.baseParams.filter.push({field: 'user_id', operator: 'equals', value: Tine.Tinebase.registry.get('currentAccount')});
        }
        
    },

    /**
     * respect record.getTitle method
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate('<tpl for="."><div class="x-combo-list-item">{[this.getTitle(values.' + this.recordClass.getMeta('idProperty') + ')]}</div></tpl>', {
                getTitle: (function(id) {
                    var record = this.getStore().getById(id),
                        title = record ? record.getTitle() + ' (+' + record.get('e164_out') + ')': '&nbsp';
                    
                    return Ext.util.Format.htmlEncode(title);
                }).createDelegate(this)
            });
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Sipgate', 'Line', Tine.Sipgate.LineSearchCombo);
