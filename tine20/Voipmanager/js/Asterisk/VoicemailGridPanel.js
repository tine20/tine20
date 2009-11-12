/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Voicemail grid panel
 */
Tine.Voipmanager.AsteriskVoicemailGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.AsteriskVoicemail,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'fullname', direction: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'fullname'
    },
    
    initComponent: function() {
    
        this.recordProxy = Tine.Voipmanager.AsteriskVoicemailBackend;
                
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
        //this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
 
         
        Tine.Voipmanager.AsteriskVoicemailGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: _('Quick search'),    field: 'query',    operators: ['contains']}
            ],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        return [{ 
	       	id: 'id', 
	       	header: this.app.i18n._('id'), 
	       	dataIndex: 'id', 
	       	width: 10, 
            sortable: true,
	       	hidden: true 
       }, { 
	       	id: 'mailbox', 
	       	header: this.app.i18n._('mailbox'), 
	       	dataIndex: 'mailbox', 
	       	width: 50, 
            sortable: true
       },{ 
	       	id: 'context', 
	       	header: this.app.i18n._('context'), 
	       	dataIndex: 'context', 
	       	width: 70, 
            sortable: true
       },{ 
	       	id: 'fullname', 
	       	header: this.app.i18n._('fullname'), 
	       	dataIndex: 'fullname', 
	       	width: 180, 
            sortable: true
       },{ 
	       	id: 'email', 
	       	header: this.app.i18n._('email'), 
	       	dataIndex: 'email', 
	       	width: 120, 
            sortable: true
       },{ 
	       	id: 'pager', 
	       	header: this.app.i18n._('pager'), 
	       	dataIndex: 'pager', 
	       	width: 120, 
            sortable: true 
       },{ 
	       	id: 'tz', 
	       	header: this.app.i18n._('tz'), 
	       	dataIndex: 'tz', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       }, { 
	       	id: 'attach', 
	       	header: this.app.i18n._('attach'), 
	       	dataIndex: 'attach', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'saycid', 
	       	header: this.app.i18n._('saycid'), 
	       	dataIndex: 'saycid', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'dialout', 
	       	header: this.app.i18n._('dialout'), 
	       	dataIndex: 'dialout', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'callback', 
	       	header: this.app.i18n._('callback'), 
	       	dataIndex: 'callback', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'review', 
	       	header: this.app.i18n._('review'), 
	       	dataIndex: 'review', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'operator', 
	       	header: this.app.i18n._('operator'), 
	       	dataIndex: 'operator', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'envelope', 
	       	header: this.app.i18n._('envelope'), 
	       	dataIndex: 'envelope', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
      }, { 
	      	id: 'sayduration', 
	      	header: this.app.i18n._('sayduration'), 
	      	dataIndex: 'sayduration', 
	      	width: 10, 
            sortable: true, 
	      	hidden: true 
       }, { 
	       	id: 'saydurationm', 
	       	header: this.app.i18n._('saydurationm'), 
	       	dataIndex: 'saydurationm', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'sendvoicemail', 
	       	header: this.app.i18n._('sendvoicemail'), 
	       	dataIndex: 'sendvoicemail', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'delete', 
	       	header: this.app.i18n._('delete'), 
	       	dataIndex: 'delete', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'nextaftercmd', 
	       	header: this.app.i18n._('nextaftercmd'), 
	       	dataIndex: 'nextaftercmd', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'forcename', 
	       	header: this.app.i18n._('forcename'), 
	       	dataIndex: 'forcename', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'forcegreetings',
	       	header: this.app.i18n._('forcegreetings'), 
	       	dataIndex: 'forcegreetings', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       },{ 
	       	id: 'hidefromdir', 
	       	header: this.app.i18n._('hidefromdir'), 
	       	dataIndex: 'hidefromdir', 
	       	width: 10, 
            sortable: true, 
	       	hidden: true 
       }];
    },
    
    initDetailsPanel: function() { return false; },
    
    /**
     * return additional tb items
     * 
     * @todo add duplicate button
     * @todo move export buttons to single menu/split button
     */
    getToolbarItems: function(){
       
        return [

        ];
    }
    
});