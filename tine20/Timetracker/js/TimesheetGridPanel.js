/*
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Timetracker');

/**
 * Timesheet grid panel
 * 
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.TimesheetGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Timesheet Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Timetracker.TimesheetGridPanel
 */
Tine.Timetracker.TimesheetGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Timetracker.Model.Timesheet} recordClass
     */
    recordClass: Tine.Timetracker.Model.Timesheet,
    
    /**
     * @private grid cfg
     */
    defaultSortInfo: {field: 'start_date', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    copyEditAction: true,
    multipleEdit: true,
    multipleEditRequiredRight: 'manage_timeaccounts',
    
    /**
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timesheetBackend;
                
        this.gridConfig.cm = this.getColumnModel();
        this.initFilterToolbar();
        this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        // only eval grants in action updater if user does not have the right to manage timeaccounts
        this.evalGrants = ! Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts');
        
        Tine.Timetracker.TimesheetGridPanel.superclass.initComponent.call(this);
    },
 
    /**
     * initialises filter toolbar
     * @private
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterPanel({
            app: this.app,
            recordClass: Tine.Timetracker.Model.Timesheet,
            allowSaving: true,
            filterModels: Tine.Timetracker.Model.Timesheet.getFilterModel().concat(this.getCustomfieldFilters()),
            defaultFilter: 'start_date',
            filters: [
                {field: 'start_date', operator: 'within', value: 'weekThis'},
                {field: 'account_id', operator: 'equals', value: Tine.Tinebase.registry.get('currentAccount')}
            ]
        });
    },    
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function(){
        var columns = [
            { id: 'tags',               header: this.app.i18n._('Tags'),                width: 50,  dataIndex: 'tags', sortable: false,
                renderer: Tine.Tinebase.common.tagsRenderer },
            { id: 'start_date',         header: this.app.i18n._("Date"),                width: 120, dataIndex: 'start_date',
                renderer: Tine.Tinebase.common.dateRenderer },
            { id: 'start_time',         header: this.app.i18n._("Start time"),          width: 100, dataIndex: 'start_time',            hidden: true,
                renderer: Tine.Tinebase.common.timeRenderer },
            { id: 'timeaccount_id',     header: this.app.i18n._('Time Account (Number - Title)'), width: 500, dataIndex: 'timeaccount_id',
                renderer: this.rendererTimeaccountId },
            { id: 'timeaccount_closed', header: this.app.i18n._("Time Account closed"), width: 100, dataIndex: 'timeaccount_closed',    hidden: true,
                renderer: this.rendererTimeaccountClosed },
            { id: 'description',        header: this.app.i18n._("Description"),         width: 400, dataIndex: 'description',           hidden: true },
            { id: 'is_billable',        header: this.app.i18n._("Billable"),            width: 100, dataIndex: 'is_billable_combined',
                renderer: Tine.Tinebase.common.booleanRenderer },
            { id: 'is_cleared',         header: this.app.i18n._("Cleared"),             width: 100, dataIndex: 'is_cleared_combined',   hidden: true,
                renderer: Tine.Tinebase.common.booleanRenderer },
            { id: 'billed_in',          header: this.app.i18n._("Cleared in"),          width: 150, dataIndex: 'billed_in',             hidden: true },
            { id: 'account_id',         header: this.app.i18n._("Account"),             width: 350, dataIndex: 'account_id',
                renderer: Tine.Tinebase.common.usernameRenderer },
            { id: 'duration',           header: this.app.i18n._("Duration"),            width: 150, dataIndex: 'duration',
                renderer: Tine.Tinebase.common.minutesRenderer }
        ].concat(this.getModlogColumns());
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            // add custom fields
            columns: columns.concat(this.getCustomfieldColumns())
        });
    },
    
    /**
     * timeaccount renderer -> returns timeaccount title
     * 
     * @param {Array} timeaccount
     * @return {String}
     */
    rendererTimeaccountId: function(timeaccount) {
        return new Tine.Timetracker.Model.Timeaccount(timeaccount).getTitle();
    },
    
    /**
     * is timeaccount closed -> returns yes/no if timeaccount is closed
     * 
     * @param {} a
     * @param {} b
     * @param {Tine.Timetracker.Model.Timesheet} record
     * @return {String}
     */
    rendererTimeaccountClosed: function(a, b, record) {
        var isopen = (record.data.timeaccount_id.is_open == '1');
        return Tine.Tinebase.common.booleanRenderer(!isopen);
    },

    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.widgets.grid.DetailsPanel({
            gridpanel: this,
            
            // use default Tpl for default and multi view
            defaultTpl: new Ext.XTemplate(
                '<div class="preview-panel-timesheet-nobreak">',
                    '<!-- Preview timeframe -->',           
                    '<div class="preview-panel preview-panel-timesheet-left">',
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<div class="preview-panel-declaration">' /*+ this.app.i18n._('timeframe')*/ + '</div>',
                        '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                            '<span class="preview-panel-bold">',
                            /*'First Entry'*/'<br/>',
                            /*'Last Entry*/'<br/>',
                            /*'Duration*/'<br/>',
                            '<br/>',
                            '</span>',
                        '</div>',
                        '<div class="preview-panel-timesheet-rightside preview-panel-left">',
                            '<span class="preview-panel-nonbold">',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '</span>',
                        '</div>',
                    '</div>',
                    '<!-- Preview summary -->',
                    '<div class="preview-panel-timesheet-right">',
                        '<div class="bordercorner_gray_1"></div>',
                        '<div class="bordercorner_gray_2"></div>',
                        '<div class="bordercorner_gray_3"></div>',
                        '<div class="bordercorner_gray_4"></div>',
                        '<div class="preview-panel-declaration">'/* + this.app.i18n._('summary')*/ + '</div>',
                        '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                            '<span class="preview-panel-bold">',
                            this.app.i18n._('Total Timesheets') + '<br/>',
                            this.app.i18n._('Billable Timesheets') + '<br/>',
                            this.app.i18n._('Total Time') + '<br/>',
                            this.app.i18n._('Time of Billable Timesheets') + '<br/>',
                            '</span>',
                        '</div>',
                        '<div class="preview-panel-timesheet-rightside preview-panel-left">',
                            '<span class="preview-panel-nonbold">',
                            '{count}<br/>',
                            '{countbillable}<br/>',
                            '{sum}<br/>',
                            '{sumbillable}<br/>',
                            '</span>',
                        '</div>',
                    '</div>',
                '</div>'            
            ),
            
            showDefault: function(body) {
                
                var data = {
                    count: this.gridpanel.store.proxy.jsonReader.jsonData.totalcount,
                    countbillable: (this.gridpanel.store.proxy.jsonReader.jsonData.totalcountbillable) ? this.gridpanel.store.proxy.jsonReader.jsonData.totalcountbillable : 0,
                    sum:  Tine.Tinebase.common.minutesRenderer(this.gridpanel.store.proxy.jsonReader.jsonData.totalsum),
                    sumbillable: Tine.Tinebase.common.minutesRenderer(this.gridpanel.store.proxy.jsonReader.jsonData.totalsumbillable)
                };
                
                this.defaultTpl.overwrite(body, data);
            },
            
            showMulti: function(sm, body) {
                
                var data = {
                    count: sm.getCount(),
                    countbillable: 0,
                    sum: 0,
                    sumbillable: 0
                };
                sm.each(function(record){
                    
                    data.sum = data.sum + parseInt(record.data.duration);
                    if (record.data.is_billable_combined == '1') {
                        data.countbillable++;
                        data.sumbillable = data.sumbillable + parseInt(record.data.duration);
                    }
                });
                data.sum = Tine.Tinebase.common.minutesRenderer(data.sum);
                data.sumbillable = Tine.Tinebase.common.minutesRenderer(data.sumbillable);
                
                this.defaultTpl.overwrite(body, data);
            },
            
            tpl: new Ext.XTemplate(
                '<div class="preview-panel-timesheet-nobreak">',    
                    '<!-- Preview beschreibung -->',
                    '<div class="preview-panel preview-panel-timesheet-left">',
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<div class="preview-panel-declaration">' /* + this.app.i18n._('Description') */ + '</div>',
                        '<div class="preview-panel-timesheet-description preview-panel-left" ext:qtip="{[this.encode(values.description)]}">',
                            '<span class="preview-panel-nonbold">',
                             '{[this.encode(values.description, "longtext")]}',
                            '<br/>',
                            '</span>',
                        '</div>',
                    '</div>',
                    '<!-- Preview detail-->',
                    '<div class="preview-panel-timesheet-right">',
                        '<div class="bordercorner_gray_1"></div>',
                        '<div class="bordercorner_gray_2"></div>',
                        '<div class="bordercorner_gray_3"></div>',
                        '<div class="bordercorner_gray_4"></div>',
                        '<div class="preview-panel-declaration">' /* + this.app.i18n._('Detail') */ + '</div>',
                        '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                        // @todo add custom fields here
                        /*
                            '<span class="preview-panel-bold">',
                            'Ansprechpartner<br/>',
                            'Newsletter<br/>',
                            'Ticketnummer<br/>',
                            'Ticketsubjekt<br/>',
                            '</span>',
                        */
                        '</div>',
                        '<div class="preview-panel-timesheet-rightside preview-panel-left">',
                            '<span class="preview-panel-nonbold">',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '</span>',
                        '</div>',
                    '</div>',
                '</div>',{
                encode: function(value, type, prefix) {
                    if (value) {
                        if (type) {
                            switch (type) {
                                case 'longtext':
                                    value = Ext.util.Format.ellipsis(value, 150);
                                    break;
                                default:
                                    value += type;
                            }                           
                        } else {
                            value = Ext.util.Format.htmlEncode(value);
                        }
                        
                        var encoded = Ext.util.Format.htmlEncode(value);
                        encoded = Ext.util.Format.nl2br(encoded);
                        
                        return encoded;
                    } else {
                        return '';
                    }
                }
            })
        });
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.actions_exportTimesheet = new Ext.Action({
            text: this.app.i18n._('Export Timesheets'),
            iconCls: 'action_export',
            scope: this,
            requiredGrant: 'exportGrant',
            disabled: true,
            allowMultiple: true,
            menu: {
                items: [
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ODS'),
                        format: 'ods',
                        iconCls: 'tinebase-action-export-ods',
                        exportFunction: 'Timetracker.exportTimesheets',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as CSV'),
                        format: 'csv',
                        iconCls: 'tinebase-action-export-csv',
                        exportFunction: 'Timetracker.exportTimesheets',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ...'),
                        iconCls: 'tinebase-action-export-xls',
                        exportFunction: 'Timetracker.exportTimesheets',
                        showExportDialog: true,
                        gridPanel: this
                    })
                ]
            }
        });
        
        // register actions in updater
        this.actionUpdater.addActions([
            this.actions_exportTimesheet
        ]);
        
        Tine.Timetracker.TimesheetGridPanel.superclass.initActions.call(this);
    },
    
    /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.Button(this.actions_exportTimesheet), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actions_exportTimesheet
//            '-', {
//            text: _('Mass Update'),
//            iconCls: 'action_edit',
//            disabled: !Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts'),
//            scope: this,
//            menu: {
//                items: [
//                    '<b class="x-ux-menu-title">' + _('Update field:') + '</b>',
//                    {
//                        text: this.app.i18n._('Billable'),
//                        field: 'is_billable',
//                        scope: this,
//                        handler: this.onMassUpdate
//                    }, {
//                        text: this.app.i18n._('Cleared'),
//                        field: 'is_cleared',
//                        scope: this,
//                        handler: this.onMassUpdate
//                    }, {
//                        text: this.app.i18n._('Cleared in'),
//                        field: 'billed_in',
//                        scope: this,
//                        handler: this.onMassUpdate
//                    }
//                ]
//            }
//        }
        ];
        
        return items;
    }
});
