/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext*/

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * a yes/no combo box
 *
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.BooleanCombo
 * @extends     Ext.form.ComboBox
 */
Ext.ux.form.BooleanCombo = Ext.extend(Ext.form.ComboBox, {
    mode: 'local',
    forceSelection: true,
    allowBlank: false,
    triggerAction: 'all',
    editable: false,

    initComponent: function () {
        this.store = [[true, i18n._('Yes')], [false, i18n._('No')]];
        this.supr().initComponent.call(this);
    }
});
Ext.reg('booleancombo', Ext.ux.form.BooleanCombo);
