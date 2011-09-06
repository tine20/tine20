/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * Link GridPanel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.LinkGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 * 
 * <p>Link GridPanel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.LinkGridPanel
 */
Tine.widgets.grid.LinkGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    
    record: null,
    recordClass: Tine.Tinebase.Model.Relation,
    
    // TODO allow to configure allowed types
    
    /**
     * @private
     */
    initComponent: function() {
        this.searchRecordClass = this.searchRecordClass || this.recordClass;
        
        Tine.widgets.grid.LinkGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function () {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns:  [
                {id: 'name', header: _('Name'), dataIndex: 'related_record', renderer: this.relatedRecordRender, scope: this}
                // TODO add type chooser combo
            ].concat(this.configColumns)
        });
    },
    
    /**
     * related record renderer
     * 
     * @param {Record} value
     * @return {String}
     */
    relatedRecordRender: function(value) {
        return Ext.util.Format.htmlEncode(value.get(this.searchRecordClass.getMeta('titleProperty')));
    },
    
    /**
     * @param {Record} recordToAdd
     */
    onAddRecordFromCombo: function(recordToAdd) {
        // TODO add more relation fields
        var record = new Tine.Tinebase.Model.Relation({
            related_record: new this.newRecordClass(recordToAdd.data, recordToAdd.id),
            related_id: recordToAdd.id,
            own_id: (this.record) ? this.record.id : null
        });
        // check if already in
        if (this.recordStore.findExact('related_id', recordToAdd.id) === -1) {
            this.recordStore.add([record]);
        }
        this.collapse();
        this.clearValue();
        this.reset();
    }
});

Ext.reg('wdgt.linkgrid', Tine.widgets.grid.LinkGridPanel);
