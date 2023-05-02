/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./DataProvenanceDialog');
require('./ContactGDPRPanel');

Ext.ns('Tine.GDPR.Addressbook');

Tine.GDPR.Addressbook.EditDialogPlugin = function() {};

Tine.GDPR.Addressbook.EditDialogPlugin.prototype = {
    contactEditDialog: null,
    app: null,

    init: function(cmp) {
        this.contactEditDialog = cmp;

        this.app = Tine.Tinebase.appMgr.get('GDPR');

        // intercept save contact
        cmp.on('save', this.onBeforeSave, this);
    },

    onBeforeSave: function(cmp, record, ticketFn) {
        var resolve = ticketFn();

        if (this.data) {
            // need this for duplicate check. Doesn´t show GDPR Windows after duplicate check.
            this.onBeforeApplyProvenanceDialog(this.data);
            resolve();

        } else {
            var window = Tine.GDPR.Addressbook.DataProvenanceDialog.openWindow({
                listeners: {
                    scope: this,
                    beforeapply: this.onBeforeApplyProvenanceDialog
                }
            });
            window.on('beforeclose', this.onBeforeCloseProvenanceDialog.createDelegate(this, [resolve]), this);
        }
    },

    validateDataProvenance: function() {
        var mandatoryConfig = Tine.Tinebase.configManager.get('dataProvenanceADBContactMandatory', 'GDPR');

        return !! (mandatoryConfig != 'yes' || this.contactEditDialog.record.get('GDPR_DataProvenance'));
    },

    onBeforeApplyProvenanceDialog: function(data) {
        var _ = window.lodash,
            me = this;

        _.each(data, function(v, k) {
            if (v) {
                me.contactEditDialog.record.set(k, v);
            }
        });

        this.data = data;

        return me.validateDataProvenance();
    },

    onBeforeCloseProvenanceDialog(resolve) {
        var _ = window.lodash,
            me = this;

        if (! me.validateDataProvenance()) {
            return false;
        }

        resolve();
    }
};
Ext.preg('Tine.GDPR.Addressbook.EditDialogPlugin', Tine.GDPR.Addressbook.EditDialogPlugin);
Ext.ux.pluginRegistry.register('/Addressbook/EditDialog/Contact', Tine.GDPR.Addressbook.EditDialogPlugin);

