
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

    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    initComponent: function() {
        try {
            this.response = Ext.decode(this.response);
            Tine.log.debug('RESP',this.response);
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
                fields: ['index', 'code', 'message', 'exception', 'resolveStrategy', 'resolvedRecord', 'isResolved']
            });
        this.store.loadData(this.response.exceptions);
    },
    
    /**
     * init buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_update ];
    },  
    
//    /**
//     * is called when the component is rendered
//     * @param {} ct
//     * @param {} position
//     */
//    onRender : function(ct, position) {
//        Tine.widgets.dialog.MultipleEditResultSummary.superclass.onRender.call(this, ct, position);
//
//        // generalized keybord map for edit dlgs
//        var map = new Ext.KeyMap(this.el, [ {
//            key : [ 10, 13 ], // ctrl + return
//            ctrl : true,
//            fn : this.onSend,
//            scope : this
//        } ]);
//
//    },
       
    /**
     * closes the window
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },    
    
    getFormItems: function() {
        try {
            this.summaryPanelInfo = {
                height: 100,
                border: false,
                layout: 'ux.display',
                layoutConfig: {
                    background: 'border'
                },
                listeners: {
                    scope: this,
                    render: function() { this.onSummaryPanelShow(); }
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
                xtype: 'ux.displaypanel',
                frame: true,
                autoScroll: true,
                items: [ this.summaryPanelInfo, this.summaryPanelFailures ]
            };
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.MultipleEditResultSummary::getFormItems');
            Tine.log.err(e.stack ? e.stack : e);
        }
    },
    
    onSummaryPanelShow: function() {
        
//        if (! this.summaryPanelInfo.rendered) {
//            return this.onSummaryPanelShow.defer(100, this);
//        }
//        try {
            alert('WUH');
//            if(this.response.failcount > 0) {
//                this.summaryPanelFailures.show();
//            }
            Tine.log.debug(this.summaryPanelFailures);//.el.update('Es gibt');
            Tine.log.debug(this.summaryPanelInfo);//.el.update('Es gibt');
//        } catch (e) {
//            Tine.log.err('Tine.widgets.dialog.MultipleEditResultSummary::onSummaryPanelShow');
//            Tine.log.err(e.stack ? e.stack : e);
//        }
    }
//    
//    /**
//     * summary panel show handler
//     */
//    onSummaryPanelShow: function() {
//        if (! this.summaryPanelInfo.rendered) {
//            return this.onSummaryPanelShow.defer(100, this);
//        }
//        
//        try {
//            // calc metrics
//            var rsp = this.lastImportResponse,
//                totalcount = rsp.totalcount,
//                failcount = 0,
//                mergecount = 0
//                discardcount = 0;
//                
//            this.exceptionStore.clearFilter();
//            this.exceptionStore.each(function(r) {
//                var strategy = r.get('resolveStrategy');
//                if (! strategy || !Ext.isString(strategy)) {
//                    failcount++;
//                } else if (strategy == 'keep') {
//                    totalcount++;
//                } else if (strategy.match(/^merge.*/)) {
//                    mergecount++;
//                } else if (strategy == 'discard') {
//                    discardcount++;
//                }
//            }, this);
//            
//            var tags = this.tagsPanel.getFormField().getValue(),
//                container = this.containerCombo.selectedContainer,
//                info = [String.format(_('In total we found {0} records in your import file.'), rsp.totalcount + rsp.duplicatecount + rsp.failcount)];
//                
//                if (totalcount) {
//                    info.push(String.format(_('{0} of them will be added as new records into: "{1}".'), 
//                        totalcount, 
//                        Tine.Tinebase.common.containerRenderer(container).replace('<div', '<span').replace('</div>', '</span>')
//                    ));
//                }
//                
//                if (mergecount + discardcount) {
//                    info.push(String.format(_('{0} of them where identified as duplicates.'), mergecount + discardcount));
//                    
//                    if (mergecount) {
//                        info.push(String.format(_('From the identified duplicates {0} will be merged into the existing records.'), mergecount));
//                    }
//                    
//                    if (discardcount) {
//                        info.push(String.format(_('From the identified duplicates {0} will be discarded.'), discardcount));
//                    }
//                }
//                
//                if (Ext.isArray(tags) && tags.length) {
//                    var tagNames = [];
//                    Ext.each(tags, function(tag) {tagNames.push(tag.name)});
//                    info.push(String.format(_('All records will be tagged with: "{0}" so you can find them easily.'), tagNames.join(',')));
//                }
//                
//                
//            this.summaryPanelInfo.update('<div style="padding: 5px;">' + info.join('<br />') + '</div>');
//            
//            // failures
//            if (failcount) {
//                this.exceptionStore.filter('code', 0);
//                this.summaryPanelFailures.show();
//                this.summaryPanelFailures.setTitle(String.format(_('{0} records have failures and will be discarded.'), failcount));
//                
//            }
//            

//    }
});


Tine.widgets.dialog.MultipleEditResultSummary.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        title: _('Summary'),
        name: Tine.widgets.dialog.ImportDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.MultipleEditResultSummary',
        contentPanelConstructorConfig: config
    });
    return window;
};