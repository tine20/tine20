/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const BoilerplatePanel = Ext.extend(Ext.Panel, {
    layout: 'form',
    border: true,
    frame: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '100%',
        labelSeparator: ''
    },

    /**
     * arguments for the last getApplicableBoilerplates call
     */
    gabpArgs: null,

    initComponent () {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.recordClass = Tine.Tinebase.data.RecordMgr.get('Sales.Document_Boilerplate');
        this.title = this.app.i18n._('More Boilerplates');

        // selected boilerplates -> shown in dialog
        this.store = new Ext.data.JsonStore({
            fields: this.recordClass,
            sortInfo: {
                field: 'name',
                direction: 'ASC'
            }
        });

        this.items = [];
        this.supr().initComponent.call(this);
    },

    onRecordLoad: async function(editDialog, record) {
        const boilerplates = record.get('boilerplates') || [];
        this.store.loadData(boilerplates);
        this.onBoilerplatesLoad(editDialog);

        this.loadBoilerplatesIf(editDialog, record);
    },

    onRecordUpdate: async function(editDialog, record) {
        this.store.each((boilerplate) => {
            const fieldName = `boilerplate_${boilerplate.get('name')}`;
            let field = editDialog.getForm().findField(fieldName);
            boilerplate.set('boilerplate', field.getValue());
        });

        await this.loadBoilerplatesIf(editDialog, record);
    },

    onBoilerplatesLoad(editDialog) {
        const fields = editDialog.getForm().items.items.filter((field) => { return String(field.name).match(/^boilerplate_/) });
        this.store.each((boilerplate, idx) => {
            const name = `boilerplate_${boilerplate.get('name')}`;
            const fieldLabel = `${this.app.i18n._('Boilerplate')}: ${Tine.Tinebase.EncodingHelper.encode(boilerplate.getTitle())}`;
            let field = editDialog.getForm().findField(name);
            if (field) {
                fields.remove(field);
            } else {
                field = new Ext.form.TextArea({
                    fieldLabel,
                    name,
                    enableKeyEvents: true,
                    height: 140
                });
                // @TODO sorting
                this.add(field);
                editDialog.relayEvents(field, ['change']);
            }
            this.assertChangeListener(boilerplate.get('name'), field);
            field.setValue(boilerplate.get('boilerplate'));
            field.originalValue = boilerplate.get('boilerplate');
            field.setFieldLabel(fieldLabel);
        });
        // remove unused fields
        fields.forEach((field) => { this.remove(field) });

        // show/hide tabstripe
        this.ownerCt[(this.items.getCount() ? 'un' : '') +'hideTabStripItem'](this);
    },

    assertChangeListener(name, field) {
        const flagName = `bp-${this.id}-listener`;
        if (!field[flagName]) {
            field.on('keyup', () => {
                const boilerplate = this.store.getAt(this.store.findExact('name', name));
                boilerplate.set('locally_changed', field.getValue() !== field.originalValue);
                field.setFieldLabel(Tine.Tinebase.EncodingHelper.encode(boilerplate.getTitle()));
            });
            field[flagName] = true;
        }
    },

    async loadBoilerplatesIf (editDialog, record) {
        const gabpArgs = Tine.Tinebase.common.assertComparable([
            record.constructor.getPhpClassName(),
            record.get('date')?.format ? record.get('date').format(Date.patterns.ISO8601Long) : null,
            record.get('customer_id')?.original_id || record.get('customer_id')?.id,
            record.get('document_category'),
            record.get('document_language')
        ]);
        const statusField = this.editDialog.fields[this.editDialog.statusFieldName]
        const booked = statusField.store.getById(statusField.getValue())?.json.booked

        if (String(this.gabpArgs) !== String(gabpArgs) && !booked) {
            this.gabpArgs = gabpArgs;
            const { results } = await Tine.Sales.getApplicableBoilerplates(...gabpArgs);
            this.applicableBoilerplatesData = results;

            await this.applicableBoilerplatesData.asyncForEach(async (applicableBoilerplateData) => {
                const applicableBoilerplate = Tine.Tinebase.data.Record.setFromJson(applicableBoilerplateData, this.recordClass);
                const existingBoilerplate = this.store.getAt(this.store.findExact('name', applicableBoilerplate.get('name')));

                if (! existingBoilerplate) {
                    applicableBoilerplate.data.original_id = applicableBoilerplate.id; // don't modify
                    this.store.addSorted(applicableBoilerplate);
                } else {
                    //@TODO: don't replace when document is in closed state -> even don't ask!
                    const isEqual = existingBoilerplate.get('boilerplate') === applicableBoilerplate.get('boilerplate');
                    const existingIsLocallyChanged = !!+existingBoilerplate.get('locally_changed');
                    const applicableIsNewer = applicableBoilerplate.getMTime() > existingBoilerplate.getMTime();

                    let option = isEqual || (existingIsLocallyChanged && !applicableIsNewer) ? 'existing' : 'applicable';

                    if (option === 'applicable' && existingIsLocallyChanged) {
                        // ask before replace locally changed!
                        const name = Tine.Tinebase.EncodingHelper.encode(existingBoilerplate.get('name'));
                        option = await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                            title: this.app.formatMessage('Choose Boilerplate', {name}),
                            questionText: this.app.formatMessage('Please choose which boilerplate you want to use as { name }', {name}),
                            height: 350,
                            allowCancel: false,
                            options: [
                                { text: '<b>' + this.app.formatMessage('Existing:') + '&nbsp;' + Tine.Tinebase.EncodingHelper.encode(existingBoilerplate.getTitle()) + '</b><br>' + Tine.Tinebase.EncodingHelper.encode(existingBoilerplate.get('boilerplate')), name:  'existing'},
                                { text: '<b>' + this.app.formatMessage('Applicable:') + '&nbsp;'+ Tine.Tinebase.EncodingHelper.encode(applicableBoilerplate.getTitle()) + '</b><br>' + Tine.Tinebase.EncodingHelper.encode(applicableBoilerplate.get('boilerplate')), name:  'applicable'}
                            ]
                        });
                    }

                    if (option === 'applicable') {
                        this.store.remove(existingBoilerplate);
                        this.store.addSorted(applicableBoilerplate);
                    }
                }
            });

            record.set('boilerplates', Tine.Tinebase.common.assertComparable(_.map(this.store.data.items, 'data')));
            this.onBoilerplatesLoad(editDialog);
        }
    },

    setOwnerCt: function(ct) {
        this.ownerCt = ct;

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: in case record is already loaded
        if (!this.setOwnerCt.initialOnRecordLoad && !this.editDialog.record.store) {
            this.setOwnerCt.initialOnRecordLoad = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }

    }
});

export {
    BoilerplatePanel
}
