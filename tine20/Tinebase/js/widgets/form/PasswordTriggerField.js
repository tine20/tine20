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
     * @cfg {Boolean} locked: true,
     */
    locked: true,
    /**
     * @cfg {Boolean} clipboard: true,
     */
    clipboard: true,

    itemCls: 'tw-passwordTriggerField',
    enableKeyEvents: true,

    initComponent: function() {
        // NOTE: we need to have this in the instance - otherwise we'd overwrite the prototype
        this.defaultAutoCreate = {tag: "input", type: "password", size: "16", autocomplete: "off"};

        this.defaultAutoCreate.type = this.locked ? 'password' : 'text';
        Tine.Tinebase.widgets.form.PasswordTriggerField.superclass.initComponent.apply(this, arguments);
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
        if (this.el.dom.type === 'text') {
            this.el.dom.type = 'password';
            this.triggers[0].addClass('locked');
        } else {
            this.el.dom.type = 'text';
            this.triggers[0].removeClass('locked');
        }
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
