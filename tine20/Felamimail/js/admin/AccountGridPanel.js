Ext.ns('Tine.Felamimail.admin');

Tine.Felamimail.admin.showAccountGridPanel = function () {
    var app = Tine.Tinebase.appMgr.get('Felamimail');
    if (! Tine.Felamimail.admin.emailAccountsGridPanel) {
        Tine.Felamimail.admin.emailAccountsGridPanel = new Tine.Felamimail.AccountGridPanel({
            asAdminModule: true,

            // NOTE: needed so ADMIN API's are used
            recordProxy: new Tine.Tinebase.data.RecordProxy({
                appName: 'Admin',
                modelName: 'EmailAccount',
                recordClass: Tine.Felamimail.Model.Account,
                idProperty: 'id'
            }),

            initComponent: function() {
                let app = Tine.Tinebase.appMgr.get('Felamimail');

                Tine.Felamimail.AccountGridPanel.prototype.initComponent.call(this);
            },

            initActions: function() {
                let isSystemAccountActionUpdater = function(action, grants, records, isFilterSelect) {

                    var enabled = !isFilterSelect
                        && records && records.length === 1
                        && _.indexOf(['system', 'shared', 'userInternal'], records[0].get('type')) > -1;

                    action.setDisabled(!enabled);
                };

                this.action_editVacation = new Ext.Action({
                    text: this.app.i18n._('Edit Vacation Message'),
                    iconCls: 'action_email_replyAll',
                    allowMultiple: false,
                    requiredGrant: 'editGrant',
                    disabled: true,
                    actionUpdater: isSystemAccountActionUpdater,
                    handler: () => {
                        let account = this.grid.getSelectionModel().getSelections()[0];
                        let record = new Tine.Felamimail.Model.Vacation({id: account.id}, account.id);

                        let popupWindow = Tine.Felamimail.sieve.VacationEditDialog.openWindow({
                            asAdminModule: true,
                            account: account,
                            record: record
                        });
                    }
                });

                this.action_editSieveRules = new Ext.Action({
                    text: this.app.i18n._('Edit Filter Rules'),
                    iconCls: 'action_email_forward',
                    allowMultiple: false,
                    requiredGrant: 'editGrant',
                    disabled: true,
                    actionUpdater: isSystemAccountActionUpdater,
                    handler: () => {
                        let account = this.grid.getSelectionModel().getSelections()[0];

                        let popupWindow = Tine.Felamimail.sieve.RulesDialog.openWindow({
                            asAdminModule: true,
                            account: account
                        });
                    }
                });

                Tine.Felamimail.AccountGridPanel.prototype.initActions.call(this);

                this.actionUpdater.addActions([
                    this.action_editVacation,
                    this.action_editSieveRules
                ]);
            },

            getActionToolbarItems: function() {
                return [
                    Ext.apply(new Ext.Button(this.action_editVacation), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_editSieveRules), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })
                ];
            },

            getContextMenuItems: function() {
                return [
                    '-',
                    this.action_editVacation,
                    this.action_editSieveRules
                ];
            }
        });
    } else {
        Tine.Felamimail.admin.emailAccountsGridPanel.loadGridData.defer(100, Tine.Felamimail.admin.emailAccountsGridPanel, []);
    }

    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Felamimail.admin.emailAccountsGridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Felamimail.admin.emailAccountsGridPanel.actionToolbar, true);
};