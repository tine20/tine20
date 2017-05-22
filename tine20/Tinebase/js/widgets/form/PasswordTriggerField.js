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
Tine.Tinebase.widgets.form.PasswordTriggerField = Ext.extend(Ext.form.TriggerField, {
    itemCls: 'tw-passwordTriggerField',
    enableKeyEvents: true,

    defaultAutoCreate: {tag: "input", type: "password", size: "16", autocomplete: "off"},

    initTrigger: function () {
        Tine.Tinebase.widgets.form.PasswordTriggerField.superclass.initTrigger.apply(this, arguments);
        this.trigger.addClass('locked');
    },

    onTriggerClick: function () {
        if (this.el.dom.type === 'text') {
            this.el.dom.type = 'password';
            this.trigger.addClass('locked');
        } else {
            this.el.dom.type = 'text';
            this.trigger.removeClass('locked');
        }
    }
});

Ext.reg('tw-passwordTriggerField', Tine.Tinebase.widgets.form.PasswordTriggerField);
