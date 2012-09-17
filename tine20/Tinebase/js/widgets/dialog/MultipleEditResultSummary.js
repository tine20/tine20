
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
    
    /**
     * Store holding the Exceptions
     * @type Ext.Store
     */
    store: null,
    
    /**
     * Json Response from updateMultipleRecords
     * @type String
     */
    response: null,

    /**
     * The appname of the calling app
     * @type String 
     */
    appName: null,
    
    /**
     * The calling app
     * @type Tinebase.Application
     */
    app: null,
    
    /**
     * {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    
    initComponent: function() {
        this.response = Ext.decode(this.response);
        
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        
        // init some translations
        if (this.app.i18n && this.recordClass !== null) {
            this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
            this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        }
        
        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        
        this.initStore();
        
        // get items for this dialog
        this.items = this.getFormItems();
       
        Tine.widgets.dialog.MultipleEditResultSummary.superclass.initComponent.call(this);
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
                idProperty: 'id',
                fields: ['id', 'record','message'],
                sortInfo: {
                    field: 'record',
                    direction: 'ASC'
                }
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
        
        var rn = (allrecs>1) ? this.i18nRecordsName : this.i18nRecordName;
        var summary = String.format( (allrecs>1) ? _('You edited {0} {1}.') : _('You edited {0} {1}.'), allrecs, rn);
        summary += '<br />';
        rn = (this.response.totalcount>1) ? this.i18nRecordsName : this.i18nRecordName;
        summary += String.format( (this.response.totalcount>1) ? _('{0} {1} have been updated properly.') : _('{0} {1} has been updated properly.'), this.response.totalcount, rn);
        summary += '<br />';
        rn = (this.response.failcount>1) ? this.i18nRecordsName : this.i18nRecordName;
        summary += String.format( (this.response.failcount>1) ? _('{0} {1} have invalid data after updating. These {1} have not been changed.') : _('{0} {1} has invalid data after updating. This {1} has not been changed.'), this.response.failcount, rn);
       
        return {
            border: false,
            cls : 'x-ux-display',
            layout: 'ux.display',
            
            frame: true,
            autoScroll: true,
            items: [
                {
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
                    ref: '../../summaryPanelInfo',
                    layout: 'ux.display',
                    layoutConfig: {
                        background: 'border'
                    }
                }, {
                    baseCls: 'ux-arrowcollapse',
                    cls: 'ux-arrowcollapse-plain',
                    collapsible: true,
                    hidden: false,
                    flex: 1,
                    title:'',
                    ref: '../../summaryPanelFailures',
                    items: [{
                        xtype: 'grid',
                        store: this.store,
                        autoHeight: true,
                        columns: [
                            { id: 'id', header: _('Index'), width: 60, sortable: false, dataIndex: 'id', hidden: true}, 
                            { id: 'record', header: this.i18nRecordName, width: 160, sortable: true, dataIndex: 'record', renderer: function(value) {
                                return value.n_fn;
                            }},
                            { id: 'failure', header: _('Failure'), width: 60, sortable: true, dataIndex: 'message'}
                        ],
                        autoExpandColumn: 'failure'
                    }]
                }
            ]
        };
    }

});


Tine.widgets.dialog.MultipleEditResultSummary.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 450,
        modal: true,
        title: _('Summary'),
        contentPanelConstructor: 'Tine.widgets.dialog.MultipleEditResultSummary',
        contentPanelConstructorConfig: config
    });
    return window;
};