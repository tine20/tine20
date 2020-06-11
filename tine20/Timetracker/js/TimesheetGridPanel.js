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
    multipleEdit: true,

    /**
     * @private
     */

    /**
     * activates copy action
     */
    copyEditAction: true,

    /**
     * only allow multi edit with manage_timeaccounts right (because of timeaccount handling in edit dlg)
     */
    multipleEditRequiredRight: 'manage_timeaccounts',

    initComponent: function() {
        this.defaultFilters = [
            {field: 'start_date', operator: 'within', value: 'weekThis'},
            {field: 'account_id', operator: 'equals', value: Tine.Tinebase.registry.get('currentAccount')}
        ];

        this.initDetailsPanel();
        
        // only eval grants in action updater if user does not have the right to manage timeaccounts
        this.evalGrants = ! Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts');


        Tine.Timetracker.TimesheetGridPanel.superclass.initComponent.call(this);
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
                        data.sumbillable = data.sumbillable + parseInt(record.data.accounting_time);
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
                        '<div class="preview-panel-timesheet-description preview-panel-left">',
                            '<span class="preview-panel-nonbold">',
                             '{[this.encode(values.description)]}',
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
        var hiddenQuickTag = false,
            quicktagName,
            quicktagId;

        quicktagId = Tine.Timetracker.registry.get('quicktagId');
        quicktagName = Tine.Timetracker.registry.get('quicktagName');

        if (!quicktagId || !quicktagName) {
            hiddenQuickTag = true;
        }

        this.actions_massQuickTag = new Ext.Action({
            hidden: hiddenQuickTag,
            requiredGrant: 'editGrant',
            text: String.format(
                this.app.i18n._('Assign \'{0}\' Tag'),
                quicktagName
            ),
            disabled: true,
            allowMultiple: true,
            handler: this.onApplyQuickTag.createDelegate(this),
            iconCls: 'action_tag',
            scope: this
        });

        this.actions_export = new Ext.Action({
            text: this.app.i18n._('Export Timesheets'),
            iconCls: 'action_export',
            scope: this,
            requiredGrant: 'exportGrant',
            disabled: true,
            allowMultiple: true,
            actionUpdater: this.updateExportAction,
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
            this.actions_export,
            this.actions_massQuickTag
        ]);
        
        Tine.Timetracker.TimesheetGridPanel.superclass.initActions.call(this);
    },

    /**
     * Apply quick tag to current selection
     */
    onApplyQuickTag: function() {
        var quickTagId,
            filter,
            filterModel,
            me;

        me = this;

        // Tag to assign
        quickTagId = Tine.Timetracker.registry.get('quicktagId');

        // Get filter model for current selection
        filter = this.selectionModel.getSelectionFilter();
        filterModel = this.recordClass.getMeta('appName') + '_Model_' +  this.recordClass.getMeta('modelName') + 'Filter';

        // Send request to backend
        Ext.Ajax.request({
            scope: this,
            timeout: 1800000,
            success: function(response, options) {
                // In case of success, just reload grid
                me.getStore().reload();
            },
            params: {
                method: 'Tinebase.attachTagToMultipleRecords',
                filterData: filter,
                filterName: filterModel,
                tag: quickTagId
            },
            failure: function(response, options) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(response, options);
            }
        });
    },

    /**
     * check user exportGrant for timeaccounts
     * NOTE: manage_timeaccounts ALWAYS allows to export
     *
     * @param action
     * @param grants
     * @param records
     * @returns {boolean}
     */
    updateExportAction: function(action, grants, records) {
        // export should be allowed always if user is allowed to manage timeaccounts
        if (Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts')) {
            action.setDisabled(false);

            // stop further events
            return false;
        }

        // By default disallow export, this apply for example, if there is no selection yet
        // E.g. filter changes and so on
        var exportGrant = false;

        // We need to go through all timeaccounts and check if the user is trying to export a timesheet of a timeaccount
        // where he has no permission to export.
        Ext.each(records, function (record) {
            var timeaccount = record.get('timeaccount_id');
            var c = timeaccount.container_id;
            if (c.hasOwnProperty('account_grants')) {
                var grants = c.account_grants;

                if (!grants.exportGrant) {
                    exportGrant = false;

                    // stop loop
                    return false;
                } else {
                    // If there was at least one selection which had the exportGrant, allow to export
                    exportGrant = true;
                }
            }
        });

        var disable = !exportGrant;
        action.setDisabled(disable);

        // stop further events
        return false;
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actions_massQuickTag,
            this.actions_export
        ];
        
        return items;
    }
});
