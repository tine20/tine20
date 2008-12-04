/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Timetracker');

Tine.Timetracker.TimeAccountSelect = Ext.extend(Ext.form.ComboBox, {
    
    /**
     * @cfg {Ext.data.DataProxy} recordProxy
     */
    recordProxy: Tine.Timetracker.timeaccountBackend,
    /**
     * @cfg {Object } defaultPaging 
     */
    defaultPaging: {
        start: 0,
        limit: 50
    },
    
    itemSelector: 'div.search-item',
    typeAhead: false,
    minChars: 3,
    pageSize:10,
    forceSelection: true,
    displayField: 'displaytitle',
    triggerAction: 'all',
    selectOnFocus: true,
    
    /**
     * @private
     */
    initComponent: function() {
        
        this.store = new Ext.data.Store({
            fields: Tine.Timetracker.Model.TimeaccountArray.concat({name: 'displaytitle'}),
            proxy: this.recordProxy,
            reader: this.recordProxy.getReader(),
            remoteSort: true,
            sortInfo: {field: 'number', dir: 'ASC'},
            listeners: {
                scope: this,
                //'update': this.onStoreUpdate,
                'beforeload': this.onStoreBeforeload
            }
        });
        
        this.tpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
                '<span>{[this.encode(values.number)]} - {[this.encode(values.title)]}</span>' +
                //'{[this.encode(values.description)]}' +
            '</div></tpl>',
            {
                encode: function(value) {
                     if (value) {
                        return Ext.util.Format.htmlEncode(value);
                    } else {
                        return '';
                    }
                }
            }
        );
        
        Tine.Timetracker.TimeAccountSelect.superclass.initComponent.call(this);
    },
    
    /*
    setValue: function(value) {
        
    }
    */
    
    onSelect: function(record){
        record.set('displaytitle', record.getTitle());
        Tine.Timetracker.TimeAccountSelect.superclass.onSelect.call(this, record);
    },
        
    /**
     * @private
     */
    onStoreBeforeload: function(store, options) {
        options.params = options.params || {};
        
        options.params.filter = [{field: 'query', operator: 'contains', value: store.baseParams.query}];
    }
});