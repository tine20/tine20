/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.tags');

Tine.widgets.tags.TagsMassAttachAction = function(config) {
    config.text = config.text ? config.text : _('Add Tag');
    config.iconCls = 'action_tag';
    config.handler = this.handleClick.createDelegate(this);
    Ext.apply(this, config);
    
    Tine.widgets.tags.TagsMassAttachAction.superclass.constructor.call(this, config);
};

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMassAttachAction
 * @extends     Ext.Action
 */
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
            listeners: {
                scope: this,
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
        this.win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 300,
            height: 150,
            modal: true,
            title: _('Select Tag'),
            items: [{
                xtype: 'form',
                buttonAlign: 'right',
                items: this.getFormItems(),
                buttons: [{
                    text: _('Cancel'),
                    minWidth: 70,
                    scope: this,
                    handler: this.onCancel,
                    iconCls: 'action_cancel'
                }, {
                    text: _('Ok'),
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
        var tag = this.tagSelect.getValue();
        
        var filter = this.selectionModel.getSelectionFilter();
        var filterModel = this.recordClass.getMeta('appName') + '_Model_' +  this.recordClass.getMeta('modelName') + 'Filter';
        
        Tine.Tinebase.attachTagToMultipleRecords(filter, filterModel, tag, this.onSuccess.createDelegate(this));
    },
    
    onSuccess: function() {
        this.updateHandler.call(this.updateHandlerScope || this);
        
        this.win.close();
    }
});