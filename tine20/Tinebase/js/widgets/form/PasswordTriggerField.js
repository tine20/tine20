/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

/**
 * Simple password field widget to allow toggling between text and password field
 *
 * @namespace   Tine.Tinebase.widgets.form
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @class       Tine.Tinebase.widgets.form.PasswordTriggerField
 * @extends     Ext.form.TriggerField
 */
Tine.Tinebase.widgets.form.PasswordTriggerField = Ext.extend(Ext.form.TwinTriggerField, {
    /**
     * @cfg {Boolean} unLockable: true,
     */
    unLockable: true,
    /**
     * @cfg {Boolean} locked: true,
     */
    locked: true,
    /**
     * @cfg {Boolean} clipboard: true,
     */
    clipboard: true,
    /**
     * @cfg {Boolean} allowBrowserPasswordManager
     */
    allowBrowserPasswordManager: false,
    hiddenPasswordChr: '●',
    
    itemCls: 'tw-passwordTriggerField',
    enableKeyEvents: true,

    initComponent: function() {
        // NOTE: we need to have this in the instance - otherwise we'd overwrite the prototype
        this.defaultAutoCreate = {tag: "input", type: "password", size: "16", autocomplete: "off"};

        this.defaultAutoCreate.type = (this.locked && this.allowBrowserPasswordManager) ? 'password' : 'text';
        if (!this.allowBrowserPasswordManager) {
            this.initPreventBrowserPasswordManager();
        }

        if (!this.unLockable) {
            this.afterIsRendered().then(() => { this.getTrigger(0).hide(); });
        }
    
        Tine.Tinebase.widgets.form.PasswordTriggerField.superclass.initComponent.apply(this, arguments);
    },

    initPreventBrowserPasswordManager: function() {
        if (! this.allowBrowserPasswordManager) {
            this.afterIsRendered().then(() => {
                this.el.on('paste', this.transformInput, this);
                this.el.on('keypress', this.transformInput, this);
                this.el.on('keydown', this.transformInput, this);
            });
            
            this.getValue = () => {
                return this.locked ? this.value || ''
                    : Tine.Tinebase.widgets.form.PasswordTriggerField.superclass.getValue.call(this);
            };
            
            this.setValue = (value) => {
                this.value = value;
                this.afterIsRendered().then(() => {
                    this.setRawValue(this.locked ? this.hiddenPasswordChr.repeat(this.value.length) : this.value);
                });
            }
        }
    },

    transformInput: function(e) {
        e = e.browserEvent || e;
        if (!this.locked || !this.editable) return;
        if (e.type === 'keydown' && e.keyCode === 229) return _.defer(_.bind(this.transformIMEInput, this, e));
        if (e.type === 'keydown' && _.indexOf([8 /* BACKSPACE */, 46 /* DELETE */], e.keyCode) < 0) return;
        if (e.type === 'keypress' && e.metaKey /* APPLE CMD */ && e.keyCode == 118 /* v */) return;
        if (e.type === 'keypress' && _.indexOf([13 /* ENTER */], e.keyCode) >= 0) return;
        Ext.lib.Event.stopEvent(e);

        clearTimeout(this.selectTextTimeout);
        let start = e.target.selectionStart;
        const end = e.target.selectionEnd;
        const valueArray = (this.getValue() || '').split('');
        const replacement = e.clipboardData ? e.clipboardData.getData('text') : String.fromCharCode(e.keyCode);
        
        // NOTE: keydown & keypress have different keyCodes!
        if (e.type === 'keydown' && _.indexOf([8 /*BACKSPACE*/, 46 /*DELETE*/], e.keyCode) > -1) {
            start = start - (e.keyCode === 8 /*BACKSPACE*/ && start === end);
            valueArray.splice(start, Math.abs(end-start)||1);
        } else {
            valueArray.splice(start, end-start, replacement);
            start = start + replacement.length;
        }
        this.setValue(valueArray.join(''));
        this.selectTextTimeout = setTimeout(() => {this.selectText(start, start)}, 20);
    },
    
    // handle android IME keyboard
    // NOTE: rawValue is updated before this event
    transformIMEInput: function(e) {
        Ext.lib.Event.stopEvent(e);
        clearTimeout(this.selectTextTimeout);
        
        const start = e.target.selectionStart;
        const valueArray = (this.getValue() || '').split('');
        const raw = this.getRawValue();
        const replacement = raw.split(this.hiddenPasswordChr).join('');
        const deleteCount = valueArray.length - (raw.length - replacement.length);
        
        valueArray.splice(start  - replacement.length, deleteCount, replacement);
        
        this.setValue(valueArray.join(''));
        this.selectTextTimeout = setTimeout(() => {this.selectText(start, start)}, 20);
    },
    
    initTrigger: function () {
        Tine.Tinebase.widgets.form.PasswordTriggerField.superclass.initTrigger.apply(this, arguments);
        this.triggers[0].set({'ext:qtip': i18n._('Cleartext/Hidden')});
        this.triggers[1].set({'ext:qtip': i18n._('Copy to Clipboard')});
        if (this.locked) {
            this.triggers[0].addClass('locked');
        }
        if (! this.clipboard) {
            this.triggers[1].hide();
        }
    },

    onTrigger1Click: function () {
        this.triggers[0][(this.locked ? 'remove' : 'add') + 'Class']('locked');
        
        if (this.allowBrowserPasswordManager) {
            this.el.dom.type = this.locked ? 'text' : 'password';
        } else {
            this.value = this.locked ? this.value : this.getRawValue();
            this.setRawValue(this.locked ? this.value : this.hiddenPasswordChr.repeat(this.value.length));
        }
        this.locked = !this.locked;
        this.focus();
    },

    onTrigger2Click: function () {
        // NOTE: password fields can not be copied
        var type = this.el.dom.type;
        this.el.dom.type = 'text';
        this.el.dom.select();
        document.execCommand("copy");
        this.el.dom.type = type;
        this.selectText(String(this.getValue()).length);
    }
});

Ext.reg('tw-passwordTriggerField', Tine.Tinebase.widgets.form.PasswordTriggerField);
