/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin.Groups');

/**
 * @namespace   Tine.Admin.Groups
 * @class       Tine.Admin.Groups.EditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 */
Tine.Admin.Groups.EditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'groupEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.Group,
    recordProxy: Tine.Admin.groupBackend,
    evalGrants: false,

    /**
     * @private
     */
    initComponent: function () {

        this.membersStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Account
        });

        Tine.Admin.Groups.EditDialog.superclass.initComponent.call(this);
    },

    /**
     * @private
     */
    getFormItems: function () {
        var tabpanelItems = [{
            title: this.app.i18n._(this.recordClass.getMeta('recordName')),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'north',
                xtype: 'columnform',
                border: false,
                autoHeight: true,
                items: [[{
                    columnWidth: 1,
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Group Name'),
                    name: 'name',
                    anchor: '100%',
                    allowBlank: false
                }], [{
                    columnWidth: 1,
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: this.app.i18n._('Description'),
                    grow: false,
                    preventScrollbars: false,
                    anchor: '100%',
                    height: 60
                }], [{
                    columnWidth: 0.5,
                    xtype: 'combo',
                    fieldLabel: this.app.i18n._('Visibility'),
                    name: 'visibility',
                    mode: 'local',
                    triggerAction: 'all',
                    allowBlank: false,
                    editable: false,
                    store: [['displayed', this.app.i18n._('Display in addressbook')], ['hidden', this.app.i18n._('Hide from addressbook')]],
                    listeners: {
                        scope: this,
                        select: function (combo, record) {
                            // disable container_id combo if hidden
                            this.getForm().findField('container_id').setDisabled(record.data.field1 === 'hidden');
                            if (record.data.field1 === 'hidden') {
                                this.getForm().findField('container_id').clearInvalid();
                            } else {
                                this.getForm().findField('container_id').isValid();
                            }
                        }
                    }
                }, {
                    columnWidth: 0.5,
                    xtype: 'tinerecordpickercombobox',
                    fieldLabel: this.app.i18n._('Saved in Addressbook'),
                    name: 'container_id',
                    blurOnSelect: true,
                    allowBlank: false,
                    listWidth: 250,
                    recordClass: Tine.Tinebase.Model.Container,
                    recordProxy: Tine.Admin.sharedAddressbookBackend,
                    disabled: this.record.get('visibility') === 'hidden'
                }], [{
                    columnWidth: 0.5,
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('E-mail'),
                    name: 'email',
                    anchor: '100%',
                    vtype: 'email',
                    maxLength: 255,
                    allowBlank: true
                }, {
                    columnWidth: 0.5,
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Only system accounts can be added to Addressbook group'),
                    name: 'account_only',
                    anchor: '100%',
                    value: true
                }]]
            }, {
                xtype: 'tinerecordpickergrid',
                title: this.app.i18n._('Group Members'),
                store: this.membersStore,
                region: 'center',
                anchor: '100% 100%',
                showHidden: true
            }]

        }, new Tine.widgets.activities.ActivitiesTabPanel({
            app: this.appName,
            record_id: (this.record && ! this.copyRecord) ? this.record.id : '',
            record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
        })];

        var adb = Tine.Tinebase.appMgr.get('Addressbook');
        if (
            Tine.Tinebase.registry.get('manageImapEmailUser') &&
            Tine.Tinebase.registry.get('manageSmtpEmailUser') &&
            adb.featureEnabled('featureMailinglist')
        ) {
            var mailingListPanel = new Tine.Addressbook.MailinglistPanel({
                editDialog: this
            });
            tabpanelItems.push(mailingListPanel);
        }

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items: tabpanelItems
        };
    },

    /**
     * is form valid?
     *
     * @return {Boolean}
     */
    isValid: function() {
        var result = Tine.Admin.Groups.EditDialog.superclass.isValid.call(this);
        var emailValue = this.getForm().findField('email').getValue();
        if (!Tine.Tinebase.common.checkEmailDomain(emailValue)) {
            this.getForm().markInvalid([{
                id: 'email',
                msg: this.app.i18n._("Domain is not allowed. Check your SMTP domain configuration.")
            }]);
            return false;
        }
        return result;
    },

    onAfterRecordLoad: function() {
        Tine.Admin.Groups.EditDialog.superclass.onAfterRecordLoad.call(this);
        this.membersStore.loadData(this.record.get('members'));
    },

    /**
     * @private
     */
    onRecordUpdate: function () {
        Tine.Admin.Groups.EditDialog.superclass.onRecordUpdate.call(this);

         // get group members
        var groupMembers = [];
        this.membersStore.each(function (record) {
            groupMembers.push(record.id);
        });

        // update record with form data
        this.record.set('members', groupMembers);
    }
});

/**
 * User Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.Groups.EditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 600,
        name: Tine.Admin.Groups.EditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.Groups.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
