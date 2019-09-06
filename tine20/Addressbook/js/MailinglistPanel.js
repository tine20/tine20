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

    checkboxes: {},

    initComponent: function() {
        var _ = window.lodash,
            panel = this;

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

        var checkboxLabels = {
            'sieveKeepCopy': this.app.i18n._('Keep copy of group mails'),
            'sieveAllowExternal': this.app.i18n._('Forward external mails'),
            'sieveAllowOnlyMembers': this.app.i18n._('Only forward member mails'),
            'sieveForwardOnlySystem': this.app.i18n._('Only forward to system email accounts')
        }, checkboxItems = [this.isMailinglistCheckbox];
        _.forOwn(checkboxLabels, function(label, key) {
            panel.checkboxes[key] = new Ext.form.Checkbox({
                disabled: true,
                boxLabel: label
            });
            checkboxItems.push(panel.checkboxes[key]);
        });

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
                items: checkboxItems}
            ]
        }];

        this.supr().initComponent.call(this);
    },

    onMailinglistCheck: function(cb, checked) {
        var _ = window.lodash;
        _.forOwn(this.checkboxes, function(checkbox, key) {
            checkbox.setReadOnly(!checked);
            checkbox.setDisabled(!checked);
        });
    },

    onRecordLoad: function(editDialog, record, ticketFn) {
        var _ = window.lodash,
            evalGrants = editDialog.evalGrants;

        var isMailinglist = _.get(record, 'data.xprops.useAsMailinglist', false);

        // TODO check right here, too
        var hasRight = Tine.Tinebase.common.hasRight('manage', 'Addressbook', 'list_email_options'),
            hasRequiredGrant = !evalGrants
            || (_.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant) && hasRight),
            mailinglistDisabled = ! (_.get(record, 'data.account_grants.adminGrant', false) && hasRight);

        this.isMailinglistCheckbox.setDisabled(mailinglistDisabled);
        this.isMailinglistCheckbox.setValue(isMailinglist);

        _.forOwn(this.checkboxes, function(checkbox, key) {
            checkbox.setValue(_.get(record, 'data.xprops.' + key, false));
            checkbox.setDisabled(! isMailinglist);
        });

        this.setReadOnly(!hasRequiredGrant);
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        this.isMailinglistCheckbox.setDisabled(readOnly);
    },

    onRecordUpdate: function(editDialog, record) {
        // TODO set record xprops
        var xprops = record.get('xprops'),
            isMailingList = this.isMailinglistCheckbox.getValue();

        if (! xprops || Ext.isArray(xprops)) {
            xprops = {};
        }
        xprops.useAsMailinglist = isMailingList;
        _.forOwn(this.checkboxes, function(checkbox, key) {
            xprops[key] = isMailingList ? checkbox.getValue() : false;
        });

        record.set('xprops', xprops);
    }
});
