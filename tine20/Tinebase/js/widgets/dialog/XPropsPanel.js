/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

export default Ext.extend(Ext.grid.PropertyGrid, {
    title: 'xprops',

    initComponent: function() {
        this.on('afterrender', () => {
            const editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
            editDialog.on('load', this.onRecordLoad, this);
            editDialog.on('recordUpdate', this.onRecordUpdate, this);

            // NOTE: in case we are rendered after record was load
            this.onRecordLoad(editDialog, editDialog.record);
        }, this);

        this.bbar = [{
            text: i18n._('Create xprop'),
            iconCls: 'action_add',
            handler: () => {
                Ext.Msg.prompt(window.i18n._('xprop Name'), window.i18n._('Please enter the name of the new xprop'), function(btn, propName) {
                    if (btn == 'ok') {
                        this.setProperty(propName, '', true);
                    }
                }, this);
            }
        }, {
            text: i18n._('Delete xprop'),
            iconCls: 'action_delete',
            disabled: true,
            handler: () => {
                const prop = this.propStore.getProperty(this.selModel.getSelectedCell()[0]);
                this.removeProperty(prop.data.name);
            }
        }]
        this.supr().initComponent.call(this);
        this.selModel.on('selectionchange', () => {
            this.getBottomToolbar().items.items[1].setDisabled(!this.selModel.getSelectedCell());
        })
    },

    onRecordLoad: function(editDialog, record) {
        this.setSource(Tine.Tinebase.common.assertComparable({... record.get('xprops')}));
    },

    onRecordUpdate: function(editDialog, record) {
        record.set('xprops', Tine.Tinebase.common.assertComparable({... this.getSource()}));
    }
});