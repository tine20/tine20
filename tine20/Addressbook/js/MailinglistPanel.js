/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

Tine.Addressbook.MailinglistPanel = Ext.extend(Ext.Panel, {

    /**
     * @cfg {Tine.widgets.dialog.EditDialog}
     */
    editDialog: null,

    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,

    /**
     * @property {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: Tine.Addressbook.Model.Contact,

    requiredGrant: 'editGrant',
    layout: 'fit',
    border: false,

    initComponent: function() {
        this.app = this.app || Tine.Tinebase.appMgr.get('Addressbook');
        this.title = this.title || this.app.i18n._('Mailing List');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        this.isMailinglistCheckbox = new Ext.form.Checkbox({
            disabled: true,
            boxLabel: this.app.i18n._('This group is a mailing list'),
            listeners: {scope: this, check: this.onMailinglistCheck}
        });

        // TODO add description?
        // this.isMailinglistDescription = new Ext.form.Label({
        //     text: this.app.i18n._("Grants of a folder also apply recursively for all of its sub folders as long they don't have own grants itself.")
        // });

        // TODO add more checkboxes / xprops
        // this.pinProtectionCheckbox = new Ext.form.Checkbox({
        //     disabled: true,
        //     hidden: ! Tine.Tinebase.areaLocks.hasLock('Tinebase.datasafe'),
        //     boxLabel: this.app.i18n._('This folder is part of the data safe')
        // });
        // this.pinProtectionDescription = new Ext.form.Label({
        //     text: this.app.i18n._("If data safe protection is enabled, this folder and all it's contents is only shown if the data safe is opened.")
        // });

        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            items: [{
                layout: 'form',
                frame: true,
                hideLabels: true,
                width: '100%',
                items: [
                    this.isMailinglistCheckbox
                    // this.isMailinglistDescription,
                    // this.pinProtectionCheckbox,
                    // this.pinProtectionDescription
                ]}
            ]
        }];

        this.supr().initComponent.call(this);
    },

    onMailinglistCheck: function(cb, checked) {
        // TODO enable/disable other items
        // this.grantsGrid.setReadOnly(!checked);
        // this.pinProtectionCheckbox.setDisabled(!checked);
    },

    onRecordLoad: function(editDialog, record, ticketFn) {
        var _ = window.lodash,
            evalGrants = editDialog.evalGrants;

        var isMailinglist = lodash.get(record, 'data.xprops.use_as_mailinglist', false);

        // TODO check right here, too
        var hasRight = Tine.Tinebase.common.hasRight('manage', 'Addressbook', 'list_email_options'),
            hasRequiredGrant = !evalGrants
            || (_.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant) && hasRight);

        this.isMailinglistCheckbox.setDisabled(! (lodash.get(record, 'data.account_grants.adminGrant', false) && hasRight));
        this.isMailinglistCheckbox.setValue(isMailinglist);

        this.setReadOnly(!hasRequiredGrant);
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        this.isMailinglistCheckbox.setDisabled(readOnly);
    },

    onRecordUpdate: function(editDialog, record) {
        // TODO set record xprops
        var xprops = record.get('xprops');
        if (! xprops) {
            xprops = {};
        }
        xprops.use_as_mailinglist = this.isMailinglistCheckbox.getValue();

        record.set('xprops', xprops);
    }
});
