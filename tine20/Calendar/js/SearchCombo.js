/*
 * Tine 2.0
 * event combo box and store
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar');

/**
 * event selection combo box
 * 
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.SearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * <p>Event Search Combobox</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Calendar.SearchCombo
 * 
 * TODO         add     forceSelection: true ?
 */

Tine.Calendar.SearchCombo = Ext.extend(Ext.form.ComboBox, {
    anchor: '100% 100%',
    margins: '10px 10px',
    
    app: null,
    appName: 'Calendar',

    /**
     * date filter from datepicker
     */
    addFilter: null,
    store: null,
    
    triggerAction: 'all',
    itemSelector: 'div.search-item',
    minChars: 3,
    forceSelection: true,
    
    initComponent: function() {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        
        this.loadingText = _('Searching...');
        
        this.recordClass = Tine.Calendar.Model.Event;
        this.recordProxy = Tine.Calendar.eventBackend;      
        
        this.displayField = this.recordClass.getMeta('titleProperty');
        this.valueField = this.recordClass.getMeta('idProperty');
        
        this.fieldLabel = this.app.i18n._('Event'),
        this.emptyText = this.app.i18n._('Search Event'),
        
        this.store = new Tine.Tinebase.data.RecordStore(Ext.copyTo({
            readOnly: true,
            proxy: this.recordProxy || undefined
        }, this, 'totalProperty,root,recordClass'));
        
        this.on('beforequery', this.onBeforeQuery, this);
        
        this.initTemplate();
        
        Tine.Calendar.SearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {

        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td width="40%"><b>{[this.encode(values.summary)]}</b></td>',
                            '<td width="60%">',
                                '{[this.encodeDate(values)]}',
                            '</td>',
                            
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    encode: function(value) {
                        
                        if (value) {
                            return Ext.util.Format.htmlEncode(value);
                        } else {
                            return '';
                        }
                    },
                    encodeDate: function(values) {
                        var start = values.dtstart;
                            end   = values.dtend;

                        var duration = values.is_all_day_event ? Tine.Tinebase.appMgr.get('Calendar').i18n._('whole day') : 
                                       Tine.Tinebase.common.minutesRenderer(Math.round((end.getTime() - start.getTime())/(1000*60)), '{0}:{1}', 'i');
                        
                        var startYear = start.getYear() + 1900;
                        return start.getDate() + '.' + (start.getMonth() + 1) + '.' + startYear + ' ' + duration;
                        
                    }
                }
            );
        }
    },
    
    /**
     * sets the filter
     * @param {} filter
     */
    setFilter: function(filter) {      
        this.addFilter = filter;
        this.store.baseParams.filter = [this.addFilter];
        this.fireEvent('filterupdate');
    },
    
    onBeforeQuery: function (qevent) {
        this.store.baseParams.filter = [this.addFilter];
        this.store.baseParams.filter.push({field: 'query', operator: 'contains', value: qevent.query });
    }


});
