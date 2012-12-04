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
    config.text = config.text ? config.text : _('Add Tag');
    config.iconCls = 'action_tag';
    config.handler = this.handleClick.createDelegate(this);
    Ext.apply(this, config);
    
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
        this.tagSelect = new Tine.widgets.tags.TagCombo({
            hideLabel: true,
            anchor: '100%',
            onlyUsableTags: true,
            app: this.app,
            forceSelection: true,
            listeners: {
                scope: this,
                render: function(field){field.focus(false, 500);},
                select: function() {
                    this.onOk();
                }
            }
        });
        
        return [{
            xtype: 'label',
            text: _('Attach the following tag to all selected items:')
        }, this.tagSelect
        ];
    },
    
    handleClick: function() {
        
        this.okButton = new Ext.Button({
            text: _('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onOk,
            disabled: true,
            iconCls: 'action_saveAndClose'
        });
        
        this.win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 300,
            height: 150,
            padding: '5px',
            modal: true,
            title: _('Select Tag'),
            items: [{
                xtype: 'form',
                buttonAlign: 'right',
                padding: '5px',
                items: this.getFormItems(),
                buttons: [{
                    text: _('Cancel'),
                    minWidth: 70,
                    scope: this,
                    handler: this.onCancel,
                    iconCls: 'action_cancel'
                }, this.okButton]
            }]
        });
    },
    
    onCancel: function() {
        this.win.close();
    },
    
    onOk: function() {
        var tag = this.tagSelect.getValue();
        
        if(! tag) {
            return;
        }
        
        this.okButton.enable();
        
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
                method: 'Tinebase.attachTagToMultipleRecords',
                filterData: filter,
                filterName: filterModel,
                tag: tag
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
