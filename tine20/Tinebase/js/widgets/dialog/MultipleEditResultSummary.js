
Ext.ns('Tine.widgets.dialog');

Tine.widgets.dialog.MultipleEditResultSummary = function(config) {
    
    Tine.widgets.dialog.MultiOptionsDialog.superclass.constructor.call(this, config);
    
    this.options = config.options || {};
    this.scope = config.scope || window;
};

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.MultipleEditResultSummary
 * @extends     Ext.FormPanel
 */
Ext.extend(Tine.widgets.dialog.MultipleEditResultSummary, Ext.FormPanel, {
      
    layout : 'fit',
    border : false,    
    
    labelAlign : 'top',
    items: null,
    anchor : '100% 100%',

    initComponent: function() {
        try {
            this.response = Ext.decode(this.response);
            // init actions
            this.initActions();
            // init buttons and tbar
            this.initButtons();
            
            this.initStore();
            
            // get items for this dialog
            this.items = this.getFormItems();
           
            Tine.widgets.dialog.MultipleEditResultSummary.superclass.initComponent.call(this);
        
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.MultipleEditResultSummary::initComponent');
            Tine.log.err(e.stack ? e.stack : e);
        }
    },
    
    /**
     * init actions
     */
    initActions: function() {       
        this.action_update = new Ext.Action({
            text : _('OK'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_saveAndClose'
        });
    },
    
    initStore: function() {
        this.store = new Ext.data.JsonStore({
                mode: 'local',
                idProperty: 'index',
                fields: ['index', 'message']
            });
        this.store.loadData(this.response.exceptions);
    },
    
    /**
     * init buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_update ];
    },  
    
    /**
     * is called when the component is rendered
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.widgets.dialog.MultipleEditResultSummary.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onCancel,
            scope : this
        } ]);

    },
       
    /**
     * closes the window
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },    
    
    getFormItems: function() {
        
        if(this.items) return this.items;
        var allrecs = this.response.totalcount + this.response.failcount;
        Tine.log.debug(this.response);
        var summary = String.format( (allrecs>1) ? _('You edited {0} records.') : _('You edited {0} record.'), allrecs);
        summary += '<br />';
        summary += String.format( (this.response.totalcount>1) ? _('{0} records have been updated properly.') : _('{0} record has been updated properly.'), this.response.totalcount);
        summary += '<br />';
        summary += String.format( (this.response.failcount>1) ? _('{0} records have invalid data after updating. These records have not been changed.') : _('{0} record has invalid data after updating. This record has not changed.'), this.response.failcount);
        
        try {
            this.summaryPanelInfo = {
                height: 100,
                border: false,
                items: [{
                    hideLabel: true,
                    xtype: 'ux.displayfield',
                    value: summary,
                    htmlEncode: false,
                    style: 'padding: 0 5px; color: black',
                    cls: 'x-panel-mc'
                }],
                hideLabel: true,
                layout: 'ux.display',
                layoutConfig: {
                    background: 'border'
                }
            };
            
            this.summaryPanelFailures = {
                baseCls: 'ux-arrowcollapse',
                cls: 'ux-arrowcollapse-plain',
                collapsible: true,
                hidden: false,
                flex: 1,
                title:'',
                items: [{
                    xtype: 'grid',
                    store: this.store,
                    autoHeight: true,
                    columns: [
                        { id: 'index', header: _('Index'), width: 60, sortable: false, dataIndex: 'index'}, 
                        { id: 'failure', header: _('Failure'), width: 60, sortable: false, dataIndex: 'message'}
                    ],
                    autoExpandColumn: 'failure'
                }]
            };
            
            return {
                border: false,            
                cls : 'x-ux-display',
                layout: 'ux.display',
                
                frame: true,
                autoScroll: true,
                items: [ this.summaryPanelInfo, this.summaryPanelFailures ]
            };
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.MultipleEditResultSummary::getFormItems');
            Tine.log.err(e.stack ? e.stack : e);
        }
    }

});


Tine.widgets.dialog.MultipleEditResultSummary.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        modal: true,
        title: _('Summary'),
        contentPanelConstructor: 'Tine.widgets.dialog.MultipleEditResultSummary',
        contentPanelConstructorConfig: config
    });
    return window;
};