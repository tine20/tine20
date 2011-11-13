/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMassDetachAction
 * @extends     Ext.Action
 */
Tine.widgets.tags.TagsMassDetachAction = function(config) {
    config.text = config.text ? config.text : _('Detach tag(s)');
    config.iconCls = 'action_tag_delete';
    config.handler = this.handleClick.createDelegate(this);
    Ext.apply(this, config);
    
    Tine.widgets.tags.TagsMassDetachAction.superclass.constructor.call(this, config);
};

Ext.extend(Tine.widgets.tags.TagsMassDetachAction, Ext.Action, {
    
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
    
    formPanel: null,
    
    initFormPanel: function() {
        this.formPanel = new Tine.widgets.tags.TagToggleBox({
            selectionModel: this.selectionModel,
            recordClass: this.recordClass,
            callingAction: this,
            mode: 'detach'
        });
        
        this.formPanel.on('cancel', function() {
            this.purgeListeners();
            this.win.close();
        });
        
        this.formPanel.on('updated', function() {
            this.callingAction.onSuccess();
        });
        
    },
     
    handleClick: function() {
        this.initFormPanel();
        this.win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 300,
            height: 150,
            modal: true,       
            title: _('Select Tag(s) to detach'),
            items: this.formPanel

        });
        
        this.formPanel.win = this.win;
        
    },
 
    onSuccess: function() {
        this.updateHandler.call(this.updateHandlerScope || this);
        this.win.close();
    }
});
