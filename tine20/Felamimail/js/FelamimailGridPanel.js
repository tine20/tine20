/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * Message grid panel
 */
Tine.Felamimail.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Felamimail.Model.Message,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'received', direction: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'subject'
    },
    
    /**
     * Return CSS class to apply to rows depending upon flags
     * - checks Flagged, Deleted and Seen
     * 
     * @param {} record
     * @param {} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var flags = record.get('flags');
        var className = '';
        if(flags !== null) {
            if (flags.match(/Flagged/)) {
                className += ' flag_flagged';
            }
            if (flags.match(/Deleted/)) {
                className += ' flag_deleted';
            }
            if (!flags.match(/Seen/)) {
                className += ' flag_unread';
            }
        }
        return className;
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Felamimail.recordBackend;
        
        this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);        
        
        Tine.Felamimail.GridPanel.superclass.initComponent.call(this);
        
        this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Felamimail', 'records'));
        this.action_editInNewWindow.requiredGrant = 'editGrant';
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Subject'),    field: 'subject',       operators: ['contains']}
                // @todo add filters
                /*
                {label: this.app.i18n._('Message'),    field: 'query',       operators: ['contains']},
                {label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
                new Tine.Felamimail.TimeAccountStatusGridFilter({
                    field: 'status'
                }),
                */
                //new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'subject',
             filters: []
        });
    },    
    
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
        return [{
            id: 'id',
            header: this.app.i18n._("Id"),
            width: 100,
            sortable: true,
            dataIndex: 'id',
            hidden: true
        }, {
            id: 'flags',
            //header: this.app.i18n._("Status"),
            width: 16,
            sortable: true,
            dataIndex: 'flags',
            renderer: Tine.Felamimail.getFlagIcon
        },{
            id: 'subject',
            header: this.app.i18n._("Subject"),
            width: 300,
            sortable: true,
            dataIndex: 'subject'
        },{
            id: 'from',
            header: this.app.i18n._("From"),
            width: 150,
            sortable: true,
            dataIndex: 'from'
            //renderer: this.statusRenderer.createDelegate(this)
        },{
            id: 'to',
            header: this.app.i18n._("To"),
            width: 150,
            sortable: true,
            dataIndex: 'to',
            hidden: true
        },{
            id: 'sent',
            header: this.app.i18n._("Sent"),
            width: 150,
            sortable: true,
            dataIndex: 'sent',
            hidden: true,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'received',
            header: this.app.i18n._("Received"),
            width: 150,
            sortable: true,
            dataIndex: 'received',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 80,
            sortable: true,
            dataIndex: 'size',
            hidden: true
        }];
    },
    
    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    },
    
    /**
     * return additional tb items
     */
    getToolbarItems: function(){
    	/*
        this.action_showClosedToggle = new Tine.widgets.grid.FilterButton({
            text: this.app.i18n._('Show closed'),
            iconCls: 'action_showArchived',
            field: 'showClosed'
        });
        */
        
        return [
            /*
            new Ext.Toolbar.Separator(),
            this.action_showClosedToggle
            */
        ];
    }    
});
