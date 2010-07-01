/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Felamimail')


Tine.Felamimail.MessageDisplayDialog = Ext.extend(Tine.Felamimail.GridDetailsPanel ,{
    /**
     * @cfg {Tine.Felamimail.Model.Message}
     */
    record: null,
    
    autoScroll: false,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18n = this.app.i18n;
        
        // far to complicated for a service release
        //this.initToolbar();
        
        this.supr().initComponent.apply(this, arguments);
        
    },
    
    initToolbar: function() {
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            evalGrants: false
        });

        // use actions from gridPanel
        this.onAddAccount = this.onToggleFlag = Ext.emptyFn;
        this.onEditInNewWindow = Tine.Felamimail.GridPanel.prototype.onEditInNewWindow;
        this.onDeleteRecords = Tine.Felamimail.GridPanel.prototype.onDeleteRecords;
        this.onPrintPreview = Tine.Felamimail.GridPanel.prototype.onPrintPreview;
        this.onPrint = Tine.Felamimail.GridPanel.prototype.onPrint;
        Tine.Felamimail.GridPanel.prototype.initActions.call(this);
        this.actionUpdater.updateActions(this.record);
        
        // use toolbar from gridPanel
        this.tbar = new Ext.Toolbar({
            defaults: {height: 55},
            items: [{
                xtype: 'buttongroup',
                columns: 8,
                items: [
                    Ext.apply(new Ext.Button(this.action_deleteRecord), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_reply), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_replyAll), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_forward), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.SplitButton(this.action_print), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign:'top',
                        arrowAlign:'right'
                    })
                ]
            }]
        });
        
    },
    
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
        this.showMessage();
    },
    
    showMessage: function() {
        this.layout.setActiveItem(this.getSingleRecordPanel());
        this.updateDetails(this.record, this.getSingleRecordPanel().body);
    }
});

Tine.Felamimail.MessageDisplayDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 700,
        name: 'TineFelamimailMessageDisplayDialog_' + id,
        contentPanelConstructor: 'Tine.Felamimail.MessageDisplayDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};