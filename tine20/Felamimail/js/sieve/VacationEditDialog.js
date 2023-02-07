/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Felamimail.sieve');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.sieve.VacationEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is editing sieve filters (vacation and rules).</p>
 * <p>
 * </p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new VacationEditDialog
 */

Tine.Felamimail.sieve.VacationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    editDialog: null,

    /**
     * @private
     */
    windowNamePrefix: 'VacationEditWindow_',
    appName: 'Felamimail',
    asAdminModule:false,
    
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    readonlyReason: false,

    initComponent: function () {
        if (!this.recordProxy) {
            this.recordProxy = new Tine.Tinebase.data.RecordProxy({
                appName: this.asAdminModule ? 'Admin' : 'Felamimail',
                modelName: this.asAdminModule ? 'SieveVacation' : 'Vacation',
                recordClass: Tine.Felamimail.Model.Vacation,
                idProperty: 'id'
            });
        }
        
        Tine.Felamimail.sieve.VacationEditDialog.superclass.initComponent.call(this);
    },

    /**
     * overwrite update toolbars function (we don't have record grants yet)
     *
     * @private
     */
    updateToolbars: function () {
    },

    /**
     * executed after record got updated from proxy
     *
     * @private
     */
    onRecordLoad: async function () {
        await this.afterIsRendered();
        const hasRight = Tine.Felamimail.AccountEditDialog.prototype.checkAccountEditRight(this.account);
        this.action_saveAndClose.setDisabled(!hasRight);
        
        // mime type is always multipart/alternative
        this.record.set('mime', 'multipart/alternative');
        if (this.account && this.account.get('signature')) {
            this.record.set('signature', this.account.get('signature'));
        }

        this.getForm().loadRecord(this.record);
        
        var title = String.format(this.app.i18n._('Vacation Message for {0}'), this.account.get('name'));
        this.window.setTitle(title);
        
        this.checkStates();
        
        Tine.log.debug('Tine.Felamimail.sieve.VacationEditDialog::onRecordLoad() -> record:');
        Tine.log.debug(this.record);
        
        this.hideLoadMask();
    },
    
    /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initalisation is done.
     *
     * @return {Object}
     * @private
     *
     */
    getFormItems: function () {
        return this.vacationPanel = new Tine.Felamimail.sieve.VacationPanel({
                title:  i18n._('Vacation'),
                account: this.account,
                asAdminModule: true,
                editDialog: this,
            });
    },

    /**
     * generic request exception handler
     *
     * @param {Object} exception
     */
    onRequestFailed: function (exception) {
        this.saving = false;
        Tine.Felamimail.handleRequestException(exception);
        this.hideLoadMask();
    },

    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: async function () {
        Tine.Felamimail.sieve.VacationEditDialog.superclass.onRecordUpdate.call(this);
        
        let contactIds = [];
        Ext.each(['contact_id1', 'contact_id2'], function (field) {
            if (this.getForm().findField(field) && this.getForm().findField(field).getValue() !== '') {
                contactIds.push(this.getForm().findField(field).getValue());
            }
        }, this);
        
        let template = this.getForm().findField('template_id').getValue();
        
        this.record.set('contact_ids', contactIds);
        this.record.set('template_id', template);

        if (template !== '') {
            try {
                let response = await Tine.Felamimail.getVacationMessage(this.record.data);
                this.vacationPanel.reasonEditor.setValue(response.message);
            } catch (e) {
                Tine.Felamimail.handleRequestException(exception);
            }
        }
        
        let form = this.getForm();
        form.updateRecord(this.record);
        
        this.checkStates();
    },

    /**
     * call checkState for every field
     */
    checkStates: function() {
        this.getForm().items.each(function (item) {
            if (Ext.isFunction(item.checkState)) {
                item.checkState(this);
            }
        }, this)
    }
});

/**
 * Felamimail Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.sieve.VacationEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 640,
        height: 550,
        name: Tine.Felamimail.sieve.VacationEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.sieve.VacationEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
