/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (type, config) => {
        const recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${type}`)
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Print Paper Slip'),
            iconCls: `action_print`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                let record = this.initialConfig.selections[0]
                const editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                const maskMsg = app.formatMessage('Creating {type} Paper Slip', { type: recordClass.getRecordName() })

                if (editDialog) {
                    try {
                        await editDialog.isValid()
                    } catch (e) {
                        return
                    }
                }

                const createPaperSlip = async (mask) => {
                    try {
                        mask.show()
                        record = editDialog ? Tine.Tinebase.data.Record.setFromJson(await editDialog.applyChanges(), recordClass) : record
                        record = Tine.Tinebase.data.Record.setFromJson(await Tine.Sales.createPaperSlip(recordClass.getPhpClassName(), record.id), recordClass)
                        editDialog ? await editDialog.loadRecord(record) : null
                        window.postal.publish({
                            channel: "recordchange",
                            topic: [app.appName, recordClass, 'update'].join('.'),
                            data: {...record.data}
                        });
                    } catch (e) {
                        await Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: app.formatMessage('There where Errors:'),
                            msg: app.formatMessage('Cannot create { type } paper slip: ({e.code}) { e.message }', { type: record.getTitle(), e })
                        });
                    }
                    mask.hide()
                }

                if (Tine.OnlyOfficeIntegrator) {
                    Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.openWindow({
                        id: record.id,
                        contentPanelConstructorInterceptor: async (config) => {
                            const win = config.window.popup
                            const mainCardPanel = win.Tine.Tinebase.viewport.tineViewportMaincardpanel
                            mainCardPanel.get(0).hide()
                            const mask = new win.Ext.LoadMask(mainCardPanel.el, { msg: maskMsg })
                            await createPaperSlip(mask)
                            const attachment = record.get('attachments')[0]
                            Object.assign(config, {
                                recordData: attachment,
                                id: attachment.id
                            })
                            // @TODO: inject mailTo action
                        }
                    })
                } else {
                    const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen }).getEl()
                    const mask = new Ext.LoadMask(maskEl, { msg: maskMsg })
                    await createPaperSlip(mask)
                    alert('OnlyOfficeIntegrator missing -> find paperSlip in attachments')
                }
            }

        }, config))
    }

    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 10)
    })
})

