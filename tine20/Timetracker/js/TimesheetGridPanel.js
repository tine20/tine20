/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Timetracker');

/**
 * Timesheet grid panel
 */
Tine.Timetracker.TimesheetGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Timetracker.Model.Timesheet,
    
    // grid specific
    defaultSortInfo: {field: 'start_date', direction: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timesheetBackend;
                
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
        this.initDetailsPanel();
        this.contextMenuItems = [
            '-', this.exportButton, '-', {
            text: 'change',
            scope: this,
            handler: function() {
                var sm = this.grid.getSelectionModel();
                var filter = sm.getSelectionFilter();
                console.log(filter);
            }
            /*
            menu: {
                items: [
                    {
                        text: 'is billable',
                        menu: {
                            items: [
                                '<b class="x-ux-menu-title">is billable</b>',
                                {
                                    text: 'yes',
                                    checked: true,
                                    group: 'is_billable',
                                    checkHandler: Ext.emptyFn
                                }, {
                                    text: 'no',
                                    checked: false,
                                    group: 'is_billable',
                                    checkHandler: Ext.emptyFn
                                }
                            ]
                        }
                    }
                ]
            }*/
        }];
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Timetracker.TimesheetGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                //{label: this.app.i18n._('Timesheet'),    field: 'query',    operators: ['contains']}, // query only searches description
                new Tine.Timetracker.TimeAccountGridFilter(),
                {label: this.app.i18n._('Time Account') + ' - ' + this.app.i18n._('Number'), field: 'timeaccount_number'},
                {label: this.app.i18n._('Time Account') + ' - ' + this.app.i18n._('Title'),   field: 'timeaccount_title'},
                {label: this.app.i18n._('Account'),      field: 'account_id', valueType: 'user'},
                {label: this.app.i18n._('Date'),         field: 'start_date', valueType: 'date'},
                {label: this.app.i18n._('Description'),  field: 'description' },
                {label: this.app.i18n._('Billable'),     field: 'is_billable', valueType: 'bool', defaultValue: true },
                {label: this.app.i18n._('Cleared'),      field: 'is_cleared',  valueType: 'bool', defaultValue: false },
                new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'start_date',
             filters: []
        });
    },    
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        return [{
            id: 'start_date',
            header: this.app.i18n._("Date"),
            width: 120,
            sortable: true,
            dataIndex: 'start_date',
            renderer: Tine.Tinebase.common.dateRenderer
        }, {
            id: 'start_time',
            hidden: true,
            header: this.app.i18n._("Start time"),
            width: 100,
            sortable: true,
            dataIndex: 'start_time',
            renderer: Tine.Tinebase.common.timeRenderer
        }, {
            id: 'timeaccount_id',
            header: this.app.i18n._("Time Account"),
            width: 500,
            sortable: true,
            dataIndex: 'timeaccount_id',
            renderer: function(timeaccount) {
                return new Tine.Timetracker.Model.Timeaccount(timeaccount).getTitle();
            }
        },{
            id: 'description',
            hidden: true,
            header: this.app.i18n._("Description"),
            width: 400,
            sortable: true,
            dataIndex: 'description',
            renderer: function(description) {
            	return Ext.util.Format.htmlEncode(description);
            }
        },{
            id: 'is_billable',
            //hidden: true,
            header: this.app.i18n._("Billable"),
            width: 100,
            sortable: true,
            dataIndex: 'is_billable',
            renderer: Tine.Tinebase.common.booleanRenderer
        },{
            id: 'is_cleared',
            hidden: true,
            header: this.app.i18n._("Cleared"),
            width: 100,
            sortable: true,
            dataIndex: 'is_cleared',
            renderer: Tine.Tinebase.common.booleanRenderer
        },{
            id: 'account_id',
            header: this.app.i18n._("Account"),
            width: 350,
            sortable: true,
            dataIndex: 'account_id',
            renderer: Tine.Tinebase.common.usernameRenderer
        },{
            id: 'duration',
            header: this.app.i18n._("Duration"),
            width: 150,
            sortable: true,
            dataIndex: 'duration',
            renderer: Tine.Tinebase.common.minutesRenderer
        }];
    },
    
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
                        '<div class="preview-panel-declaration">timeframe</div>',
                        '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                            '<span class="preview-panel-bold">',
                            'First Entry<br/>',
                            'Last Entry<br/>',
                            'Duration<br/>',
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
                        '<div class="preview-panel-declaration">summary</div>',
                        '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                            '<span class="preview-panel-bold">',
                            'Total Timesheets<br/>',
                            'Total Time<br/>',
                            'Billable Timesheets<br/>',
                            'Time of Billable Timesheets<br/>',
                            '</span>',
                        '</div>',
                        '<div class="preview-panel-timesheet-rightside preview-panel-left">',
                            '<span class="preview-panel-nonbold">',
                            '{count}<br/>',
                            '{sum}<br/>',
                            '{countbillable}<br/>',
                            '{sumbillable}<br/>',
                            '</span>',
                        '</div>',
                    '</div>',
                '</div>'            
            ),
            
            showDefault: function(body) {
            	
            	//console.log(this.gridpanel.store.proxy.jsonReader.jsonData);
            	
				var data = {
				    count: this.gridpanel.store.proxy.jsonReader.jsonData.totalcount,
				    countbillable: (this.gridpanel.store.proxy.jsonReader.jsonData.totalcountbillable) ? this.gridpanel.store.proxy.jsonReader.jsonData.totalcountbillable : 0,
				    sum:  Tine.Tinebase.common.minutesRenderer(this.gridpanel.store.proxy.jsonReader.jsonData.totalsum),
				    sumbillable: Tine.Tinebase.common.minutesRenderer(this.gridpanel.store.proxy.jsonReader.jsonData.totalsumbillable)
			    };
                
			    //console.log(this.gridpanel.store.proxy.jsonReader.jsonData);
			    
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
                    if (record.data.is_billable == '1') {
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
        				'<div class="preview-panel-declaration">beschreibung</div>',
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
        				'<div class="preview-panel-declaration">detail</div>',
        				'<div class="preview-panel-timesheet-leftside preview-panel-left">',
        					'<span class="preview-panel-bold">',
        					'Ansprechpartner<br/>',
        					'Newsletter<br/>',
        					'Ticketnummer<br/>',
        					'Ticketsubjekt<br/>',
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
        		'</div>',{

              //  '<div class="detailPanel">',
                //    '{[this.encode(values.description)]}',
                //'</div>', {
                encode: function(value, type, prefix) {
                    if (value) {
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
     * @todo add duplicate button
     * @todo move export buttons to single menu/split button
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
                        exportFunction: 'Timetracker.exportTimesheets',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as CSV'),
                        format: 'csv',
                        exportFunction: 'Timetracker.exportTimesheets',
                        gridPanel: this
                    })
                ]
            }
        });
        return ['-', this.exportButton];
    } 
});
