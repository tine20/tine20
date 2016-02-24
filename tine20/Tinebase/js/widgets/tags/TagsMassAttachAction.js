/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMassAttachAction
 * @extends     Ext.Action
 */
Tine.widgets.tags.TagsMassAttachAction = function(config) {
    config.text = config.text ? config.text : _('Add Tags');
    config.iconCls = 'action_tag';
    config.handler = config.handler ? config.handler : this.handleClick.createDelegate(this);
    config.scope = config.scope ? config.scope : this.handleClick.createDelegate(this);
    Ext.apply(this, config);
    
    this.store = new Ext.data.SimpleStore({
        fields: Tine.Tinebase.Model.Tag
    });
    
    this.store.on('add', this.manageOkBtn, this);
    this.store.on('remove', this.manageOkBtn, this);
    
    Tine.widgets.tags.TagsMassAttachAction.superclass.constructor.call(this, config);
};

Ext.extend(Tine.widgets.tags.TagsMassAttachAction, Ext.Action, {
    
    /**
     * called when tags got updates
     * 
     * @type Function
     */
    updateHandler: Ext.emptyFn,
    
    /**
     * scope of update handler
     * 
     * @type Object
     */
    updateHandlerScope: null,
    
    loadMask: null,
    
    /**
     * @cfg {mixed} selectionModel
     * 
     * selection model (required)
     */
    selectionModel: null,
    
    /**
     * @cfg {function} recordClass
     * 
     * record class of records to filter for (required)
     */
    recordClass: null,
    
    getFormItems: function() {
        return new Tine.widgets.grid.PickerGridPanel({
            height: 'auto',
            searchComboConfig: {app: this.app},
            recordClass: Tine.Tinebase.Model.Tag,
            store: this.store,
            labelRenderer: Tine.Tinebase.common.tagRenderer
        });
    },
    
    manageOkBtn: function() {
        if (this.win && this.win.okButton) {
            this.win.okButton.setDisabled(! this.store.getCount());
        }
    },
    
    handleClick: function() {
        // NOTE: in gridPanels ctxMenu (and so this action) is only created once
        this.store.removeAll();
        
        this.win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 300,
            height: 300,
            padding: '5px',
            modal: true,
            closeAction: 'hide', // mhh not working :-(
            title: _('Select Tags'),
            items: [{
                xtype: 'form',
                buttonAlign: 'right',
                border: false,
                layout: 'fit',
                items: this.getFormItems(),
                buttons: [{
                    text: _('Cancel'),
                    minWidth: 70,
                    scope: this,
                    handler: this.onCancel,
                    iconCls: 'action_cancel'
                }, {
                    text: _('Ok'),
                    ref: '../../../okButton',
                    disabled: this.store ? !this.store.getCount() : true,
                    minWidth: 70,
                    scope: this,
                    handler: this.onOk,
                    iconCls: 'action_saveAndClose'
                }]
            }]
        });
    },
    
    onCancel: function() {
        this.win.close();
    },
    
    onOk: function() {
        var tags = [];
        this.store.each(function(r) {
            tags.push(r.data);
        }, this);
        
        if (! tags) {
            this.win.close();
            return;
        }
        
        this.loadMask = new Ext.LoadMask(this.win.getEl(), {msg: _('Attaching Tag')});
        this.loadMask.show();
        
        var filter = this.selectionModel.getSelectionFilter();
        var filterModel = this.recordClass.getMeta('appName') + '_Model_' +  this.recordClass.getMeta('modelName') + 'Filter';
        
        // can't use Ext direct because the timeout is not configurable
        //Tine.Tinebase.attachTagToMultipleRecords(filter, filterModel, tag, this.onSuccess.createDelegate(this));
        Ext.Ajax.request({
            scope: this,
            timeout: 1800000, // 30 minutes
            success: this.onSuccess.createDelegate(this),
            params: {
                method: 'Tinebase.attachMultipleTagsToMultipleRecords',
                filterData: filter,
                filterName: filterModel,
                tags: tags
            },
            failure: function(response, options) {
                this.loadMask.hide();
                Tine.Tinebase.ExceptionHandler.handleRequestException(response, options);
            }
        });
    },
    
    onSuccess: function() {
        this.updateHandler.call(this.updateHandlerScope || this);
        this.loadMask.hide();
        this.win.close();
    }
});
