/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

Tine.Tinebase.widgets.form.JsonField = Ext.extend(Ext.form.Field, {
    defaultAutoCreate: {tag: 'div'},

    afterRender: function() {

        Tine.Tinebase.widgets.form.JsonField.superclass.afterRender.apply(this, arguments);

        import('widgets/ace').then(() => {
            this.ed = ace.edit(this.el.id, {
                mode: 'ace/mode/json',
                fontFamily: 'monospace',
                fontSize: 12
            });

            this.setValue(this.getValue() || '');
        });
    },

    setValue: function(value) {
        Tine.Tinebase.widgets.form.JsonField.superclass.setValue.apply(this, arguments);

        if (this.ed) {
            this.ed.setValue(Ext.isString(value) ? value : JSON.stringify(value, undefined, 4), -1);
        }
    }
});

Ext.reg('tw-jsonfield', Tine.Tinebase.widgets.form.JsonField);
