import TierTypeCombo from "./TierTypeCombo";
import FieldClipboardPlugin from 'ux/form/FieldClipboardPlugin'

Ext.ux.ItemRegistry.registerItem('Filemanager-Node-EditDialog-NodeTab-CenterPanel', Ext.extend(Ext.Panel, {
    layout: 'fit',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('EFile');

        const nodeFieldManager = _.bind(Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager, 'Filemanager', 'Node', _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

        this.tierRefNumberField = nodeFieldManager('efile_tier_ref_number', {
            readOnly: true,
            columnWidth: 0.75,
            plugins: [new FieldClipboardPlugin()]
        });
        this.tierTypeCombo = new TierTypeCombo({
            columnWidth: 0.25,
        });

        this.items = {
            xtype: 'fieldset',
            layout: 'hfit',
            autoHeight: true,
            title: this.app.i18n._('eFile'),
            items: [{
                xtype: 'columnform',
                labelAlign: 'top',
                items: [[this.tierRefNumberField, this.tierTypeCombo]]
            }]
        };
        
        this.supr().initComponent.apply(this, arguments);
    },

    onRecordLoad: async function(editDialog, record) {
        const tierType = record.get('efile_tier_type');
        this.tierTypeCombo.setNode(record);
        
        this[tierType ? 'show' : 'hide']();
        
        if (tierType === 'file') {
            // greetings to Dr. Knorn 
            const descriptionField = editDialog.getForm().findField('description');
            descriptionField.emptyText = this.app.i18n._('Enter contents');
            descriptionField.ownerCt.setTitle(this.app.i18n._('Contents'));
        }
    },

    onRecordUpdate: function(editDialog, record) {
        record.set('efile_tier_type', this.tierTypeCombo.getValue());
    },

    onRender: function() {
        this.supr().onRender.apply(this, arguments);

        if (!this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);
    }
}), 2);
