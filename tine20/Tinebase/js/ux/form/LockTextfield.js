/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * Generic widget for a twin trigger textfield
 *
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.LockTextfield
 * @extends     Ext.form.TriggerField
 */
Ext.ux.form.LockTextfield = Ext.extend(Ext.form.TriggerField, {
    hiddenFieldId: '',
    hiddenFieldData: '',
    
    triggerClassLocked: 'x-form-locked-trigger',
    triggerClassUnlocked: 'x-form-unlocked-trigger',
    
    /**
     * @private
     */
    initComponent: function() {
        this.triggerClass = (this.hiddenFieldData == '1') 
            ? this.triggerClassUnlocked 
            : this.triggerClassLocked;

        Ext.ux.form.LockTextfield.superclass.initComponent.call(this);
    },
    
    onRender:function(ct, position) {
        Ext.ux.form.LockTextfield.superclass.onRender.call(this, ct, position); // render the textfield
        this.hiddenBox = ct.parent().createChild({tag:'input', type:'hidden', name: this.hiddenFieldId, id: this.hiddenFieldId, value: this.hiddenFieldData });
        Ext.ComponentMgr.register(this.hiddenBox);
    },
    
    onTriggerClick: function() {
        //this.hiddenFieldData = (this.hiddenFieldData == '1') ? '0' : '1';
        //this.triggerClass = (this.hiddenFieldData == '1') ? 'x-form-unlocked-trigger' : 'x-form-locked-trigger';

        var _currentValue = Ext.getCmp(this.hiddenFieldId).getValue();

        if (_currentValue == '0') {
            Ext.getCmp(this.hiddenFieldId).dom.value = '1';
            this.trigger.removeClass(this.triggerClassLocked);
            this.trigger.addClass(this.triggerClassUnlocked);
        } else {
            Ext.getCmp(this.hiddenFieldId).dom.value = '0';
            this.trigger.removeClass(this.triggerClassUnlocked);
            this.trigger.addClass(this.triggerClassLocked);
        }
    }
});
Ext.reg('lockTextfield', Ext.ux.form.LockTextfield);
