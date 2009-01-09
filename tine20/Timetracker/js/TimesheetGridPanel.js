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
                {label: this.app.i18n._('Timesheet'),    field: 'query',    operators: ['contains']},
                new Tine.Timetracker.TimeAccountGridFilter(),
                {label: this.app.i18n._('Account'),      field: 'account_id', valueType: 'user'},
                {label: this.app.i18n._('Date'),         field: 'start_date', valueType: 'date'},
                {label: this.app.i18n._('Description'),  field: 'description' }
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
            hidden: true,
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
            
            showDefault: function(body) {
                var totalsum = Tine.Tinebase.common.minutesRenderer(this.gridpanel.store.proxy.jsonReader.jsonData.totalsum);
                var tpl = new Ext.XTemplate(
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
					'{totalcount}<br/>',
					totalsum + '<br/>',
					'<br/>',
					'<br/>',
					'</span>',
				'</div>',
			'</div>',
		'</div>'
				//' total time of all {totalcount} timesheets: ' + totalsum + '&nbsp;&nbsp;&nbsp;'
				);
                tpl.overwrite(body, this.gridpanel.store.proxy.jsonReader.jsonData);
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
                        return Ext.util.Format.htmlEncode(value);
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
     */
    getToolbarItems: function(){
        this.action_exportCsv = new Tine.widgets.grid.ExportButton({
            text: this.app.i18n._('Export All'),
            format: 'csv',
            exportFunction: 'Timetracker.exportTimesheets',
            filterToolbar: this.filterToolbar
        });
        
        return [
            new Ext.Toolbar.Separator(),
            this.action_exportCsv
            /*
            ,[{
                text: this.app.i18n._('Duplicate'),
                iconCls: 'action_duplicate'
            }]
            */
        ];
    } 
});
