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
 * @TODO        Extend Tine.Tinebase.widgets.form.RecordPickerComboBox once this class
 *              is rewritten to use beforeload/load events
 */

Tine.Calendar.SearchCombo = Ext.extend(Ext.ux.form.ClearableComboBox, {
    anchor: '100% 100%',
    margins: '10px 10px',

    app: null,
    appName: 'Calendar',

    store: null,
    allowBlank: false,
    triggerAction: 'all',
    itemSelector: 'div.search-item',
    minChars: 3,

    forceSelection: true,
    minListWidth: 400,

    /*
     * shows date pager on bottom of the resultlist
     */
    showDatePager: true,

    /**
     * shows an reload button in the datepager
     */
    showReloadBtn: null,

    /**
     * shows an today button in the datepager
     */
    showTodayBtn: null,

    /**
     * To show or hide the paging toolbar
     */
    hasPaging: true,

    initComponent: function() {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }

        this.listEmptyText = this.app.i18n._('no events found');
        this.loadingText = i18n._('Searching...');

        this.recordClass = Tine.Calendar.Model.Event;
        this.recordProxy = Tine.Calendar.backend;

        this.displayField = this.recordClass.getMeta('titleProperty');
        this.valueField = this.recordClass.getMeta('idProperty');

        this.emptyText = this.emptyText || this.app.i18n._('Search Event');
        this.fieldLabel = this.fieldLabel || this.app.i18n._('Event');

        this.disableClearer = ! this.allowBlank;

        this.store = new Tine.Tinebase.data.RecordStore(Ext.copyTo({
            readOnly: true,
            sortInfo: {
                field: 'dtstart',
                direction: 'ASC'
            },
            proxy: this.recordProxy || undefined
        }, this, 'totalProperty,root,recordClass'));

        this.store.on('beforeload', this.onBeforeStoreLoad, this);
        this.store.on('load', this.onStoreLoad, this);

        this.initTemplate();

        Tine.Calendar.SearchCombo.superclass.initComponent.call(this);
    },

    setValue: Tine.Tinebase.widgets.form.RecordPickerComboBox.prototype.setValue,

    /**
     * @param {} store
     * @param {} records
     */
    onStoreLoad: function(store, records) {
        // override, when required.
    },

    /**
     * sets period ans searchword as query parameter
     * @param {} store
     */
    onBeforeStoreLoad: function(store) {
        store.baseParams.filter = [
            {field: 'query', operator: 'contains', value: this.getRawValue()}
        ];

        if (this.hasPaging && this.pageTb) {
            store.baseParams.filter.push({field: 'period', operator: 'within', value: this.pageTb.getPeriod()});
        }
    },

    expand: function () {
        this.pageTb.setVisible(this.hasPaging);
        this.supr().expand.apply(this, arguments);
    },

    /**
     * collapses the result list only when periodpicker is not active
     * @return {Boolean}
     */
    collapse: function() {
        if(this.hasPaging && this.pageTb.periodPickerActive == true) {
            return false;
        } else {
            Tine.Calendar.SearchCombo.superclass.collapse.call(this);
        }
    },

    /**
     * Reload store when combobox is cleared
     */
    clearValue: function () {
        this.supr().clearValue.apply(this, arguments);
        this.store.reload();
    },

    /**
     * is called, when list is initialized, appends a date-paging-toolbar instead a normal one
     */
    initList: function() {
        Tine.Calendar.SearchCombo.superclass.initList.call(this);
        var startDate = new Date().clearTime();

        this.footer = this.list.createChild({cls:'list-ft'});
        this.pageTb = new Tine.Calendar.PagingToolbar({
            view: 'month',
            anchor: '100% 100%',
            store: this.store,
            dtStart: startDate,
            showReloadBtn: this.showReloadBtn,
            showTodayBtn: this.showTodayBtn,
            renderTo: this.footer,
            listeners: {
                scope: this,
                change: function() {
                    this.store.removeAll();
                    this.store.load();
                    }
            }
        });

        this.assetHeight += this.footer.getHeight();
    },

    onBlur: Ext.emptyFn,
    assertValue: Ext.emptyFn,

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
                        var start = values.dtstart,
                            end   = values.dtend,
                            _ = window.lodash;

                        if (_.isString(start)) {
                            start = Date.parseDate(start, Date.patterns.ISO8601Long);
                        }

                        if (_.isString(end)) {
                            end = Date.parseDate(end, Date.patterns.ISO8601Long);
                        }

                        var duration = values.is_all_day_event ? Tine.Tinebase.appMgr.get('Calendar').i18n._('whole day') :
                                       Tine.Tinebase.common.minutesRenderer(Math.round((end.getTime() - start.getTime())/(1000*60)), '{0}:{1}', 'i');

                        return Tine.Tinebase.common.dateTimeRenderer(start) + ' (' + duration + ')';
                    }
                }
            );
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Calendar', 'Event', Tine.Calendar.SearchCombo);
