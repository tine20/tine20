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
     * @cfg {Bool} onlyBookable
     * only show bookable TA's
     */
    onlyBookable: true,
    /**
     * @cfg {Bool} showClosed
     * also show closed TA's
     */
    showClosed: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    /**
     * @cfg {Object} defaultPaging 
     */
    defaultPaging: {
        start: 0,
        limit: 50
    },
    
    /**
     * @property {Tine.Timetracker.Model.Timeaccount} record
     */
    record: null,
    
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
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        
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
                '<span>' +
                    '{[this.encode(values.number)]} - {[this.encode(values.title)]}' +
                    '<tpl if="is_open != 1 ">&nbsp;<i>(' + this.app.i18n._('closed') + ')</i></tpl>',
                '</span>' +
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
        
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    },
    
    getValue: function() {
        return this.record ? this.record.get('id') : null;
    },
    
    setValue: function(value) {
        if (value) {
            if (typeof(value.get) == 'function') {
                this.record = value;
                
            } else if (typeof(value) == 'string') {
                // NOTE: the string also could be the string for the display field!!!
                //console.log('id');
                
            } else {
                // we try raw data
                this.record = new Tine.Timetracker.Model.Timeaccount(value, value.id);
            }
            
            var title = this.record.getTitle();
            if (title) {
                Tine.Timetracker.TimeAccountSelect.superclass.setValue.call(this, title);
            }
        }
    },
    
    onSelect: function(record){
        record.set('displaytitle', record.getTitle());
        this.record = record;
        
        Tine.Timetracker.TimeAccountSelect.superclass.onSelect.call(this, record);
    },
        
    /**
     * @private
     */
    onStoreBeforeload: function(store, options) {
        options.params = options.params || {};
        
        options.params.filter = [
            {field: 'query', operator: 'contains', value: store.baseParams.query}
        ];
        
        if (this.onlyBookable) {
            options.params.filter.push({field: 'isBookable', operator: 'equals', value: 1 });
        }
        
        if (this.showClosed) {
            options.params.filter.push({field: 'showClosed', operator: 'equals', value: 1 });
        }
    }
});

Tine.Timetracker.TimeAccountGridFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    field: 'timeaccount_id',
    valueType: 'timeaccount',    
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        this.label = this.app.i18n._("Time Account");
        this.operators = ['equals'];
    },
   
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        // value
        var value = new Tine.Timetracker.TimeAccountSelect({
            filter: filter,
            onlyBookable: false,
            showClosed: true,
            blurOnSelect: true,
            width: 200,
            listWidth: 500,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        //value.on('select', this.onFiltertrigger, this);
        
        return value;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['timetracker.timeaccountselect'] = Tine.Timetracker.TimeAccountGridFilter;

Tine.Timetracker.TimeAccountStatusGridFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
	field: 'timeaccount_status',
    valueType: 'string',
    defaultValue: 'to bill',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        this.label = this.label ? this.label : this.app.i18n._("Time Account - Status");
        this.operators = ['equals'];
    },
   
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        // value
        var value = new Ext.form.ComboBox({
            filter: filter,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            mode: 'local',
            forceSelection: true,
            blurOnSelect: true,
            triggerAction: 'all',
            store: [
                ['not yet billed', this.app.i18n._('not yet billed')], 
                ['to bill', this.app.i18n._('to bill')],
                ['billed', this.app.i18n._('billed')]
            ]
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        
        return value;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['timetracker.timeaccountstatus'] = Tine.Timetracker.TimeAccountStatusGridFilter;
