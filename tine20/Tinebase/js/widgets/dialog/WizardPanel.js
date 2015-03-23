/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.dialog');

/**
 * Wizard Panel
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.WizardPanel
 * @extends     Ext.Panel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.dialog.WizardPanel = Ext.extend(Ext.Panel, {
    
    // private config overrides
    cls: 'tw-editdialog',
    border: false,
    layout: 'card',
    activeItem: 0,
    
    /**
     * init this wizard
     */
    initComponent: function() {
        this.addEvents(
            /**
             * @event cancel
             * @desc Fired when user pressed cancel button
             * @param {WizardPanel} this
             * @parm {Ext.Panel} active panel
             */
            'cancel',
            
            /**
             * @event finish
             * @desc Fired when user pressed finish button
             * @param {WizardPanel} this
             * @parm {Ext.Panel} active panel
             */
            'finish'
        );
        
        this.initFooterBar();
        
        this.on('afterrender', this.onAfterRender, this);
        Tine.widgets.dialog.WizardPanel.superclass.initComponent.call(this);
    },
    
    /**
     * back button handler
     */
    onBackButton: function() {
        return Ext.isFunction(this.layout.activeItem.onBackButton) ?
            this.layout.activeItem.onBackButton() :
            this.navigate(-1);
    },
    
    /**
     * next button handler
     */
    onNextButton: function() {
        return Ext.isFunction(this.layout.activeItem.onNextButton) ?
            this.layout.activeItem.onNextButton() :
            this.navigate(+1);
    },
    
    /**
     * cancel button handler
     */
    onCancelButton: function() {
        if (Ext.isFunction(this.layout.activeItem.onCancelButton)) {
            return this.layout.activeItem.onCancelButton();
        }
        
        else {
            this.fireEvent('cancel', this, this.layout.activeItem);
        }
    },
    
    /**
     * finish button handler
     */
    onFinishButton: function() {
        if (Ext.isFunction(this.layout.activeItem.onFinishButton)) {
            return this.layout.activeItem.onFinishButton();
        }
        
        else {
            this.fireEvent('finish', this, this.layout.activeItem);
        }
    },
    
    /**
     * navigate to panel
     * 
     * @param {Number/String} ref
     */
    navigate: function(ref) {
        var currentIndex = this.items.indexOf(this.layout.activeItem),
            toActivatePanel = Ext.isNumber(ref) ? 
                this.items.get(currentIndex + ref) :
                this.items.get(ref);
        
        if (toActivatePanel) {
            this.layout.setActiveItem(toActivatePanel);
            toActivatePanel.doLayout();
        }
        
        this.manageButtons();
    },
    
    /**
     * manage footer buttons
     */
    manageButtons: function() {
        Ext.each(['back', 'next', 'cancel', 'finish'], function(key) {
            if (this[key + 'Button'] && Ext.isFunction(this[key + 'IsAllowed'])) {
                this[key + 'Button'].setDisabled(!this[key + 'IsAllowed']());
            }
        }, this);
    },
    
    /**
     * check if back button is allowed
     */
    backIsAllowed: function() {
        var panelCondition = Ext.isFunction(this.layout.activeItem.backIsAllowed) ?
            this.layout.activeItem.backIsAllowed() : true;
        
        return panelCondition && this.items.indexOf(this.layout.activeItem) > 0;
    },
    
    /**
     * check if next button is allowed
     */
    nextIsAllowed: function() {
        var panelCondition = Ext.isFunction(this.layout.activeItem.nextIsAllowed) ?
            this.layout.activeItem.nextIsAllowed() : true;
        
        return panelCondition && this.items.indexOf(this.layout.activeItem) < this.items.getCount() -1;
    },
    
    /**
     * check if cancel button is allowed
     */
    cancelIsAllowed: function() {
        var panelCondition = Ext.isFunction(this.layout.activeItem.cancelIsAllowed) ?
            this.layout.activeItem.cancelIsAllowed() : true;
        
        return panelCondition && true;
    },
    
    /**
     * check if finish button is allowed
     */
    finishIsAllowed: function() {
        var panelCondition = Ext.isFunction(this.layout.activeItem.finishIsAllowed) ?
            this.layout.activeItem.finishIsAllowed() : true;
        
        return panelCondition && this.items.indexOf(this.layout.activeItem) == this.items.getCount() -1;
    },
    
    /**
     * init footer bar
     */
    initFooterBar: function() {
        this.fbar = ['->',{
            text: _('Back'),
            minWidth: 70,
            ref: '../backButton',
            iconCls: 'action_previous',
            scope: this,
            handler: this.onBackButton
        }, {
            text: _('Next'),
            minWidth: 70,
            ref: '../nextButton',
            iconCls: 'action_next',
            scope: this,
            handler: this.onNextButton
        }, {
            text: _('Cancel'),
            minWidth: 70,
            ref: '../cancelButton',
            iconCls: 'action_cancel',
            scope: this,
            handler: this.onCancelButton
        }, {
            text: _('Finish'),
            minWidth: 70,
            ref: '../finishButton',
            iconCls: 'action_saveAndClose',
            scope: this,
            handler: this.onFinishButton
        }];
    },
    
    /**
     * called after this wizard is rendered
     */
    onAfterRender: function() {
        this.manageButtons();
    }
});

Ext.reg('tw-wizardpanel', Tine.widgets.dialog.WizardPanel);