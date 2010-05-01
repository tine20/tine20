/*
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        loadMask: true,
        autoExpandColumn: 'description'
    },
    copyEditAction: true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timesheetBackend;
                
        this.gridConfig.cm = this.getColumnModel();
        this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
        this.initDetailsPanel();
        
        // Quick hack for mass update, to be generalized!!!
        // NOTE: The comment above means: do not CnP ;-)
        this.contextMenuItems = [
            '-', this.exportButton, '-', {
            text: _('Mass Update'),
            iconCls: 'action_edit',
            disabled: !Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts'),
            scope: this,
            menu: {
                items: [
                    '<b class="x-ux-menu-title">' + _('Update field:') + '</b>',
                    {
                        text: this.app.i18n._('Billable'),
                        field: 'is_billable',
                        scope: this,
                        handler: this.onMassUpdate
                    }, {
                        text: this.app.i18n._('Cleared'),
                        field: 'is_cleared',
                        scope: this,
                        handler: this.onMassUpdate
                    }, {
                        text: this.app.i18n._('Cleared in'),
                        field: 'billed_in',
                        scope: this,
                        handler: this.onMassUpdate
                    }
                ]
            }}
        ];
        // END OF QUICK HACK
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Timetracker.TimesheetGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * onMassUpdate (Quick hack for mass update, to be generalized!!!)
     * 
     * @param {Button} btn
     * @param {Event} e
     */ 
    onMassUpdate: function(btn, e) {
        var input;
        
        switch (btn.field) {
            case 'is_billable':
            case 'is_cleared':
//                input = new Ext.form.ComboBox({
//                    fieldLabel: btn.text,
//                    name: btn.field,
//                    width: 40,
//                    mode: 'local',
//                    forceSelection: true,
//                    triggerAction: 'all',
//                    store: [
//                        [0, Locale.getTranslationData('Question', 'no').replace(/:.*/, '')], 
//                        [1, Locale.getTranslationData('Question', 'yes').replace(/:.*/, '')]
//                    ]
//                });
                    input = new Ext.form.Checkbox({
                        hideLabel: true,
                        boxLabel: btn.text,
                        name: btn.field
                    });
                break;
            default:
                input = new Ext.form.TextField({
                    fieldLabel: btn.text,
                    name: btn.field
                });
        }
        
        var sm = this.grid.getSelectionModel();
        var filter = sm.getSelectionFilter();
        
        var updateForm = new Ext.FormPanel({
            border: false,
            labelAlign: 'top',
            buttonAlign: 'right',
            items: input,
            defaults: {
                anchor: '90%'
            }
        });
        var win = new Ext.Window({
            title: String.format(_('Update {0} records'), sm.getCount()),
            width: 300,
            height: 150,
            layout: 'fit',
            plain: true,
            closeAction: 'close',
            autoScroll: true,
            items: updateForm,
            buttons: [{
                text: _('Cancel'),
                iconCls: 'action_cancel',
                handler: function() {
                    win.close();
                }
            }, {
                text: _('Ok'),
                iconCls: 'action_saveAndClose',
                scope: this,
                handler: function() {
                    win.close();
                    this.grid.loadMask.show();
                    
                    var update = {};
                    update[input.name] = input.getValue();
                    
                    // some adjustments
                    if (input.name == 'is_cleared' && !update[input.name]) {
                        // reset billed_in field
                        update.billed_in = '';
                    }
                    if (input.name == 'billed_in' && update[input.name].length > 0) {
                        // set is cleard dynamically
                        update.is_cleared = true;
                    }
                    
                    this.recordProxy.updateRecords(filter, update, {
                        scope: this,
                        success: function(response) {
                            this.store.load();
                            
                            Ext.Msg.show({
                               title: _('Success'),
                               msg: String.format(_('Updated {0} records'), response.count),
                               buttons: Ext.Msg.OK,
                               animEl: 'elId',
                               icon: Ext.MessageBox.INFO
                            });
                        }
                    });
                }
            }]
        });
        win.show();
    },
    // END OF QUICK HACK
    
    /**
     * initialises filter toolbar
     * @private
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            allowSaving: true,
            filterModels: [
                //{label: _('Quick search'),    field: 'query',    operators: ['contains']}, // query only searches description
                {label: this.app.i18n._('Account'),      field: 'account_id', valueType: 'user'},
                {label: this.app.i18n._('Date'),         field: 'start_date', valueType: 'date', pastOnly: true},
                {label: this.app.i18n._('Description'),  field: 'description', defaultOperator: 'contains'},
                {label: this.app.i18n._('Billable'),     field: 'is_billable', valueType: 'bool', defaultValue: true },
                {label: this.app.i18n._('Cleared'),      field: 'is_cleared',  valueType: 'bool', defaultValue: false },
                {filtertype: 'tinebase.tag', app: this.app},
                {filtertype: 'timetracker.timeaccount'}
             ].concat(this.getCustomfieldFilters()),
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
            { id: 'timeaccount_id',     header: this.app.i18n._("Time Account"),        width: 500, dataIndex: 'timeaccount_id',        
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
                renderer: Tine.Tinebase.common.minutesRenderer },
            { id: 'creation_time',      header: this.app.i18n._('Creation Time'), dataIndex: 'creation_time', renderer: Tine.Tinebase.common.dateRenderer,   hidden: true },
            { id: 'last_modified_time', header: this.app.i18n._('Last Modified Time'), dataIndex: 'last_modified_time', renderer: Tine.Tinebase.common.dateRenderer,   hidden: true }
        ];
        
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
     * return additional tb items
     * 
     * @return {Array}
     * 
     * TODO add duplicate button
     */
    getToolbarItems: function() {
        this.exportButton = new Ext.Action({
            text: _('Export'),
            iconCls: 'action_export',
            scope: this,
            requiredGrant: 'readGrant',
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
                    })
                ]
            }
        });
        
        return [
            Ext.apply(new Ext.Button(this.exportButton), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    } 
});
